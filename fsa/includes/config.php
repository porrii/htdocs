<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'peluqueria_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Vacío por defecto en XAMPP

// Site configuration
define('SITE_NAME', 'FSA Studio');
define('SITE_URL', 'http://localhost/fsa');
define('ADMIN_EMAIL', 'admin@fsastudio.com');

// Email configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu-email@gmail.com'); // Cambiar por tu email
define('SMTP_PASS', 'tu-password-app'); // Cambiar por tu contraseña de aplicación
define('SMTP_FROM', 'tu-email@gmail.com'); // Cambiar por tu email
define('SMTP_NAME', 'FSA Studio');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Appointment settings
define('TIME_SLOT_DURATION', 30); // minutes
define('MAX_ADVANCE_BOOKING', 90); // days
define('MIN_ADVANCE_BOOKING', 60); // minutes

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_PATH', 'uploads/');

// Timezone
date_default_timezone_set('Europe/Madrid');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
