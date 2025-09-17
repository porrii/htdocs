<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['device']) || !isset($data['online'])) {
    echo json_encode(['success' => false, 'message' => 'Device and online status required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO sensor_data 
        (device_id, online_status) 
        VALUES (?, ?)
    ");
    
    $success = $stmt->execute([
        $data['device'],
        $data['online'] ? 1 : 0
    ]);
    
    echo json_encode(['success' => $success]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>