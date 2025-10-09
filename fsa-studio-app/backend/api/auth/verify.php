<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Método no permitido", 405);
}

try {
    $user = AuthMiddleware::authenticate();

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, nombre, email, rol, telefono FROM usuarios WHERE id = :id AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $user['id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        Response::unauthorized("Usuario no encontrado o desactivado");
    }

    $userData = $stmt->fetch();

    Response::success([
        'user' => $userData
    ], "Token válido");

} catch (Exception $e) {
    Response::serverError("Error al verificar el token: " . $e->getMessage());
}
?>
