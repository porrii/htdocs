<?php
require_once '../../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../../config/database.php';

// Get dashboard statistics
$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM tools_products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        header('Content-Type: application/json');
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID required']);
}
?>
