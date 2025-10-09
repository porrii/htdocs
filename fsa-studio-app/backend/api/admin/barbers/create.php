<?php
require_once __DIR__ . '/../../../config/cors.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->nombre)) {
    Response::error("El nombre es requerido");
}

try {
    AuthMiddleware::requireAdmin();

    $database = new Database();
    $db = $database->getConnection();

    $query = "INSERT INTO barberos (nombre, especialidad, foto_url, activo, orden) 
              VALUES (:nombre, :especialidad, :foto_url, :activo, :orden)";
    
    $stmt = $db->prepare($query);
    
    $especialidad = !empty($data->especialidad) ? $data->especialidad : null;
    $foto_url = !empty($data->foto_url) ? $data->foto_url : null;
    $activo = isset($data->activo) ? $data->activo : 1;
    $orden = isset($data->orden) ? $data->orden : 0;

    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":especialidad", $especialidad);
    $stmt->bindParam(":foto_url", $foto_url);
    $stmt->bindParam(":activo", $activo);
    $stmt->bindParam(":orden", $orden);

    if ($stmt->execute()) {
        Response::success([
            'id' => $db->lastInsertId()
        ], "Barbero creado exitosamente", 201);
    } else {
        Response::serverError("Error al crear el barbero");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
