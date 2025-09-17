<?php
require_once '../../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['category'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    try {
        if ($id) {
            // Update existing product
            $stmt = $db->prepare("UPDATE tools_products SET name = ?, description = ?, type = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$name, $description, $type, $image_url, $id]);
            $_SESSION['message'] = 'Producto actualizado correctamente';
        } else {
            // Create new product
            $stmt = $db->prepare("INSERT INTO tools_products (name, description, type, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $type, $image_url]);
            $_SESSION['message'] = 'Producto agregado correctamente';
        }
        
        header('Location: ../products.php');
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error al guardar el producto: ' . $e->getMessage();
        header('Location: ../products.php');
    }
}
?>
