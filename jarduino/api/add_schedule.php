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
if (!isset($data['device_id']) || !isset($data['start_time']) || !isset($data['duration']) || !isset($data['days_of_week'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$device_id = $data['device_id'];
$start_time = $data['start_time'];
$duration = $data['duration'];
$days_of_week = $data['days_of_week'];
$active = isset($data['active']) ? (int)$data['active'] : 1;
$user_id = $_SESSION['user_id'];

try {
    // Verificar que el dispositivo pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE device_id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Dispositivo no encontrado o no autorizado']);
        exit();
    }
    
    // Insertar nueva programación
    $stmt = $pdo->prepare("
        INSERT INTO irrigation_schedules (device_id, start_time, duration, days_of_week, active) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$device_id, $start_time, $duration, $days_of_week, $active]);
    
    echo json_encode(['success' => true, 'message' => 'Programación añadida correctamente', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>