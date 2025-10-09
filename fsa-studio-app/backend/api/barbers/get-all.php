<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Método no permitido", 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, nombre, especialidad, foto_url 
              FROM barberos 
              WHERE activo = 1 
              ORDER BY orden ASC, nombre ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $barbers = $stmt->fetchAll();

    Response::success($barbers, "Barberos obtenidos correctamente");

} catch (Exception $e) {
    Response::serverError("Error al obtener los barberos: " . $e->getMessage());
}
?>
