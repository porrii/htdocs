<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    Response::error("ID de cita es requerido");
}

try {
    $user = AuthMiddleware::authenticate();

    $database = new Database();
    $db = $database->getConnection();

    // Verificar que la cita existe y pertenece al usuario
    $checkQuery = "SELECT id FROM citas WHERE id = :id AND usuario_id = :usuario_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":id", $data->id);
    $checkStmt->bindParam(":usuario_id", $user['id']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        Response::error("Cita no encontrada", 404);
    }

    // Cancelar la cita
    $query = "UPDATE citas SET estado = 'cancelada' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        // Crear notificación
        $notifQuery = "INSERT INTO notificaciones (usuario_id, cita_id, tipo, mensaje) 
                      VALUES (:usuario_id, :cita_id, 'cancelacion', 'Tu cita ha sido cancelada')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->bindParam(":usuario_id", $user['id']);
        $notifStmt->bindParam(":cita_id", $data->id);
        $notifStmt->execute();

        Response::success(null, "Cita cancelada exitosamente");
    } else {
        Response::serverError("Error al cancelar la cita");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
