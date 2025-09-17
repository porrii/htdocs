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
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $video_url = $_POST['video_url'] ?? '';
    $thumbnail_url = $_POST['thumbnail_url'] ?? '';

    try {
        if ($id) {
            // Update existing video
            $stmt = $db->prepare("UPDATE videos SET title = ?, description = ?, video_url = ?, thumbnail_url = ? WHERE id = ?");
            $stmt->execute([$title, $description, $video_url, $thumbnail_url, $id]);
            $_SESSION['message'] = 'Video actualizado correctamente';
        } else {
            // Create new video
            $stmt = $db->prepare("INSERT INTO videos (title, description, video_url, thumbnail_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $video_url, $thumbnail_url]);
            $_SESSION['message'] = 'Video agregado correctamente';
        }
        
        header('Location: ../videos.php');
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error al guardar el video: ' . $e->getMessage();
        header('Location: ../videos.php');
    }
}
?>
