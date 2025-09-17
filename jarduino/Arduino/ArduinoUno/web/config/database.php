<?php
$host = 'localhost';
$dbname = 'smartgarden_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Crear tablas si no existen
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        device_id VARCHAR(16) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        auto_irrigation BOOLEAN DEFAULT false,
        soil_threshold INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS sensor_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(16),
        temperature DECIMAL(4,1),
        air_humidity DECIMAL(4,1),
        soil_moisture INT,
        pump_status BOOLEAN,
        online_status BOOLEAN,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (device_id, created_at)
    )",
    
    "CREATE TABLE IF NOT EXISTS irrigation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(16),
        duration INT,
        mode ENUM('manual', 'auto', 'micro'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (device_id, created_at)
    )"
];

foreach ($tables as $table) {
    try {
        $pdo->exec($table);
    } catch (PDOException $e) {
        // Ignorar errores de tabla ya existente
    }
}
?>