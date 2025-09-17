<?php
// Cambiar la lógica para verificar la antigüedad
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['device'])) {
    echo json_encode(['success' => false, 'message' => 'Device ID required']);
    exit;
}

try {
    // Siempre insertar con online_status = 1 cuando llegan datos
    $stmt = $pdo->prepare("
        INSERT INTO sensor_data 
        (device_id, temperature, air_humidity, soil_moisture, pump_status, online_status) 
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    
    $success = $stmt->execute([
        $data['device'],
        $data['temperature'] ?? null,
        $data['air_humidity'] ?? null,
        $data['soil_moisture'] ?? null,
        $data['pump_status'] ?? 0
    ]);
    
    echo json_encode(['success' => $success]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>