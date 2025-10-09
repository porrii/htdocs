<?php
require_once '../../../config/database.php';
require_once '../../../config/cors.php';
require_once '../../../utils/response.php';
require_once '../../../middleware/auth.php';

header('Content-Type: application/json');

try {
    $user = requireAuth();
    
    if ($user['role'] !== 'admin') {
        Response::error('Acceso denegado', 403);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, title, description, youtube_url, is_active, created_at 
        FROM videos 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success($videos);
    
} catch (Exception $e) {
    Response::error('Error al obtener videos: ' . $e->getMessage(), 500);
}
?>
