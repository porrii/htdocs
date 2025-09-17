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
if (!isset($data['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de programación no proporcionado']);
    exit();
}

$schedule_id = $data['schedule_id'];
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
    
    // Eliminar programación
    $stmt = $pdo->prepare("DELETE FROM irrigation_schedules WHERE id = ?");
    $stmt->execute([$schedule_id]);
    
    echo json_encode(['success' => true, 'message' => 'Programación eliminada correctamente']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>