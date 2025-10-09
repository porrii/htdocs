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
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['title']) || !isset($data['youtube_url'])) {
        Response::error('Título y URL de YouTube son requeridos', 400);
        exit;
    }
    
    // Extract YouTube video ID from URL
    $youtube_url = $data['youtube_url'];
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
    
    if (!isset($matches[1])) {
        Response::error('URL de YouTube inválida', 400);
        exit;
    }
    
    $video_id = $matches[1];
    $embed_url = "https://www.youtube.com/embed/" . $video_id;
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO videos (title, description, youtube_url) 
        VALUES (:title, :description, :youtube_url)
    ");
    
    $stmt->execute([
        ':title' => $data['title'],
        ':description' => $data['description'] ?? '',
        ':youtube_url' => $embed_url
    ]);
    
    $videoId = $db->lastInsertId();
    
    Response::success([
        'id' => $videoId,
        'message' => 'Video creado exitosamente'
    ]);
    
} catch (Exception $e) {
    Response::error('Error al crear video: ' . $e->getMessage(), 500);
}
?>
