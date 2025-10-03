<?php
// Funciones de autenticación y seguridad

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Verificar si el usuario tiene un rol específico
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Obtener el ID del usuario actual
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Cerrar sesión
function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    if (!isAuthenticated()) {
        header("Location: ../login.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "logout") {
    logout();
}

// Verificar credenciales de inicio de sesión
function verifyCredentials($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.user_id AS u_user_id, u.username, u.email, u.password, u.role,
            up.dark_mode, up.timezone, up.language
        FROM users u
        LEFT JOIN user_preferences up ON u.user_id = up.user_id
        WHERE u.username = ? OR u.email = ?
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

// Registrar un nuevo usuario
function registerUser($username, $email, $password) {
    global $pdo;
    
    // Verificar si el usuario o email ya existen
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        return false;
    }
    
    // Hash de la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $result = $stmt->execute([$username, $email, $hashedPassword]);
    
    return $result ? $pdo->lastInsertId() : false;
}

// Generar token CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirigir si no está autenticado
function requireAuth() {
    if (!isAuthenticated()) {
        header("Location: login.php");
        exit();
    }
}

// Redirigir si no tiene el rol adecuado
function requireRole($role) {
    requireAuth();
    
    if (!hasRole($role)) {
        header("HTTP/1.1 403 Forbidden");
        echo "No tienes permisos para acceder a esta página.";
        exit();
    }
}

function getUserDevices($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>