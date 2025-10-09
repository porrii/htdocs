<?php
require_once __DIR__ . '/../../../config/cors.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Método no permitido", 405);
}

try {
    AuthMiddleware::requireAdmin();

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM info_barberia LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $info = $stmt->fetch();

    if (!$info) {
        Response::error("Información no encontrada", 404);
    }

    Response::success($info, "Información obtenida correctamente");

} catch (Exception $e) {
    Response::serverError("Error al obtener la información: " . $e->getMessage());
}
?>
