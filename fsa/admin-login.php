<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Check if already logged in
if (isLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        $userId = verificarCredencialesAdmin($username, $password);

        if ($userId) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $userId;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_last_activity'] = time();

            header('Location: admin/index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}

// Check for session expired message
if (isset($_GET['expired'])) {
    $error = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrador - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin-styles.css">
    
    <style>
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: var(--bg-secondary);
            border-radius: var(--radius-2xl);
            padding: 3rem;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .login-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary-gradient);
            border: none;
            color: white;
            border-radius: var(--radius-lg);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <!-- Login -->
    <div class="login-container fade-in-up">
        <div class="login-header">
            <h1 class="login-title">
                <i class="fas fa-user-shield me-2"></i>
                Admin Panel
            </h1>
            <p class="login-subtitle">Acceso para administradores</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert-modern alert-error">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" id="username" name="username" class="form-control-modern" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control-modern" required>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Iniciar Sesión
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left me-1"></i>
                Volver al sitio web
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
