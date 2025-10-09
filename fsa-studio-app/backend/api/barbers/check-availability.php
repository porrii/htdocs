<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Método no permitido", 405);
}

if (empty($_GET['barbero_id']) || empty($_GET['fecha']) || empty($_GET['hora'])) {
    Response::error("Barbero, fecha y hora son requeridos");
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id FROM citas 
              WHERE barbero_id = :barbero_id 
              AND fecha = :fecha 
              AND hora = :hora 
              AND estado != 'cancelada'";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":barbero_id", $_GET['barbero_id']);
    $stmt->bindParam(":fecha", $_GET['fecha']);
    $stmt->bindParam(":hora", $_GET['hora']);
    $stmt->execute();

    $available = $stmt->rowCount() === 0;

    Response::success([
        'available' => $available
    ], $available ? "Horario disponible" : "Horario no disponible");

} catch (Exception $e) {
    Response::serverError("Error al verificar disponibilidad: " . $e->getMessage());
}
?>
