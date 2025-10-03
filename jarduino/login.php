<?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/auth.php';

    // Si ya está autenticado, redirigir al dashboard
    if (isAuthenticated()) {
        header("Location: index.php");
        exit();
    }

    $error = '';
    $success = '';

    // Procesar inicio de sesión
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = 'Por favor, complete todos los campos.';
        } else {
            $user = verifyCredentials($username, $password);
            
            if ($user) {
                // Iniciar sesión
                $_SESSION['user_id'] = $user['u_user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['dark_mode'] = $user['dark_mode'];
                $_SESSION['timezone'] = $user['timezone'];
                $_SESSION['language'] = $user['language'];
                
                // Redirigir al dashboard
                header("Location: index.php");
                exit();
            } else {
                $error = 'Credenciales incorrectas.';
            }
        }
    }

    // Procesar registro
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
        $username = trim($_POST['new_username']);
        $email = trim($_POST['email']);
        $password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Por favor, complete todos los campos.';
        } elseif ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $result = registerUser($username, $email, $password);
            
            if ($result) {
                $success = 'Cuenta creada exitosamente. Ahora puede iniciar sesión.';
            } else {
                $error = 'El nombre de usuario o email ya están en uso.';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartGarden - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
        }
        .login-left {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right {
            padding: 40px;
        }
        .form-toggle {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .form-toggle button {
            flex: 1;
            border: none;
            background: none;
            padding: 10px;
            cursor: pointer;
            font-weight: 500;
        }
        .form-toggle button.active {
            border-bottom: 2px solid #4e73df;
            color: #4e73df;
        }
        .form-container {
            display: none;
        }
        .form-container.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row no-gutters">
                <div class="col-md-5 login-left">
                    <h2 class="mb-4">Bienvenido a SmartGarden</h2>
                    <p>Sistema inteligente de monitoreo y control de riego para tus plantas.</p>
                    <ul class="mt-4">
                        <li><i class="fas fa-check-circle me-2"></i>Monitoreo en tiempo real</li>
                        <li><i class="fas fa-check-circle me-2"></i>Control de riego automático</li>
                        <li><i class="fas fa-check-circle me-2"></i>Programación flexible</li>
                        <li><i class="fas fa-check-circle me-2"></i>Alertas inteligentes</li>
                    </ul>
                </div>
                <div class="col-md-7 login-right">
                    <div class="form-toggle">
                        <button id="loginToggle" class="active">Iniciar Sesión</button>
                        <button id="registerToggle">Registrarse</button>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div id="loginForm" class="form-container active">
                        <form method="POST" action="">
                            <input type="hidden" name="login" value="1">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario o Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
                        </div>
                    </div>
                    
                    <div id="registerForm" class="form-container">
                        <form method="POST" action="">
                            <input type="hidden" name="register" value="1">
                            <div class="mb-3">
                                <label for="new_username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="new_username" name="new_username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Registrarse</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginToggle = document.getElementById('loginToggle');
            const registerToggle = document.getElementById('registerToggle');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            loginToggle.addEventListener('click', function() {
                loginToggle.classList.add('active');
                registerToggle.classList.remove('active');
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
            });
            
            registerToggle.addEventListener('click', function() {
                registerToggle.classList.add('active');
                loginToggle.classList.remove('active');
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
            });
        });
    </script>
</body>
</html>