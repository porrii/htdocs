<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Obtener información del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
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
                $_SESSION['username'] = $name;
                $_SESSION['email'] = $email;
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
    }
}

// Procesar preferencias del sistema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    $language = $_POST['language'];
    $timezone = $_POST['timezone'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    
    // Guardar preferencias en la base de datos
    $stmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, language, timezone, notifications, dark_mode) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        language = VALUES(language), 
        timezone = VALUES(timezone), 
        notifications = VALUES(notifications), 
        dark_mode = VALUES(dark_mode)
    ");
    
    if ($stmt->execute([$user_id, $language, $timezone, $notifications, $dark_mode])) {
        $success = "Preferencias guardadas correctamente";
        
        // Actualizar modo oscuro en la sesión
        $_SESSION['dark_mode'] = $dark_mode;
    } else {
        $error = "Error al guardar las preferencias";
    }
}

// Obtener preferencias del usuario
$preferences = [
    'language' => 'es',
    'timezone' => 'Europe/Madrid',
    'notifications' => 1,
    'dark_mode' => 0
];

$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_preferences = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_preferences) {
    $preferences = array_merge($preferences, $user_preferences);
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
    
    <div class="container">
        <div class="row">
            <?php // include 'includes/sidebar.php'; ?>
            
            <main class="px-md-4">
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
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
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
                                <form method="POST">
                                    <input type="hidden" name="update_preferences" value="1">
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Idioma</label>
                                        <select class="form-select" id="language" name="language">
                                            <option value="es" <?php echo $preferences['language'] == 'es' ? 'selected' : ''; ?>>Español</option>
                                            <option value="en" <?php echo $preferences['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">Zona Horaria</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <option value="Europe/Madrid" <?php echo $preferences['timezone'] == 'Europe/Madrid' ? 'selected' : ''; ?>>España (GMT+1)</option>
                                            <option value="UTC" <?php echo $preferences['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo $preferences['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>EST (GMT-5)</option>
                                            <option value="America/Los_Angeles" <?php echo $preferences['timezone'] == 'America/Los_Angeles' ? 'selected' : ''; ?>>PST (GMT-8)</option>
                                        </select>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="notifications" name="notifications" <?php echo $preferences['notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notifications">Notificaciones por email</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" <?php echo $preferences['dark_mode'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="dark_mode">Modo oscuro</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Guardar Preferencias</button>
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
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Estado
                                        <span class="badge bg-success">En línea</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Dispositivos registrados
                                        <span class="text-muted">
                                            <?php
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE user_id = ?");
                                            $stmt->execute([$user_id]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Riegos este mes
                                        <span class="text-muted">
                                            <?php
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) 
                                                FROM irrigation_log il 
                                                JOIN devices d ON il.device_id = d.device_id 
                                                WHERE d.user_id = ? AND MONTH(il.created_at) = MONTH(CURDATE())
                                            ");
                                            $stmt->execute([$user_id]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Acciones Peligrosas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#exportDataModal">
                                        <i class="fas fa-download me-2"></i>Exportar Mis Datos
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-trash me-2"></i>Eliminar Mi Cuenta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Exportar Datos -->
    <div class="modal fade" id="exportDataModal" tabindex="-1" aria-labelledby="exportDataModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportDataModalLabel">Exportar Mis Datos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea exportar todos sus datos? Se generará un archivo con toda la información de sus dispositivos, historial de riego y configuraciones.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="includeSensors">
                        <label class="form-check-label" for="includeSensors">
                            Incluir datos de sensores (puede ser un archivo muy grande)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="exportData('all')">Exportar Datos</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Cuenta -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Eliminar Mi Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Advertencia:</strong> Esta acción no se puede deshacer. Todos sus datos, dispositivos e historial se eliminarán permanentemente.
                    </div>
                    <p>Para confirmar que desea eliminar su cuenta, escriba "ELIMINAR" en el siguiente campo:</p>
                    <input type="text" class="form-control" id="deleteConfirmation" placeholder="ELIMINAR">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Eliminar Cuenta Permanentemente</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validación para eliminar cuenta
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        const confirmDelete = document.getElementById('confirmDelete');
        
        deleteConfirmation.addEventListener('input', function() {
            confirmDelete.disabled = this.value !== 'ELIMINAR';
        });
        
        confirmDelete.addEventListener('click', function() {
            if (confirm('¿Está absolutamente seguro? Esta acción no se puede deshacer.')) {
                fetch('../api/delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cuenta eliminada correctamente. Será redirigido al inicio.');
                        window.location.href = '../index.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error de conexión: ' + error);
                });
            }
        });
    });
    </script>
</body>
</html>