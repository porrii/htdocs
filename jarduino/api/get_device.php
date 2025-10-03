<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Verificar que se proporcionó el ID del device
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de dispositivo no proporcionado']);
    exit();
}

$device_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT d.*, dc.auto_irrigation, dc.threshold, dc.duration 
        FROM devices d 
        LEFT JOIN device_config dc ON d.device_id = dc.device_id 
        WHERE d.device_id = ?
    ");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        echo json_encode(['success' => false, 'message' => 'Dispositivo no encontrado']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $device]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
