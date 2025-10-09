<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../utils/response.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, name, description, image_url, created_at 
        FROM tools 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    
    $stmt->execute();
    $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success($tools);
    
} catch (Exception $e) {
    Response::error('Error al obtener herramientas: ' . $e->getMessage(), 500);
}
?>
