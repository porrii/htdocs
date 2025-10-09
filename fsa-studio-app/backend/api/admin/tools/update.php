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
        Response::error('ID de herramienta requerido', 400);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $updates = [];
    $params = [':id' => $data['id']];
    
    if (isset($data['name'])) {
        $updates[] = "name = :name";
        $params[':name'] = $data['name'];
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = :description";
        $params[':description'] = $data['description'];
    }
    
    if (isset($data['image_url'])) {
        $updates[] = "image_url = :image_url";
        $params[':image_url'] = $data['image_url'];
    }
    
    if (isset($data['is_active'])) {
        $updates[] = "is_active = :is_active";
        $params[':is_active'] = $data['is_active'] ? 1 : 0;
    }
    
    if (empty($updates)) {
        Response::error('No hay datos para actualizar', 400);
        exit;
    }
    
    $sql = "UPDATE tools SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    Response::success(['message' => 'Herramienta actualizada exitosamente']);
    
} catch (Exception $e) {
    Response::error('Error al actualizar herramienta: ' . $e->getMessage(), 500);
}
?>
