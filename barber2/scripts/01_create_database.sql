-- Crear base de datos para la barbería
CREATE DATABASE IF NOT EXISTS barbershop_db;
USE barbershop_db;

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    smtp_host VARCHAR(255),
    smtp_port INT DEFAULT 587,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    smtp_encryption VARCHAR(10) DEFAULT 'tls',
    appointment_interval INT DEFAULT 60, -- minutos
    booking_restriction INT DEFAULT 30, -- minutos antes de la cita
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de servicios
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- duración en minutos
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de herramientas/productos
CREATE TABLE IF NOT EXISTS tools_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('tool', 'product') NOT NULL,
    brand VARCHAR(255),
    image_url VARCHAR(500),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de videos
CREATE TABLE IF NOT EXISTS videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500),
    active BOOLEAN DEFAULT TRUE,
    order_position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de horarios de trabajo
CREATE TABLE IF NOT EXISTS work_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day_of_week INT NOT NULL, -- 0=Domingo, 1=Lunes, ..., 6=Sábado
    is_working_day BOOLEAN DEFAULT TRUE,
    morning_start TIME,
    morning_end TIME,
    afternoon_start TIME,
    afternoon_end TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_day (day_of_week)
);

-- Tabla de citas
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(255) NOT NULL,
    client_email VARCHAR(255) NOT NULL,
    client_phone VARCHAR(20),
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id),
    UNIQUE KEY unique_appointment (appointment_date, appointment_time)
);

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
