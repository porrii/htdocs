<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->fecha) || empty($data->hora)) {
    Response::error("ID, fecha y hora son requeridos");
}

try {
    $user = AuthMiddleware::authenticate();

    $database = new Database();
    $db = $database->getConnection();

    // Verificar que la cita existe y pertenece al usuario
    $checkQuery = "SELECT id, barbero_id FROM citas WHERE id = :id AND usuario_id = :usuario_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":id", $data->id);
    $checkStmt->bindParam(":usuario_id", $user['id']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        Response::error("Cita no encontrada", 404);
    }

    $cita = $checkStmt->fetch();

    // Si hay barbero asignado, verificar disponibilidad en el nuevo horario
    if ($cita['barbero_id']) {
        $conflictQuery = "SELECT id FROM citas 
                         WHERE barbero_id = :barbero_id 
                         AND fecha = :fecha 
                         AND hora = :hora 
                         AND id != :id
                         AND estado != 'cancelada'";
        $conflictStmt = $db->prepare($conflictQuery);
        $conflictStmt->bindParam(":barbero_id", $cita['barbero_id']);
        $conflictStmt->bindParam(":fecha", $data->fecha);
        $conflictStmt->bindParam(":hora", $data->hora);
        $conflictStmt->bindParam(":id", $data->id);
        $conflictStmt->execute();

        if ($conflictStmt->rowCount() > 0) {
            Response::error("El barbero ya tiene una cita en ese horario", 409);
        }
    }

    // Actualizar la cita
    $query = "UPDATE citas SET fecha = :fecha, hora = :hora WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":fecha", $data->fecha);
    $stmt->bindParam(":hora", $data->hora);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        // Crear notificación
        $notifQuery = "INSERT INTO notificaciones (usuario_id, cita_id, tipo, mensaje) 
                      VALUES (:usuario_id, :cita_id, 'confirmacion', :mensaje)";
        $notifStmt = $db->prepare($notifQuery);
        $mensaje = "Tu cita ha sido reprogramada para el " . date('d/m/Y', strtotime($data->fecha)) . " a las " . substr($data->hora, 0, 5);
        $notifStmt->bindParam(":usuario_id", $user['id']);
        $notifStmt->bindParam(":cita_id", $data->id);
        $notifStmt->bindParam(":mensaje", $mensaje);
        $notifStmt->execute();

        Response::success(null, "Cita actualizada exitosamente");
    } else {
        Response::serverError("Error al actualizar la cita");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
