<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();

// Handle form submissions
$mensaje = '';
$tipo_mensaje = '';

if ($_POST) {
    if (isset($_POST['crear_usuario'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $nombre = sanitizeInput($_POST['nombre']);
        $password = $_POST['password'];
        
        // Check if username already exists
        $existeUsuario = $db->fetchOne("SELECT id FROM usuarios WHERE username = ?", [$username]);
        
        if ($existeUsuario) {
            $mensaje = 'El nombre de usuario ya existe.';
            $tipo_mensaje = 'error';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            if ($db->insert("INSERT INTO usuarios (username, password, email, nombre, activo, fecha_creacion) VALUES (?, ?, ?, ?, 1, NOW())", 
                           [$username, $password_hash, $email, $nombre])) {
                $mensaje = 'Usuario creado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al crear el usuario.';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['editar_usuario'])) {
        $id = intval($_POST['id']);
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $nombre = sanitizeInput($_POST['nombre']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Check if username already exists (excluding current user)
        $existeUsuario = $db->fetchOne("SELECT id FROM usuarios WHERE username = ? AND id != ?", [$username, $id]);
        
        if ($existeUsuario) {
            $mensaje = 'El nombre de usuario ya existe.';
            $tipo_mensaje = 'error';
        } else {
            if ($db->update("UPDATE usuarios SET username = ?, email = ?, nombre = ?, activo = ?, fecha_actualizacion = NOW() WHERE id = ?", 
                           [$username, $email, $nombre, $activo, $id])) {
                $mensaje = 'Usuario actualizado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar el usuario.';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['cambiar_password'])) {
        $id = intval($_POST['id']);
        $password_actual = $_POST['password_actual'];
        $password_nueva = $_POST['password_nueva'];
        
        if (cambiarPasswordAdmin($id, $password_actual, $password_nueva)) {
            $mensaje = 'Contraseña cambiada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al cambiar la contraseña. Verifica que la contraseña actual sea correcta.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['eliminar_usuario'])) {
        $id = intval($_POST['id']);
        
        // Don't allow deleting the current user
        if ($id == $_SESSION['admin_user_id']) {
            $mensaje = 'No puedes eliminar tu propio usuario.';
            $tipo_mensaje = 'error';
        } else {
            if ($db->update("UPDATE usuarios SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?", [$id])) {
                $mensaje = 'Usuario desactivado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al desactivar el usuario.';
                $tipo_mensaje = 'error';
            }
        }
    }
}

// Get all users
$usuarios = $db->fetchAll("SELECT * FROM usuarios ORDER BY fecha_creacion DESC");

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">

    <style>
        .data-card.fade-in-up {
            height: auto !important;         /* Evita que se estire verticalmente */
            min-height: unset !important;    /* Elimina mínimos forzados */
            overflow: visible;               /* Asegura que no se recorte el contenido */
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
            <!-- Header -->
            <div class="dashboard-header fade-in-up">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="dashboard-title">
                        <i class="fas fa-users"></i>
                        Gestión de Usuarios
                    </h1>
                    <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i>
                        Nuevo Usuario
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($mensaje): ?>
            <div class="alert-modern alert-<?php echo $tipo_mensaje; ?> fade-in-up">
                <div class="alert-icon">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                </div>
                <div><?php echo $mensaje; ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Users Table -->
            <div class="data-card fade-in-up">
                <div class="data-header">
                    <h3 class="data-title">
                        <i class="fas fa-list"></i>
                        Usuarios del Sistema (<?php echo count($usuarios); ?>)
                    </h3>
                </div>
                <div class="data-body">
                    <?php if (!empty($usuarios)): ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Información</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($usuario['username']); ?></div>
                                                <small class="text-muted">ID: <?php echo $usuario['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $usuario['activo'] ? 'confirmada' : 'cancelada'; ?>">
                                            <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ultimo_acceso']): ?>
                                            <div><?php echo date('d/m/Y', strtotime($usuario['ultimo_acceso'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($usuario['ultimo_acceso'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                    onclick="changePassword(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($usuario['id'] != $_SESSION['admin_user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteUser(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No hay usuarios</h3>
                        <p>No se encontraron usuarios en el sistema.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="username" class="form-label">Nombre de Usuario *</label>
                        <input type="text" class="form-control-modern" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control-modern" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control-modern" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control-modern" id="password" name="password" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_usuario" class="btn-modern">
                        <i class="fas fa-plus"></i>
                        Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="edit_username" class="form-label">Nombre de Usuario *</label>
                        <input type="text" class="form-control-modern" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control-modern" id="edit_nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control-modern" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="edit_activo" name="activo">
                        <label class="form-check-label" for="edit_activo">Usuario activo</label>
                    </div>
                    
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_usuario" class="btn-modern">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="changePasswordForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="password_actual" class="form-label">Contraseña Actual *</label>
                        <input type="password" class="form-control-modern" id="password_actual" name="password_actual" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="password_nueva" class="form-label">Nueva Contraseña *</label>
                        <input type="password" class="form-control-modern" id="password_nueva" name="password_nueva" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña *</label>
                        <input type="password" class="form-control-modern" id="password_confirmar" required>
                    </div>
                    
                    <input type="hidden" name="id" id="change_password_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="cambiar_password" class="btn-modern">
                        <i class="fas fa-key"></i>
                        Cambiar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // User data for editing
    const usuarios = <?php echo json_encode($usuarios); ?>;
    
    function editUser(id) {
        const usuario = usuarios.find(u => u.id == id);
        if (usuario) {
            document.getElementById('edit_id').value = usuario.id;
            document.getElementById('edit_username').value = usuario.username;
            document.getElementById('edit_nombre').value = usuario.nombre;
            document.getElementById('edit_email').value = usuario.email;
            document.getElementById('edit_activo').checked = usuario.activo == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
    }
    
    function changePassword(id) {
        document.getElementById('change_password_id').value = id;
        
        const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
        modal.show();
    }
    
    function deleteUser(id) {
        const usuario = usuarios.find(u => u.id == id);
        if (usuario && confirm(`¿Estás seguro de que quieres desactivar al usuario "${usuario.username}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="eliminar_usuario" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Password confirmation validation
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        const nueva = document.getElementById('password_nueva').value;
        const confirmar = document.getElementById('password_confirmar').value;
        
        if (nueva !== confirmar) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return false;
        }
        
        if (nueva.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres');
            return false;
        }
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-modern');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>
</body>
</html>
