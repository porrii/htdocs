<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['device']) || !isset($data['duration'])) {
    echo json_encode(['success' => false, 'message' => 'Device and duration required']);
    exit;
}

try {
    $device = trim($data['device']);
    $duration = (int)$data['duration'];
    $mode = isset($data['mode']);

    if ($duration <= 0) {
        echo json_encode(['success' => false, 'message' => 'Duration must be positive']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO irrigation_logs (device_id, duration, mode) 
        VALUES (?, ?, ?)
    ");
    $success = $stmt->execute([$device, $duration, $mode]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => "Riego iniciado en dispositivo $device por $duration segundos (modo $mode)"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo registrar el riego']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
