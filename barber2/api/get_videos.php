<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM videos WHERE active = 1 ORDER BY order_position, id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($videos);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar videos']);
}
?>
