<?php
/**
 * Script para verificar la configuración de la base de datos
 * Ejecutar: http://localhost/fsa2/scripts/verify-database.php
 */

require_once '../includes/config.php';

echo "<h1>🔍 Verificación de Base de Datos</h1>";

// Verificar conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<p style='color: green;'>✅ Conexión a la base de datos: EXITOSA</p>";
    
    // Verificar tablas
    $tables = ['usuarios', 'horarios', 'citas', 'productos', 'videos'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p style='color: green;'>✅ Tabla '$table': $count registros</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Tabla '$table': ERROR - " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar usuario admin
    try {
        $stmt = $pdo->prepare("SELECT username, email, activo FROM usuarios WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p style='color: green;'>✅ Usuario admin encontrado:</p>";
            echo "<ul>";
            echo "<li>Usuario: " . htmlspecialchars($admin['username']) . "</li>";
            echo "<li>Email: " . htmlspecialchars($admin['email']) . "</li>";
            echo "<li>Activo: " . ($admin['activo'] ? 'Sí' : 'No') . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ Usuario admin NO encontrado</p>";
            echo "<p><a href='create-admin-user.php'>Crear usuario admin</a></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error verificando usuario admin: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    echo "<h3>Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verificar que XAMPP esté ejecutándose</li>";
    echo "<li>Verificar que MySQL esté activo</li>";
    echo "<li>Verificar las credenciales en includes/config.php</li>";
    echo "<li>Crear la base de datos 'peluqueria_db'</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='create-admin-user.php'>Crear/Actualizar Usuario Admin</a></p>";
echo "<p><a href='../admin-login.php'>← Ir al Panel de Administración</a></p>";
echo "<p><a href='../index.php'>← Volver al Sitio Web</a></p>";
?>
