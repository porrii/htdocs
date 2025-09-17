<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (!isset($data['device'])) {
    echo json_encode(['success' => false, 'message' => 'ID de dispositivo no proporcionado']);
    exit();
}

$device_id = $data['device'];
$auto_irrigation = isset($data['auto_irrigation']) ? (int)$data['auto_irrigation'] : 0;
$threshold = isset($data['threshold']) ? $data['threshold'] : 50;
$duration = isset($data['duration']) ? $data['duration'] : 10;
$user_id = $_SESSION['user_id'];

try {
    // Verificar que el dispositivo pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE device_id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        echo json_encode(['success' => false, 'message' => 'Dispositivo no encontrado o no autorizado']);
        exit();
    }
    
    // Verificar si ya existe configuración para este dispositivo
    $stmt = $pdo->prepare("SELECT * FROM device_config WHERE device_id = ?");
    $stmt->execute([$device_id]);
    $existing_config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_config) {
        // Actualizar configuración existente
        $stmt = $pdo->prepare("
            UPDATE device_config 
            SET auto_irrigation = ?, threshold = ?, duration = ?, updated_at = NOW() 
            WHERE device_id = ?
        ");
        $stmt->execute([$auto_irrigation, $threshold, $duration, $device_id]);
    } else {
        // Insertar nueva configuración
        $stmt = $pdo->prepare("
            INSERT INTO device_config (device_id, auto_irrigation, threshold, duration, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$device_id, $auto_irrigation, $threshold, $duration]);
    }
    
    // Enviar configuración al ESP32 (simulado)
    // En un sistema real, aquí enviarías la configuración al dispositivo
    
    echo json_encode(['success' => true, 'message' => 'Configuración guardada']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>