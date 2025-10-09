<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Método no permitido", 405);
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->nombre) || empty($data->email) || empty($data->password)) {
    Response::error("Nombre, email y contraseña son requeridos");
}

// Validar formato de email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    Response::error("Formato de email inválido");
}

// Validar longitud de contraseña
if (strlen($data->password) < 6) {
    Response::error("La contraseña debe tener al menos 6 caracteres");
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el email ya existe
    $checkQuery = "SELECT id FROM usuarios WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":email", $data->email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        Response::error("El email ya está registrado", 409);
    }

    // Crear nuevo usuario
    $query = "INSERT INTO usuarios (nombre, email, password, telefono, rol) VALUES (:nombre, :email, :password, :telefono, 'cliente')";
    $stmt = $db->prepare($query);

    $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);
    $telefono = isset($data->telefono) ? $data->telefono : null;

    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashedPassword);
    $stmt->bindParam(":telefono", $telefono);

    if ($stmt->execute()) {
        $userId = $db->lastInsertId();

        // Generar token JWT
        $payload = [
            'id' => $userId,
            'email' => $data->email,
            'rol' => 'cliente',
            'iat' => time(),
            'exp' => time() + (86400 * 30) // 30 días
        ];

        $token = JWT::encode($payload);

        Response::success([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'nombre' => $data->nombre,
                'email' => $data->email,
                'rol' => 'cliente'
            ]
        ], "Registro exitoso", 201);
    } else {
        Response::serverError("Error al crear el usuario");
    }

} catch (Exception $e) {
    Response::serverError("Error al procesar la solicitud: " . $e->getMessage());
}
?>
