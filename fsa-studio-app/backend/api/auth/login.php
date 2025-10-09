<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    Response::error("Email y contraseña son requeridos");
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        Response::error("Credenciales inválidas", 401);
    }

    $user = $stmt->fetch();

    if (!$user['activo']) {
        Response::error("Usuario desactivado. Contacte al administrador", 403);
    }

    if (!password_verify($data->password, $user['password'])) {
        Response::error("Credenciales inválidas", 401);
    }

    // Actualizar último acceso
    $updateQuery = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(":id", $user['id']);
    $updateStmt->execute();

    // Generar token JWT
    $payload = [
        'id' => $user['id'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'iat' => time(),
        'exp' => time() + (86400 * 30) // 30 días
    ];

    $token = JWT::encode($payload);

    Response::success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol']
        ]
    ], "Inicio de sesión exitoso");

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
