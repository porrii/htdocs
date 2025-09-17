<?php
// Configuración general del sitio
define('SITE_NAME', 'FSA Studio');
define('SITE_LOCATION', 'Calle Principal 123, 28001 Madrid');
define('SITE_NUMBER', '+34 912 345 678');
define('SITE_URL', 'http://localhost/barber2');
define('ADMIN_URL', SITE_URL . '/admin');

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de sesiones
session_start();

// Función para obtener configuración de la base de datos
function getConfig() {
    require_once 'database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM config LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para verificar si el usuario está logueado como admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Función para redirigir
function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
