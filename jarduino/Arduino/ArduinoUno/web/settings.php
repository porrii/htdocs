<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Obtener información del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $error = '';
    $success = '';
    
    // Validar campos obligatorios
    if (empty($name) || empty($email)) {
        $error = "Nombre y email son obligatorios";
    } else {
        // Verificar si el email ya existe (excepto para el usuario actual)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error = "El email ya está en uso por otro usuario";
        } else {
            // Actualizar información básica
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $user_id])) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = "Perfil actualizado correctamente";
            } else {
                $error = "Error al actualizar el perfil";
            }
            
            // Actualizar contraseña si se proporcionó
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = "Las nuevas contraseñas no coinciden";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = "La contraseña actual es incorrecta";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $success = "Perfil y contraseña actualizados correctamente";
                    } else {
                        $error = "Error al actualizar la contraseña";
                    }
                }
            }
        }
    // header("Location: settings.php");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - SmartGarden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración</h1>
                </div>

                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Perfil de Usuario</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <hr>
                                    <h6>Cambiar Contraseña</h6>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">Guardar Cambios</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Preferencias del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Idioma</label>
                                        <select class="form-select" id="language">
                                            <option value="es" selected>Español</option>
                                            <option value="en">English</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">Zona Horaria</label>
                                        <select class="form-select" id="timezone">
                                            <option value="Europe/Madrid" selected>España (GMT+1)</option>
                                            <option value="UTC">UTC</option>
                                            <option value="America/New_York">EST (GMT-5)</option>
                                            <option value="America/Los_Angeles">PST (GMT-8)</option>
                                        </select>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="notifications" checked>
                                        <label class="form-check-label" for="notifications">Notificaciones por email</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="dark_mode">
                                        <label class="form-check-label" for="dark_mode">Modo oscuro</label>
                                    </div>
                                    <button type="button" class="btn btn-secondary">Guardar Preferencias</button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Versión
                                        <span class="text-muted">v2.0.1</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Última actualización
                                        <span class="text-muted"><?php echo date('d/m/Y'); ?></span>
                                    </li>
                                    <!-- <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Servidor MQTT
                                        <span class="text-muted">54.163.155.226:1883</span>
                                    </li> -->
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Estado
                                        <span class="badge bg-success">En línea</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>