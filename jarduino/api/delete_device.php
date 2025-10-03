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
if (!isset($data['device_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de dispositivo no proporcionado']);
    exit();
}

$device_id = $data['device_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verificar que el dispositivo pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT device_id 
        FROM devices  
        WHERE device_id = ? AND user_id = ?
    ");
    $stmt->execute([$device_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Dispositivo no encontrado o no autorizado']);
        exit();
    }
    
    // Eliminar dispositivo
    $stmt = $pdo->prepare("DELETE FROM devices WHERE device_id = ?");
    $stmt->execute([$device_id]);
    
    echo json_encode(['success' => true, 'message' => 'Dispositivo eliminado correctamente']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>