<?php
header('Content-Type: application/json');
include('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['device'])) {
    echo json_encode(['success' => false, 'message' => 'Device ID missing']);
    exit;
}

$device = $data['device'];
$temperature = isset($data['temperature']) ? floatval($data['temperature']) : null;
$air_humidity = isset($data['air_humidity']) ? floatval($data['air_humidity']) : null;
$soil_moisture = isset($data['soil_moisture']) ? intval($data['soil_moisture']) : null;
$pump_status = isset($data['pump_status']) ? intval($data['pump_status']) : 0;

try {
    $stmt = $pdo->prepare("INSERT INTO sensor_data (device_id, temperature, air_humidity, soil_moisture, pump_status) VALUES (?, ?, ?, ?, ?)");
    $success = $stmt->execute([$device, $temperature, $air_humidity, $soil_moisture, $pump_status]);
    
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>