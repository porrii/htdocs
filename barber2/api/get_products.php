<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM tools_products WHERE active = 1 ORDER BY type, name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar productos']);
}
?>
