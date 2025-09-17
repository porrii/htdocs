<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Start session
session_start();

// Generate correct password hash for "admin123"
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Crear Usuario Administrador</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .credentials { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>";

echo "<h1>🔧 Crear/Actualizar Usuario Administrador</h1>";

try {
    // Check if admin user exists
    $existingUser = $db->fetchOne("SELECT * FROM usuarios WHERE username = 'admin'");
    
    if ($existingUser) {
        // Update existing user
        $result = $db->update(
            "UPDATE usuarios SET password = ?, activo = 1, fecha_actualizacion = NOW() WHERE username = 'admin'",
            [$password_hash]
        );
        
        if ($result) {
            echo "<div class='success'>✅ Usuario administrador actualizado correctamente</div>";
        } else {
            echo "<div class='error'>❌ Error al actualizar el usuario administrador</div>";
        }
    } else {
        // Create new admin user
        $result = $db->insert(
            "INSERT INTO usuarios (username, password, email, nombre, activo, fecha_creacion) VALUES (?, ?, ?, ?, 1, NOW())",
            ['admin', $password_hash, 'admin@peluqueria.com', 'Administrador']
        );
        
        if ($result) {
            echo "<div class='success'>✅ Usuario administrador creado correctamente</div>";
        } else {
            echo "<div class='error'>❌ Error al crear el usuario administrador</div>";
        }
    }
    
    // Verify the password works
    $testUser = $db->fetchOne("SELECT * FROM usuarios WHERE username = 'admin'");
    
    if ($testUser && password_verify($password, $testUser['password'])) {
        echo "<div class='success'>✅ Verificación de contraseña exitosa</div>";
        
        echo "<div class='credentials'>
            <h3>🔑 Credenciales de Acceso:</h3>
            <p><strong>Usuario:</strong> admin</p>
            <p><strong>Contraseña:</strong> admin123</p>
            <p><strong>URL de acceso:</strong> <a href='../admin-login.php'>admin-login.php</a></p>
        </div>";
        
    } else {
        echo "<div class='error'>❌ Error en la verificación de contraseña</div>";
    }
    
    // Show user details
    if ($testUser) {
        echo "<div class='info'>
            <h3>📋 Detalles del Usuario:</h3>
            <p><strong>ID:</strong> {$testUser['id']}</p>
            <p><strong>Username:</strong> {$testUser['username']}</p>
            <p><strong>Email:</strong> {$testUser['email']}</p>
            <p><strong>Nombre:</strong> {$testUser['nombre']}</p>
            <p><strong>Activo:</strong> " . ($testUser['activo'] ? 'Sí' : 'No') . "</p>
            <p><strong>Último acceso:</strong> " . ($testUser['ultimo_acceso'] ?: 'Nunca') . "</p>
        </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<div style='margin-top: 30px;'>
    <a href='../admin-login.php' class='btn'>🚀 Ir al Panel de Admin</a>
    <a href='verify-database.php' class='btn'>🔍 Verificar Base de Datos</a>
    <a href='../index.php' class='btn'>🏠 Ir al Sitio Web</a>
</div>";

echo "</body></html>";
?>
