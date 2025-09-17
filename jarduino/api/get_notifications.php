<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

// Mostrar errores en desarrollo (puedes quitar esto en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener parámetros
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$unread_only = isset($_GET['unread']) && $_GET['unread'] == 'true';

try {
    // Construir condiciones dinámicas
    $where_conditions = ["d.user_id = ?"];
    $params = [$user_id];
    
    if ($unread_only) {
        $where_conditions[] = "a.resolved = 0";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Obtener notificaciones
    $sql = "
        SELECT 
            a.id,
            a.device_id,
            a.title,
            a.message,
            a.priority,
            a.resolved,
            a.created_at,
            d.name as device_name,
            TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as minutes_ago,
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, a.created_at, NOW()), ' min')
                WHEN TIMESTAMPDIFF(HOUR, a.created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, a.created_at, NOW()), ' h')
                ELSE CONCAT(TIMESTAMPDIFF(DAY, a.created_at, NOW()), ' d')
            END as time_ago
        FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE $where_clause 
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener conteo total (usa los mismos parámetros base)
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as total_count 
        FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE $where_clause
    ");
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total_count'];
    
    // Obtener conteo de no leídas
    $stmt_unread = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE d.user_id = ? AND a.resolved = 0
    ");
    $stmt_unread->execute([$user_id]);
    $unread_count = $stmt_unread->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total_count' => $total_count,
        'unread_count' => $unread_count
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
