<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $dayOfWeek = $input['day_of_week'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT is_working_day FROM work_schedules WHERE day_of_week = :day_of_week";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':day_of_week', $dayOfWeek);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'is_working_day' => $result ? (bool)$result['is_working_day'] : false
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al verificar día laboral']);
}
?>
