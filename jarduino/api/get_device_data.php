<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Verificar si se proporcionó un ID de dispositivo
if (!isset($_GET['device_id']) || empty($_GET['device_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de dispositivo no proporcionado']);
    exit();
}

$device_id = $_GET['device_id'];

try {
    // Obtener el estado actual del dispositivo
    $stmt = $pdo->prepare("
        SELECT online_status, pump_status, temperature, air_humidity, soil_moisture 
        FROM sensor_data 
        WHERE device_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$device_id]);
    $current_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener datos históricos (últimas 24 horas)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%H:%i') as time,
            temperature, 
            air_humidity, 
            soil_moisture 
        FROM sensor_data 
        WHERE device_id = ? AND created_at >= NOW() - INTERVAL 24 HOUR 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$device_id]);
    $sensor_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'online_status' => $current_data ? (bool)$current_data['online_status'] : false,
        'pump_status' => $current_data ? (bool)$current_data['pump_status'] : false,
        'temperature' => $current_data ? $current_data['temperature'] : null,
        'air_humidity' => $current_data ? $current_data['air_humidity'] : null,
        'soil_moisture' => $current_data ? $current_data['soil_moisture'] : null,
        'sensor_data' => $sensor_data
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>