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

    $query = "SELECT id, nombre, descripcion, duracion, precio 
              FROM servicios 
              WHERE activo = 1 
              ORDER BY nombre ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $services = $stmt->fetchAll();

    Response::success($services, "Servicios obtenidos correctamente");

} catch (Exception $e) {
    Response::serverError("Error al obtener los servicios: " . $e->getMessage());
}
?>
