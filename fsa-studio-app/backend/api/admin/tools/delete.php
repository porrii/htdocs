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
    
    $stmt = $db->prepare("DELETE FROM tools WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    
    Response::success(['message' => 'Herramienta eliminada exitosamente']);
    
} catch (Exception $e) {
    Response::error('Error al eliminar herramienta: ' . $e->getMessage(), 500);
}
?>
