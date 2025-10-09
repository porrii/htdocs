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

    $query = "SELECT 
                c.id,
                c.fecha,
                c.hora,
                c.estado,
                c.notas,
                s.nombre as servicio_nombre,
                s.duracion as servicio_duracion,
                s.precio as servicio_precio,
                b.nombre as barbero_nombre,
                b.foto_url as barbero_foto
              FROM citas c
              INNER JOIN servicios s ON c.servicio_id = s.id
              LEFT JOIN barberos b ON c.barbero_id = b.id
              WHERE c.usuario_id = :usuario_id
              ORDER BY c.fecha DESC, c.hora DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $user['id']);
    $stmt->execute();

    $appointments = $stmt->fetchAll();

    Response::success($appointments, "Citas obtenidas correctamente");

} catch (Exception $e) {
    Response::serverError("Error al obtener las citas: " . $e->getMessage());
}
?>
