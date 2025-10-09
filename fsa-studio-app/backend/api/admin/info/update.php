<?php
require_once __DIR__ . '/../../../config/cors.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

try {
    AuthMiddleware::requireAdmin();

    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE info_barberia SET 
              nombre = :nombre,
              direccion = :direccion,
              telefono = :telefono,
              email = :email,
              horario_lunes = :horario_lunes,
              horario_martes = :horario_martes,
              horario_miercoles = :horario_miercoles,
              horario_jueves = :horario_jueves,
              horario_viernes = :horario_viernes,
              horario_sabado = :horario_sabado,
              horario_domingo = :horario_domingo,
              descripcion = :descripcion,
              instagram = :instagram,
              facebook = :facebook,
              whatsapp = :whatsapp
              WHERE id = 1";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":direccion", $data->direccion);
    $stmt->bindParam(":telefono", $data->telefono);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":horario_lunes", $data->horario_lunes);
    $stmt->bindParam(":horario_martes", $data->horario_martes);
    $stmt->bindParam(":horario_miercoles", $data->horario_miercoles);
    $stmt->bindParam(":horario_jueves", $data->horario_jueves);
    $stmt->bindParam(":horario_viernes", $data->horario_viernes);
    $stmt->bindParam(":horario_sabado", $data->horario_sabado);
    $stmt->bindParam(":horario_domingo", $data->horario_domingo);
    $stmt->bindParam(":descripcion", $data->descripcion);
    $stmt->bindParam(":instagram", $data->instagram);
    $stmt->bindParam(":facebook", $data->facebook);
    $stmt->bindParam(":whatsapp", $data->whatsapp);

    if ($stmt->execute()) {
        Response::success(null, "Información actualizada exitosamente");
    } else {
        Response::serverError("Error al actualizar la información");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
