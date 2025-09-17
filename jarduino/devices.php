<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener dispositivos del usuario con su configuración
$stmt = $pdo->prepare("SELECT d.*, dc.auto_irrigation, dc.threshold, dc.duration 
                       FROM devices d 
                       LEFT JOIN device_config dc ON d.device_id = dc.device_id 
                       WHERE d.user_id = ?");
$stmt->execute([$user_id]);
$devices_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar eliminación de dispositivo
if (isset($_GET['delete'])) {
    $device_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE device_id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    
    if ($stmt->fetch()) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM sensor_data WHERE device_id = ?")->execute([$device_id]);
            $pdo->prepare("DELETE FROM irrigation_log WHERE device_id = ?")->execute([$device_id]);
            $pdo->prepare("DELETE FROM device_config WHERE device_id = ?")->execute([$device_id]);
            $pdo->prepare("DELETE FROM devices WHERE device_id = ?")->execute([$device_id]);
            $pdo->commit();
            $success = "Dispositivo eliminado correctamente";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al eliminar el dispositivo: " . $e->getMessage();
        }
    } else {
        $error = "Dispositivo no encontrado o no tienes permisos";
    }
}

// Procesar edición de dispositivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_device'])) {
    $device_id = $_POST['device_id'];
    $name = $_POST['name'];
    $location = $_POST['location'];
    $soil_threshold = $_POST['soil_threshold'];
    $auto_irrigation = isset($_POST['auto_irrigation']) ? 1 : 0;
    $irrigation_duration = $_POST['irrigation_duration'];

    $stmt = $pdo->prepare("SELECT device_id FROM devices WHERE device_id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);

    if ($stmt->fetch()) {
        // Actualizar información básica del dispositivo
        $stmt = $pdo->prepare("UPDATE devices SET name = ?, location= ? WHERE device_id = ?");
        if ($stmt->execute([$name, $location, $device_id])) {
            // Actualizar o insertar configuración
            $stmt = $pdo->prepare("INSERT INTO device_config (device_id, auto_irrigation, threshold, duration) 
                                  VALUES (?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE 
                                  auto_irrigation = VALUES(auto_irrigation), 
                                  threshold = VALUES(threshold), 
                                  duration = VALUES(duration)");
            if ($stmt->execute([$device_id, $auto_irrigation, $soil_threshold, $irrigation_duration])) {
                $success = "Dispositivo actualizado correctamente";
            } else {
                $error = "Error al actualizar la configuración del dispositivo";
            }
        } else {
            $error = "Error al actualizar el dispositivo";
        }
    } else {
        $error = "Dispositivo no encontrado o no tienes permisos";
    }
}

// Obtener estado en línea de los dispositivos
$online_status = [];
if (!empty($devices_bd)) {
    $device_ids = array_column($devices_bd, 'device_id');
    $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
    
    $stmt = $pdo->prepare("
        SELECT device_id, 
               MAX(created_at) as last_seen,
               TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW()) as minutes_ago,
               CASE WHEN TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW()) <= 5 THEN 1 ELSE 0 END as is_online
        FROM sensor_data 
        WHERE device_id IN ($placeholders) 
        GROUP BY device_id
    ");
    $stmt->execute($device_ids);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $online_status[$row['device_id']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Dispositivos - SmartGarden</title>
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
                <h1 class="h2">Mis Dispositivos</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                        <i class="fas fa-plus me-1"></i>Añadir Dispositivo
                    </button>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row">
                <?php if (empty($devices_bd)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No tienes dispositivos registrados.
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addDeviceModal">Añade tu primer dispositivo</a>.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($devices_bd as $device): 
                        // Valores reales de la BD con fallback seguro
                        $auto_irrigation = isset($device['auto_irrigation']) ? (bool)$device['auto_irrigation'] : false;
                        $soil_threshold = $device['threshold'] ?? 50;
                        $irrigation_duration = $device['duration'] ?? 10;
                        
                        // Estado en línea
                        $is_online = isset($online_status[$device['device_id']]) && 
                                    $online_status[$device['device_id']]['is_online'];
                        $last_seen = isset($online_status[$device['device_id']]) ? 
                                    $online_status[$device['device_id']]['last_seen'] : 'Nunca';
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4" id="device-<?php echo $device['device_id']; ?>">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($device['name']); ?></h5>
                                    <span class="badge bg-<?php echo $is_online ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-circle me-1"></i>
                                        <?php echo $is_online ? 'En línea' : 'Offline'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            ID del dispositivo
                                            <span class="text-muted"><?php echo $device['device_id']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Ubicación
                                            <span class="text-muted"><?php echo $device['location']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Última conexión
                                            <span class="text-muted"><?php echo $last_seen; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Umbral de humedad
                                            <span class="text-muted"><?php echo $soil_threshold; ?>%</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Duración riego
                                            <span class="text-muted"><?php echo $irrigation_duration; ?>s</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Riego automático
                                            <span class="badge bg-<?php echo $auto_irrigation ? 'success' : 'danger'; ?>">
                                                <?php echo $auto_irrigation ? 'Activado' : 'Desactivado'; ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editDeviceModal<?php echo $device['device_id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Editar
                                    </button>
                                    <a href="?delete=<?php echo $device['device_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este dispositivo?')">
                                        <i class="fas fa-trash me-1"></i>Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Editar -->
                        <div class="modal fade" id="editDeviceModal<?php echo $device['device_id']; ?>" tabindex="-1" aria-labelledby="editDeviceModalLabel<?php echo $device['device_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editDeviceModalLabel<?php echo $device['device_id']; ?>">Editar Dispositivo</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" id="editDeviceForm<?php echo $device['device_id']; ?>">
                                        <input type="hidden" name="edit_device" value="1">
                                        <input type="hidden" name="device_id" value="<?php echo $device['device_id']; ?>">
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Nombre</label>
                                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($device['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ubicación</label>
                                                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($device['location']); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Umbral de humedad (%)</label>
                                                <input type="number" class="form-control" id="soil_threshold<?php echo $device['device_id']; ?>" name="soil_threshold" min="5" max="95" value="<?php echo $soil_threshold; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Duración riego automático (segundos)</label>
                                                <input type="number" class="form-control" id="irrigation_duration<?php echo $device['device_id']; ?>" name="irrigation_duration" min="1" max="300" value="<?php echo $irrigation_duration; ?>" required>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="auto_irrigation<?php echo $device['device_id']; ?>" name="auto_irrigation" <?php echo $auto_irrigation ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Riego automático</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Modal Añadir Dispositivo -->
<?php include 'includes/modal_addDevice.php'; ?>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>