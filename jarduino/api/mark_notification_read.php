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
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de notificación no proporcionado']);
    exit();
}

$notification_id = $data['id'];
$user_id = $_SESSION['user_id'];

try {
    // Verificar que la notificación pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE a.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$notification_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Notificación no encontrada o no autorizada']);
        exit();
    }
    
    // Marcar como leída
    $stmt = $pdo->prepare("UPDATE alerts SET resolved = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);
    
    echo json_encode(['success' => true, 'message' => 'Notificación marcada como leída']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>