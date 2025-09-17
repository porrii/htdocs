<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Marcar todas las notificaciones como leídas
    $stmt = $pdo->prepare("
        UPDATE alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        SET a.resolved = 1 
        WHERE d.user_id = ? AND a.resolved = 0
    ");
    $stmt->execute([$user_id]);
    
    $affected_rows = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => "{$affected_rows} notificaciones marcadas como leídas"
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>