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
$device_id = isset($_POST['device_id']) ? $_POST['device_id'] : null;

try {
    // Construir consulta base
    $where_conditions = ["d.user_id = ?", "a.resolved = 1"];
    $params = [$user_id];
    
    if ($device_id) {
        $where_conditions[] = "a.device_id = ?";
        $params[] = $device_id;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Eliminar alertas resueltas
    $stmt = $pdo->prepare("
        DELETE a FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE $where_clause
    ");
    $stmt->execute($params);
    
    $deleted_count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => "{$deleted_count} alertas eliminadas"
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>