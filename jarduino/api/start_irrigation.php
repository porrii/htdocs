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
if (!isset($data['device_id']) || !isset($data['duration']) || !isset($data['mode'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$device_id = $data['device_id'];
$duration = $data['duration'];
$mode = $data['mode'];
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
    
    // Enviar comando al ESP32 (simulado)
    // En un sistema real, aquí enviarías el comando al dispositivo
    // Por ejemplo, mediante MQTT, HTTP request, etc.
    
    // Registrar el riego en la base de datos
    $stmt = $pdo->prepare("
        INSERT INTO irrigation_log (device_id, duration, mode, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$device_id, $duration, $mode]);
    
    // Actualizar estado de la bomba
    $stmt = $pdo->prepare("
        UPDATE sensor_data 
        SET pump_status = 1 
        WHERE device_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$device_id]);
    
    echo json_encode(['success' => true, 'message' => 'Riego iniciado']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>