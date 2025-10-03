<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Leer datos JSON enviados desde JS
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$schedule_id = $input['schedule_id'];
$device_id = $input['device_id'] ?? null;
$start_time = $input['start_time'] ?? null;
$duration = $input['duration'] ?? null;
$days_of_week = $input['days_of_week'] ?? null;
$active = isset($input['active']) ? (bool)$input['active'] : true;

// Validaciones mínimas
if (!$device_id || !$start_time || !$duration || !$days_of_week) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE irrigation_schedules
        SET device_id = ?, start_time = ?, duration = ?, days_of_week = ?, active = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$device_id, $start_time, $duration, $days_of_week, $active, $schedule_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
