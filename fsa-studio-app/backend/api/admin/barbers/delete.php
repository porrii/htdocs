<?php
require_once __DIR__ . '/../../../config/cors.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error("Método no permitido", 405);
}

if (empty($_GET['id'])) {
    Response::error("ID es requerido");
}

try {
    AuthMiddleware::requireAdmin();

    $database = new Database();
    $db = $database->getConnection();

    $query = "DELETE FROM barberos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);

    if ($stmt->execute()) {
        Response::success(null, "Barbero eliminado exitosamente");
    } else {
        Response::serverError("Error al eliminar el barbero");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
