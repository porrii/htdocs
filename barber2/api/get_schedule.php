<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM work_schedules ORDER BY day_of_week";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($schedule);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar horarios']);
}
?>
