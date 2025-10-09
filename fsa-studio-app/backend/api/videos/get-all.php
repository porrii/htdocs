<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../utils/response.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, title, description, youtube_url, created_at 
        FROM videos 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success($videos);
    
} catch (Exception $e) {
    Response::error('Error al obtener videos: ' . $e->getMessage(), 500);
}
?>
