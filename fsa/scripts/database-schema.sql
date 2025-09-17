-- Crear base de datos
CREATE DATABASE IF NOT EXISTS peluqueria_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE peluqueria_db;

-- Tabla de usuarios administradores
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de horarios de negocio
CREATE TABLE horarios (
    id_dia INT PRIMARY KEY,
    dia VARCHAR(20) NOT NULL,
    hora_apertura TIME NULL,
    hora_cierre TIME NULL,
    cerrado BOOLEAN DEFAULT FALSE,
    horario_partido BOOLEAN DEFAULT FALSE,
    hora_apertura_tarde TIME NULL,
    hora_cierre_tarde TIME NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de servicios - NUEVA
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    duracion INT DEFAULT 30 COMMENT 'Duración en minutos',
    precio DECIMAL(10,2),
    activo BOOLEAN DEFAULT TRUE,
    icono TEXT,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_orden (orden)
);

-- Tabla de categorías de videos - NUEVA
CREATE TABLE categorias_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_orden (orden)
);

-- Tabla de citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    servicio VARCHAR(100) NOT NULL,
    estado ENUM('confirmada', 'cancelada', 'completada') DEFAULT 'confirmada',
    notas TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    recordatorio_enviado TINYINT(1) DEFAULT 0,
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_email (email)
);

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo)
);

-- Tabla de videos - ACTUALIZADA
CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    youtube_id VARCHAR(50) NOT NULL,
    categoria_id INT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_categoria (categoria_id),
    FOREIGN KEY (categoria_id) REFERENCES categorias_videos(id) ON DELETE SET NULL
);

-- Insertar horarios por defecto
INSERT INTO horarios (id_dia, dia, hora_apertura, hora_cierre, cerrado) VALUES
(1, 'Lunes', '09:00:00', '18:00:00', FALSE),
(2, 'Martes', '09:00:00', '18:00:00', FALSE),
(3, 'Miércoles', '09:00:00', '18:00:00', FALSE),
(4, 'Jueves', '09:00:00', '18:00:00', FALSE),
(5, 'Viernes', '09:00:00', '18:00:00', FALSE),
(6, 'Sábado', '09:00:00', '14:00:00', FALSE),
(7, 'Domingo', NULL, NULL, TRUE);

-- Insertar usuario administrador por defecto
-- Usuario: admin, Contraseña: admin123
INSERT INTO usuarios (username, password, email, nombre) VALUES
('admin', '$2y$10$7KIwP8zKtOeH0nQa9mGdHOYX8oIhove7rJ5Ej6qJ5kJ5kJ5kJ5kJ5O', 'admin@peluqueria.com', 'Administrador');

-- Insertar servicios por defecto
INSERT INTO servicios (nombre, descripcion, duracion, precio, orden) VALUES
('Corte de Cabello', 'Corte profesional adaptado a tu estilo', 30, 25.00, 1),
('Corte y Peinado', 'Corte completo con peinado incluido', 45, 35.00, 2),
('Coloración', 'Coloración completa del cabello', 90, 65.00, 3),
('Mechas', 'Mechas profesionales para iluminar tu cabello', 120, 85.00, 4),
('Tratamiento Capilar', 'Tratamiento nutritivo y reparador', 60, 45.00, 5),
('Peinado Especial', 'Peinado para eventos y ocasiones especiales', 60, 40.00, 6),
('Asesoría de Imagen', 'Consulta personalizada de estilo', 45, 30.00, 7);

-- Insertar categorías de videos por defecto
INSERT INTO categorias_videos (nombre, descripcion, orden) VALUES
('Cortes', 'Videos de técnicas de corte', 1),
('Coloración', 'Videos de coloración y tintes', 2),
('Peinados', 'Videos de peinados y estilos', 3),
('Tratamientos', 'Videos de tratamientos capilares', 4),
('Tutoriales', 'Tutoriales paso a paso', 5);

-- Insertar productos de ejemplo
INSERT INTO productos (nombre, descripcion, precio, imagen) VALUES
('Champú Profesional', 'Champú hidratante para todo tipo de cabello', 25.99, NULL),
('Acondicionador Nutritivo', 'Acondicionador que nutre y fortalece el cabello', 22.50, NULL),
('Mascarilla Reparadora', 'Tratamiento intensivo para cabello dañado', 35.00, NULL),
('Serum Anti-Frizz', 'Serum que controla el encrespamiento y aporta brillo', 28.75, NULL),
('Spray Protector Térmico', 'Protege el cabello del calor de herramientas de peinado', 19.99, NULL),
('Aceite Capilar Nutritivo', 'Aceite natural que nutre y repara el cabello', 32.00, NULL);

-- Insertar videos de ejemplo con categorías
INSERT INTO videos (titulo, descripcion, youtube_id, categoria_id) VALUES
('Técnica de Corte Moderno', 'Aprende las últimas técnicas de corte para un look moderno', 'dQw4w9WgXcQ', 1),
('Coloración Profesional', 'Tutorial paso a paso para una coloración perfecta', 'dQw4w9WgXcQ', 2),
('Peinados para Eventos', 'Crea peinados elegantes para ocasiones especiales', 'dQw4w9WgXcQ', 3),
('Tratamientos Capilares', 'Conoce los mejores tratamientos para cada tipo de cabello', 'dQw4w9WgXcQ', 4);
