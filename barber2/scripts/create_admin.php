<?php
try {
    require_once '../config/database.php';

    // Conexión con PDO
    $database = new Database();
    $pdo = $database->getConnection();

    // Datos del usuario admin
    $username = "admin";
    $password = "admin123";
    $email = "admin@example.com";

    // Hashear la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Preparar e insertar
    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, email, active) 
                           VALUES (:username, :password_hash, :email, 1)");

    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email
    ]);

    echo "Usuario admin creado correctamente.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
