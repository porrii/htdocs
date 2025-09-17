<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM services WHERE active = 1 ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($services);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar servicios']);
}
?>
