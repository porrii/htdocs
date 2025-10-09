<?php
// Archivo principal para manejar rutas de la API
require_once __DIR__ . '/config/cors.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remover el prefijo /backend/ si existe
$requestUri = str_replace('/backend', '', $requestUri);

// Remover query string
$requestUri = strtok($requestUri, '?');

// Enrutamiento básico
$routes = [
    'POST /api/auth/login' => 'api/auth/login.php',
    'POST /api/auth/register' => 'api/auth/register.php',
    'GET /api/auth/verify' => 'api/auth/verify.php',
];

$route = $requestMethod . ' ' . $requestUri;

if (isset($routes[$route])) {
    require_once __DIR__ . '/' . $routes[$route];
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Ruta no encontrada: ' . $route
    ]);
}
?>
