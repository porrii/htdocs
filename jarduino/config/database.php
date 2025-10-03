<?php
// Configuración de la base de datos

// Docker
// $host = 'mysql';
// $dbname = 'smartgarden_db';
// $username = 'root';
// $password = 'rootpassword';

// XAMPP
$host = 'localhost';
$dbname = 'smartgarden_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Crear tablas si no existen
$sql = "
    -- Tabla de usuarios
    CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Tabla de preferencias de usuario
    CREATE TABLE IF NOT EXISTS user_preferences (
        preference_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        language VARCHAR(10) DEFAULT 'es',
        timezone VARCHAR(50) DEFAULT 'Europe/Madrid',
        notifications TINYINT(1) DEFAULT 1,
        dark_mode TINYINT(1) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_preferences_user 
            FOREIGN KEY (user_id) REFERENCES users(user_id) 
            ON DELETE CASCADE ON UPDATE CASCADE,
        UNIQUE KEY uq_user_preferences_user (user_id)
    );

    -- Tabla de dispositivos
    CREATE TABLE IF NOT EXISTS devices (
        device_id VARCHAR(20) PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(255),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );

    -- Tabla de datos de sensores
    CREATE TABLE IF NOT EXISTS sensor_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(20) NOT NULL,
        temperature DECIMAL(5, 2),
        air_humidity DECIMAL(5, 2),
        soil_moisture DECIMAL(5, 2),
        pump_status BOOLEAN DEFAULT FALSE,
        online_status BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    );

    -- Tabla de configuración de dispositivos
    CREATE TABLE IF NOT EXISTS device_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(20) NOT NULL UNIQUE,
        auto_irrigation BOOLEAN DEFAULT FALSE,
        threshold DECIMAL(5, 2) DEFAULT 50.0,
        duration INT DEFAULT 10,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    );

    -- Tabla de registros de riego
    CREATE TABLE IF NOT EXISTS irrigation_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(20) NOT NULL,
        duration INT NOT NULL,
        mode ENUM('manual', 'auto', 'schedule') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    );

    -- Tabla de programaciones de riego
    CREATE TABLE IF NOT EXISTS irrigation_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(20) NOT NULL,
        start_time TIME NOT NULL,
        duration INT NOT NULL,
        days_of_week VARCHAR(20) NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    );

    -- Tabla de alertas
    CREATE TABLE IF NOT EXISTS alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        resolved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    );

    -- Tabla de reportes
    CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id VARCHAR(20) NOT NULL,
        type VARCHAR(50) NOT NULL,
        period VARCHAR(20) NOT NULL,
        data TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    );

    -- Tabla de backups
    CREATE TABLE IF NOT EXISTS backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        size INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );
";

// Ejecutar las consultas
$statements = explode(';', $sql);
foreach ($statements as $statement) {
    if (trim($statement) != '') {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Ignorar errores de tablas ya existentes
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("Error creando tablas: " . $e->getMessage());
            }
        }
    }
}
?>