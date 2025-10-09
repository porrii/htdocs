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
    
    if (!isset($data['id'])) {
        Response::error('ID de video requerido', 400);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $updates = [];
    $params = [':id' => $data['id']];
    
    if (isset($data['title'])) {
        $updates[] = "title = :title";
        $params[':title'] = $data['title'];
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = :description";
        $params[':description'] = $data['description'];
    }
    
    if (isset($data['youtube_url'])) {
        // Extract YouTube video ID from URL
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $data['youtube_url'], $matches);
        
        if (!isset($matches[1])) {
            Response::error('URL de YouTube inválida', 400);
            exit;
        }
        
        $video_id = $matches[1];
        $embed_url = "https://www.youtube.com/embed/" . $video_id;
        
        $updates[] = "youtube_url = :youtube_url";
        $params[':youtube_url'] = $embed_url;
    }
    
    if (isset($data['is_active'])) {
        $updates[] = "is_active = :is_active";
        $params[':is_active'] = $data['is_active'] ? 1 : 0;
    }
    
    if (empty($updates)) {
        Response::error('No hay datos para actualizar', 400);
        exit;
    }
    
    $sql = "UPDATE videos SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    Response::success(['message' => 'Video actualizado exitosamente']);
    
} catch (Exception $e) {
    Response::error('Error al actualizar video: ' . $e->getMessage(), 500);
}
?>
