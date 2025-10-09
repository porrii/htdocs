<?php
require_once __DIR__ . '/../../../config/cors.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->nombre)) {
    Response::error("ID y nombre son requeridos");
}

try {
    AuthMiddleware::requireAdmin();

    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE barberos 
              SET nombre = :nombre, 
                  especialidad = :especialidad, 
                  foto_url = :foto_url, 
                  activo = :activo, 
                  orden = :orden 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    $especialidad = !empty($data->especialidad) ? $data->especialidad : null;
    $foto_url = !empty($data->foto_url) ? $data->foto_url : null;
    $activo = isset($data->activo) ? $data->activo : 1;
    $orden = isset($data->orden) ? $data->orden : 0;

    $stmt->bindParam(":id", $data->id);
    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":especialidad", $especialidad);
    $stmt->bindParam(":foto_url", $foto_url);
    $stmt->bindParam(":activo", $activo);
    $stmt->bindParam(":orden", $orden);

    if ($stmt->execute()) {
        Response::success(null, "Barbero actualizado exitosamente");
    } else {
        Response::serverError("Error al actualizar el barbero");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
