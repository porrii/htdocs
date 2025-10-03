<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Verificar que se proporcionó el ID del schedule
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de programación no proporcionado']);
    exit();
}

$schedule_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT id AS schedule_id, device_id, start_time, duration, days_of_week, active
        FROM irrigation_schedules
        WHERE id = ?
    ");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Programación no encontrada']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $schedule]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
