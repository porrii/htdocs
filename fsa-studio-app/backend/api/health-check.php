<?php
// Health check endpoint para verificar conexión
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar conexión a la base de datos
try {
    require_once '../config/database.php';
    $db = Database::getInstance()->getConnection();
    
    // Hacer una consulta simple para verificar la conexión
    $stmt = $db->query("SELECT 1");
    
    echo json_encode([
        'success' => true,
        'status' => 'healthy',
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'status' => 'unhealthy',
        'error' => 'Database connection failed'
    ]);
}
?>
