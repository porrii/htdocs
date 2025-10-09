<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->servicio_id) || empty($data->fecha) || empty($data->hora)) {
    Response::error("Servicio, fecha y hora son requeridos");
}

try {
    $user = AuthMiddleware::authenticate();

    $database = new Database();
    $db = $database->getConnection();

    // Verificar que el servicio existe y está activo
    $serviceQuery = "SELECT id FROM servicios WHERE id = :servicio_id AND activo = 1";
    $serviceStmt = $db->prepare($serviceQuery);
    $serviceStmt->bindParam(":servicio_id", $data->servicio_id);
    $serviceStmt->execute();

    if ($serviceStmt->rowCount() === 0) {
        Response::error("Servicio no encontrado o no disponible");
    }

    // Si se seleccionó barbero, verificar disponibilidad
    if (!empty($data->barbero_id)) {
        // Verificar que el barbero existe y está activo
        $barberQuery = "SELECT id FROM barberos WHERE id = :barbero_id AND activo = 1";
        $barberStmt = $db->prepare($barberQuery);
        $barberStmt->bindParam(":barbero_id", $data->barbero_id);
        $barberStmt->execute();

        if ($barberStmt->rowCount() === 0) {
            Response::error("Barbero no encontrado o no disponible");
        }

        // Verificar que no haya conflicto de horario
        $conflictQuery = "SELECT id FROM citas 
                         WHERE barbero_id = :barbero_id 
                         AND fecha = :fecha 
                         AND hora = :hora 
                         AND estado != 'cancelada'";
        $conflictStmt = $db->prepare($conflictQuery);
        $conflictStmt->bindParam(":barbero_id", $data->barbero_id);
        $conflictStmt->bindParam(":fecha", $data->fecha);
        $conflictStmt->bindParam(":hora", $data->hora);
        $conflictStmt->execute();

        if ($conflictStmt->rowCount() > 0) {
            Response::error("El barbero ya tiene una cita en ese horario", 409);
        }
    }

    // Crear la cita
    $query = "INSERT INTO citas (usuario_id, barbero_id, servicio_id, fecha, hora, notas, estado) 
              VALUES (:usuario_id, :barbero_id, :servicio_id, :fecha, :hora, :notas, 'pendiente')";
    
    $stmt = $db->prepare($query);
    
    $barbero_id = !empty($data->barbero_id) ? $data->barbero_id : null;
    $notas = !empty($data->notas) ? $data->notas : null;

    $stmt->bindParam(":usuario_id", $user['id']);
    $stmt->bindParam(":barbero_id", $barbero_id);
    $stmt->bindParam(":servicio_id", $data->servicio_id);
    $stmt->bindParam(":fecha", $data->fecha);
    $stmt->bindParam(":hora", $data->hora);
    $stmt->bindParam(":notas", $notas);

    if ($stmt->execute()) {
        $citaId = $db->lastInsertId();

        // Crear notificación
        $notifQuery = "INSERT INTO notificaciones (usuario_id, cita_id, tipo, mensaje) 
                      VALUES (:usuario_id, :cita_id, 'confirmacion', :mensaje)";
        $notifStmt = $db->prepare($notifQuery);
        $mensaje = "Tu cita ha sido agendada para el " . date('d/m/Y', strtotime($data->fecha)) . " a las " . substr($data->hora, 0, 5);
        $notifStmt->bindParam(":usuario_id", $user['id']);
        $notifStmt->bindParam(":cita_id", $citaId);
        $notifStmt->bindParam(":mensaje", $mensaje);
        $notifStmt->execute();

        Response::success([
            'id' => $citaId
        ], "Cita creada exitosamente", 201);
    } else {
        Response::serverError("Error al crear la cita");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
