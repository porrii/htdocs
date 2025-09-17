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

// Verificar confirmación adicional por seguridad
$confirmation = json_decode(file_get_contents('php://input'), true);
if (!isset($confirmation['confirm']) || $confirmation['confirm'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Confirmación requerida']);
    exit();
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Obtener todos los dispositivos del usuario
    $stmt = $pdo->prepare("SELECT device_id FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($devices)) {
        $placeholders = implode(',', array_fill(0, count($devices), '?'));
        
        // 2. Eliminar datos de sensores
        $stmt = $pdo->prepare("DELETE FROM sensor_data WHERE device_id IN ($placeholders)");
        $stmt->execute($devices);
        
        // 3. Eliminar registros de riego
        $stmt = $pdo->prepare("DELETE FROM irrigation_log WHERE device_id IN ($placeholders)");
        $stmt->execute($devices);
        
        // 4. Eliminar configuraciones de dispositivos
        $stmt = $pdo->prepare("DELETE FROM device_config WHERE device_id IN ($placeholders)");
        $stmt->execute($devices);
        
        // 5. Eliminar programaciones de riego
        $stmt = $pdo->prepare("DELETE FROM irrigation_schedules WHERE device_id IN ($placeholders)");
        $stmt->execute($devices);
        
        // 6. Eliminar alertas
        $stmt = $pdo->prepare("DELETE FROM alerts WHERE device_id IN ($placeholders)");
        $stmt->execute($devices);
        
        // 7. Eliminar reportes
        $stmt = $pdo->prepare("DELETE FROM reports WHERE device_id IN ($placeholders)");
        $stmt->execute($devices);
        
        // 8. Eliminar dispositivos
        $stmt = $pdo->prepare("DELETE FROM devices WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // 9. Eliminar preferencias del usuario
    $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // 10. Eliminar backups
    $stmt = $pdo->prepare("DELETE FROM backups WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // 11. Eliminar usuario
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Confirmar transacción
    $pdo->commit();
    
    // Limpiar sesión
    session_unset();
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Cuenta eliminada correctamente']);
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la cuenta: ' . $e->getMessage()]);
}
?>