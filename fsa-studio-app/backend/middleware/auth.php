<?php
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/response.php';

class AuthMiddleware {
    public static function authenticate() {
        $token = JWT::getBearerToken();
        
        if (!$token) {
            Response::unauthorized("Token no proporcionado");
        }

        $decoded = JWT::decode($token);
        
        if (!$decoded) {
            Response::unauthorized("Token inválido o expirado");
        }

        return $decoded;
    }

    public static function requireAdmin() {
        $user = self::authenticate();
        
        if ($user['rol'] !== 'admin') {
            Response::error("Acceso denegado. Se requieren permisos de administrador", 403);
        }

        return $user;
    }
}
?>
