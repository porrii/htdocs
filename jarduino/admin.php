<?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/auth.php';

    // Verificar autenticación y permisos de administrador
    if (!isAuthenticated() || !hasRole('admin')) {
        header("Location: index.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Obtener estadísticas del sistema
    $stats = [];
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM devices) as total_devices,
            (SELECT COUNT(*) FROM irrigation_log WHERE DATE(created_at) = CURDATE()) as today_irrigations,
            (SELECT COUNT(*) FROM alerts WHERE resolved = 0) as pending_alerts
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener usuarios
    $stmt = $pdo->prepare("SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener dispositivos
    $stmt = $pdo->prepare("
        SELECT d.*, u.username as owner 
        FROM devices d 
        JOIN users u ON d.user_id = u.user_id 
        ORDER BY d.created_at DESC
    ");
    $stmt->execute();
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar acciones de administración
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_user_role'])) {
            $target_user_id = $_POST['user_id'];
            $new_role = $_POST['role'];
            
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->execute([$new_role, $target_user_id]);
            
            header("Location: admin.php?message=role_updated");
            exit();
        }
        
        if (isset($_POST['delete_user'])) {
            $target_user_id = $_POST['user_id'];
            
            // No permitir auto-eliminación
            if ($target_user_id != $user_id) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$target_user_id]);
                
                header("Location: admin.php?message=user_deleted");
                exit();
            }
        }
        
        if (isset($_POST['update_device_owner'])) {
            $device_id = $_POST['device_id'];
            $new_owner_id = $_POST['user_id'];
            
            $stmt = $pdo->prepare("UPDATE devices SET user_id = ? WHERE device_id = ?");
            $stmt->execute([$new_owner_id, $device_id]);
            
            header("Location: admin.php?message=device_updated");
            exit();
        }
        
        if (isset($_POST['delete_device'])) {
            $device_id = $_POST['device_id'];
            
            $stmt = $pdo->prepare("DELETE FROM devices WHERE device_id = ?");
            $stmt->execute([$device_id]);
            
            header("Location: admin.php?message=device_deleted");
            exit();
        }
    }

    // Mensajes de confirmación
    $message = '';
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'role_updated':
                $message = 'Rol de usuario actualizado correctamente.';
                break;
            case 'user_deleted':
                $message = 'Usuario eliminado correctamente.';
                break;
            case 'device_updated':
                $message = 'Propietario del dispositivo actualizado correctamente.';
                break;
            case 'device_deleted':
                $message = 'Dispositivo eliminado correctamente.';
                break;
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartGarden - Panel de Administración</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="row">
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Panel de Administración</h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Estadísticas del Sistema -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Usuarios Totales</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Dispositivos Totales</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_devices']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-microchip fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Riegos Hoy</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['today_irrigations']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tint fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Alertas Pendientes</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_alerts']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bell fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestión de Usuarios -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gestión de Usuarios</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <select name="role" class="form-select form-select-sm d-inline" style="width: auto;">
                                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Usuario</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                                </select>
                                                <button type="submit" name="update_user_role" class="btn btn-sm btn-primary ms-1">Actualizar</button>
                                            </form>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['user_id'] != $user_id): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este usuario?');">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-muted">Usuario actual</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Gestión de Dispositivos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gestión de Dispositivos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Propietario</th>
                                        <th>Ubicación</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $device): ?>
                                    <tr>
                                        <td><?php echo $device['device_id']; ?></td>
                                        <td><?php echo htmlspecialchars($device['name']); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="device_id" value="<?php echo $device['device_id']; ?>">
                                                <select name="user_id" class="form-select form-select-sm d-inline" style="width: auto;">
                                                    <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['user_id']; ?>" <?php echo $device['user_id'] == $user['user_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['username']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="update_device_owner" class="btn btn-sm btn-primary ms-1">Actualizar</button>
                                            </form>
                                        </td>
                                        <td><?php echo htmlspecialchars($device['location'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($device['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="device_id" value="<?php echo $device['device_id']; ?>">
                                                <button type="submit" name="delete_device" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este dispositivo?');">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>