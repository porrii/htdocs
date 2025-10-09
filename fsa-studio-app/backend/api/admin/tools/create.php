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
    
    if (!isset($data['name'])) {
        Response::error('Nombre de herramienta requerido', 400);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO tools (name, description, image_url) 
        VALUES (:name, :description, :image_url)
    ");
    
    $stmt->execute([
        ':name' => $data['name'],
        ':description' => $data['description'] ?? '',
        ':image_url' => $data['image_url'] ?? null
    ]);
    
    $toolId = $db->lastInsertId();
    
    Response::success([
        'id' => $toolId,
        'message' => 'Herramienta creada exitosamente'
    ]);
    
} catch (Exception $e) {
    Response::error('Error al crear herramienta: ' . $e->getMessage(), 500);
}
?>
