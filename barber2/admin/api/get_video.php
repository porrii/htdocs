<?php
require_once '../../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    
    if ($video) {
        header('Content-Type: application/json');
        echo json_encode($video);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Video not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID required']);
}
?>
