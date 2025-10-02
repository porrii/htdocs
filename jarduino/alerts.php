<?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/auth.php';

    // Verificar autenticación
    if (!isAuthenticated()) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $device_id = isset($_GET['device']) ? $_GET['device'] : null;

    // Obtener alertas
    $where_conditions = ["d.user_id = ?"];
    $params = [$user_id];

    if ($device_id) {
        $where_conditions[] = "a.device_id = ?";
        $params[] = $device_id;
    }

    $where_clause = implode(" AND ", $where_conditions);

    $stmt = $pdo->prepare("
        SELECT a.*, d.name as device_name 
        FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE $where_clause 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute($params);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener dispositivos para el filtro
    $stmt = $pdo->prepare("SELECT device_id, name FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar resolución de alertas
    if (isset($_POST['resolve_alert'])) {
        $alert_id = $_POST['alert_id'];
        
        // Verificar que la alerta pertenece al usuario
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM alerts a 
            JOIN devices d ON a.device_id = d.device_id 
            WHERE a.id = ? AND d.user_id = ?
        ");
        $stmt->execute([$alert_id, $user_id]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE alerts SET resolved = 1 WHERE id = ?");
            $stmt->execute([$alert_id]);
            
            header("Location: alerts.php?" . http_build_query($_GET));
            exit();
        }
    }

    // Procesar eliminación de alertas
    if (isset($_POST['delete_alert'])) {
        $alert_id = $_POST['alert_id'];
        
        // Verificar que la alerta pertenece al usuario
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM alerts a 
            JOIN devices d ON a.device_id = d.device_id 
            WHERE a.id = ? AND d.user_id = ?
        ");
        $stmt->execute([$alert_id, $user_id]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM alerts WHERE id = ?");
            $stmt->execute([$alert_id]);
            
            header("Location: alerts.php?" . http_build_query($_GET));
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartGarden - Gestión de Alertas</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="row">            
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Alertas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearAlertsModal">
                                <i class="fas fa-trash me-1"></i>Limpiar Alertas Resueltas
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="device" class="form-label">Filtrar por Dispositivo</label>
                                <select class="form-select" id="device" name="device">
                                    <option value="">Todos los dispositivos</option>
                                    <?php foreach ($devices as $device): ?>
                                    <option value="<?php echo $device['device_id']; ?>" <?php echo $device_id == $device['device_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($device['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Filtrar por Estado</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos los estados</option>
                                    <option value="resolved" <?php echo isset($_GET['status']) && $_GET['status'] == 'resolved' ? 'selected' : ''; ?>>Resueltas</option>
                                    <option value="unresolved" <?php echo isset($_GET['status']) && $_GET['status'] == 'unresolved' ? 'selected' : ''; ?>>No Resueltas</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="priority" class="form-label">Filtrar por Prioridad</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="">Todas las prioridades</option>
                                    <option value="high" <?php echo isset($_GET['priority']) && $_GET['priority'] == 'high' ? 'selected' : ''; ?>>Alta</option>
                                    <option value="medium" <?php echo isset($_GET['priority']) && $_GET['priority'] == 'medium' ? 'selected' : ''; ?>>Media</option>
                                    <option value="low" <?php echo isset($_GET['priority']) && $_GET['priority'] == 'low' ? 'selected' : ''; ?>>Baja</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de alertas -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($alerts)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No hay alertas para mostrar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped" id="alertsTable">
                                    <thead>
                                        <tr>
                                            <th>Dispositivo</th>
                                            <th>Título</th>
                                            <th>Mensaje</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alerts as $alert): ?>
                                        <tr class="<?php echo $alert['resolved'] ? 'table-success' : ($alert['priority'] == 'high' ? 'table-danger' : ($alert['priority'] == 'medium' ? 'table-warning' : '')); ?>">
                                            <td><?php echo htmlspecialchars($alert['device_name']); ?></td>
                                            <td><?php echo htmlspecialchars($alert['title']); ?></td>
                                            <td><?php echo htmlspecialchars($alert['message']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $alert['priority'] == 'high' ? 'danger' : 
                                                        ($alert['priority'] == 'medium' ? 'warning' : 'info');
                                                ?>">
                                                    <?php echo ucfirst($alert['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $alert['resolved'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $alert['resolved'] ? 'Resuelta' : 'Pendiente'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($alert['created_at'])); ?></td>
                                            <td>
                                                <?php if (!$alert['resolved']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                                    <button type="submit" name="resolve_alert" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Resolver
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                                    <button type="submit" name="delete_alert" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar esta alerta?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Limpiar Alertas Resueltas -->
    <div class="modal fade" id="clearAlertsModal" tabindex="-1" aria-labelledby="clearAlertsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearAlertsModalLabel">Limpiar Alertas Resueltas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar todas las alertas resueltas?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="clear_alerts.php">
                        <input type="hidden" name="device_id" value="<?php echo $device_id; ?>">
                        <button type="submit" class="btn btn-danger">Eliminar Alertas Resueltas</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>