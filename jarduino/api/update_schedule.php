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
if (!isset($data['schedule_id']) || !isset($data['active'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$schedule_id = $data['schedule_id'];
$active = $data['active'];
$user_id = $_SESSION['user_id'];

try {
    // Verificar que la programación pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT s.id 
        FROM irrigation_schedules s 
        JOIN devices d ON s.device_id = d.device_id 
        WHERE s.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$schedule_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Programación no encontrada o no autorizada']);
        exit();
    }
    
    // Actualizar estado de la programación
    $stmt = $pdo->prepare("UPDATE irrigation_schedules SET active = ? WHERE id = ?");
    $stmt->execute([$active, $schedule_id]);
    
    echo json_encode(['success' => true, 'message' => 'Programación actualizada correctamente']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>