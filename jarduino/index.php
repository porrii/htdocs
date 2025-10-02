<?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/auth.php';

    // Verificar si el usuario está logueado
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Obtener estadísticas generales
    $stats = [];
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_devices,
            SUM(CASE WHEN sd.online_status = 1 AND TIMESTAMPDIFF(SECOND, sd.created_at, NOW()) <= 30 THEN 1 ELSE 0 END) as online_devices,
            (SELECT COUNT(*) FROM irrigation_log il JOIN devices d ON il.device_id = d.device_id WHERE d.user_id = ? AND DATE(il.created_at) = CURDATE()) as today_irrigations,
            (SELECT SUM(duration) FROM irrigation_log il JOIN devices d ON il.device_id = d.device_id WHERE d.user_id = ? AND DATE(il.created_at) = CURDATE()) as today_water_usage
        FROM devices d
        LEFT JOIN sensor_data sd ON d.device_id = sd.device_id 
            AND sd.created_at = (SELECT MAX(created_at) FROM sensor_data WHERE device_id = d.device_id)
        WHERE d.user_id = ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener alertas recientes
    $alerts = [];
    $stmt = $pdo->prepare("
        SELECT a.*, d.name as device_name 
        FROM alerts a 
        JOIN devices d ON a.device_id = d.device_id 
        WHERE d.user_id = ? 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener dispositivos del usuario
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si se ha seleccionado un dispositivo
    $selected_device = null;
    $sensor_data = [];
    $irrigation_logs = [];
    $device_config = [];
    $irrigation_schedules = [];

    if (isset($_GET['device']) && !empty($_GET['device'])) {
        $device_id = $_GET['device'];
        
        // Verificar que el dispositivo pertenece al usuario
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE device_id = ? AND user_id = ?");
        $stmt->execute([$device_id, $user_id]);
        $selected_device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_device) {
            // Obtener configuración del dispositivo
            $stmt = $pdo->prepare("SELECT * FROM device_config WHERE device_id = ?");
            $stmt->execute([$device_id]);
            $device_config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener programaciones de riego
            $stmt = $pdo->prepare("SELECT * FROM irrigation_schedules WHERE device_id = ? ORDER BY created_at DESC");
            $stmt->execute([$device_id]);
            $irrigation_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener datos del sensor (últimas 24 horas)
            $stmt = $pdo->prepare("
                SELECT * FROM sensor_data 
                WHERE device_id = ? AND created_at >= NOW() - INTERVAL 24 HOUR 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$device_id]);
            $sensor_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener registros de riego
            $stmt = $pdo->prepare("
                SELECT * FROM irrigation_log 
                WHERE device_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$device_id]);
            $irrigation_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener configuración
            $auto_irrigation = $device_config ? (bool)$device_config['auto_irrigation'] : false;
            $soil_threshold = $device_config ? $device_config['threshold'] : 50;
            $irrigation_duration = $device_config ? $device_config['duration'] : 10;
        }
    }

    // Marcar dispositivos offline automáticamente
    $offline_threshold = 30; // segundos
    $stmt = $pdo->prepare("
        UPDATE sensor_data 
        SET online_status = 0 
        WHERE created_at < NOW() - INTERVAL ? SECOND 
        AND online_status = 1
    ");
    $stmt->execute([$offline_threshold]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartGarden Control Panel</title>
    <style>
        .stats-card { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
        .device-card { cursor: pointer; transition: transform 0.2s; }
        .device-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .online-status { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .online { background-color: #1cc88a; }
        .offline { background-color: #e74a3b; }
        .pump-active { color: #f6c23e; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .alert-badge { position: absolute; top: -5px; right: -5px; }
        .weather-card { background: linear-gradient(45deg, #36b9cc, #2c9faf); color: white; }
        .consumption-card { background: linear-gradient(45deg, #1cc88a, #17a673); color: white; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="row">
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Panel de Control</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                                <i class="fas fa-plus me-1"></i>Añadir Dispositivo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.location.href='irrigation.php'">
                                <i class="fas fa-tint me-1"></i>Control de Riego
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas generales -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-0">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Dispositivos Totales</div>
                                        <div class="h5 mb-0 font-weight-bold text-white"><?php echo $stats['total_devices'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-microchip fa-2x text-white-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-0">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Dispositivos Online</div>
                                        <div class="h5 mb-0 font-weight-bold text-white"><?php echo $stats['online_devices'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-wifi fa-2x text-white-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card weather-card border-0">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Riegos Hoy</div>
                                        <div class="h5 mb-0 font-weight-bold text-white"><?php echo $stats['today_irrigations'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tint fa-2x text-white-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card consumption-card border-0">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Agua Usada Hoy</div>
                                        <div class="h5 mb-0 font-weight-bold text-white"><?php echo ($stats['today_water_usage'] ?? 0) . 's'; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-water fa-2x text-white-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas recientes -->
                <?php if (!empty($alerts)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Alertas Recientes</h6>
                                <a href="alerts.php" class="btn btn-sm btn-primary">Ver Todas</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach ($alerts as $alert): ?>
                                    <a href="alerts.php" class="list-group-item list-group-item-action flex-column align-items-start">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($alert['device_name']); ?> - <?php echo htmlspecialchars($alert['title']); ?></h6>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($alert['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($alert['message']); ?></p>
                                        <small class="text-<?php echo $alert['priority'] == 'high' ? 'danger' : ($alert['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                            Prioridad: <?php echo ucfirst($alert['priority']); ?>
                                        </small>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($devices)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No tienes dispositivos registrados. 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addDeviceModal">Añade tu primer dispositivo</a>.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php
                        $device_ids = array_column($devices, 'device_id');
                        $statuses = [];
                        if (!empty($device_ids)) {
                            $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
                            
                            $stmt = $pdo->prepare("
                                SELECT d.device_id, 
                                    COALESCE(sd.online_status, 0) AS online_status,
                                    COALESCE(TIMESTAMPDIFF(SECOND, sd.created_at, NOW()) > 30, 1) AS is_offline,
                                    (SELECT COUNT(*) FROM alerts a WHERE a.device_id = d.device_id AND a.resolved = 0) as alert_count
                                FROM devices d
                                LEFT JOIN sensor_data sd ON d.device_id = sd.device_id 
                                    AND sd.created_at = (
                                        SELECT MAX(created_at) 
                                        FROM sensor_data 
                                        WHERE device_id = d.device_id
                                    )
                                WHERE d.device_id IN ($placeholders)
                            ");
                            $stmt->execute($device_ids);

                            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                $statuses[$row['device_id']] = [
                                    'online_status' => $row['online_status'],
                                    'is_offline'   => $row['is_offline'],
                                    'alert_count'  => $row['alert_count']
                                ];
                            }
                        }

                        foreach ($devices as $device): 
                            $is_online = isset($statuses[$device['device_id']]) 
                                ? ($statuses[$device['device_id']]['online_status'] && !$statuses[$device['device_id']]['is_offline']) 
                                : false;
                            $alert_count = $statuses[$device['device_id']]['alert_count'] ?? 0;
                        ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card device-card h-100 position-relative" 
                                    onclick="location.href='?device=<?php echo $device['device_id']; ?>'" 
                                    id="device-<?php echo $device['device_id']; ?>">
                                    <?php if ($alert_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger alert-badge">
                                        <?php echo $alert_count; ?>
                                    </span>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    <?php echo htmlspecialchars($device['name']); ?>
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="status-<?php echo $device['device_id']; ?>">
                                                    <span class="online-status <?php echo $is_online ? 'online' : 'offline'; ?>"></span>
                                                    <?php echo $is_online ? 'En línea' : 'Desconectado'; ?>
                                                </div>
                                                <div class="mt-2 text-muted">
                                                    <small>ID: <?php echo $device['device_id']; ?></small>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-microchip fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($selected_device): 
                    $stmt = $pdo->prepare("
                        SELECT online_status, pump_status, temperature, air_humidity, soil_moisture 
                        FROM sensor_data 
                        WHERE device_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$selected_device['device_id']]);
                    $current_status = $stmt->fetch(PDO::FETCH_ASSOC);

                    $is_online = $current_status ? (bool)$current_status['online_status'] : false;
                    $pump_active = $current_status ? (bool)$current_status['pump_status'] : false;
                    $temperature = $current_status && $current_status['temperature'] !== null ? $current_status['temperature'] : 'N/A';
                    $air_humidity = $current_status && $current_status['air_humidity'] !== null ? $current_status['air_humidity'] : 'N/A';
                    $soil_moisture = $current_status && $current_status['soil_moisture'] !== null ? $current_status['soil_moisture'] : 'N/A';
                    
                    // Obtener configuración
                    $auto_irrigation = $device_config ? (bool)$device_config['auto_irrigation'] : false;
                    $soil_threshold = $device_config ? $device_config['threshold'] : 50;
                    $irrigation_duration = $device_config ? $device_config['duration'] : 10;
                ?>
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Datos en Tiempo Real - <?php echo htmlspecialchars($selected_device['name']); ?></h6>
                                <span class="badge bg-<?php echo $is_online ? 'success' : 'danger'; ?>" data-device-status>
                                    <?php echo $is_online ? 'En línea' : 'Desconectado'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Temperatura -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card stats-card border-0">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Temperatura</div>
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-temperature><?php echo $temperature; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-temperature-high fa-2x text-white-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Humedad Aire -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card stats-card border-0">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Humedad del Aire</div>
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-air-humidity><?php echo $air_humidity; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-tint fa-2x text-white-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Humedad Suelo -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card stats-card border-0">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Humedad del Suelo</div>
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-soil-moisture><?php echo $soil_moisture; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-seedling fa-2x text-white-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Estado Bomba -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-0 <?php echo $pump_active ? 'bg-warning' : 'bg-secondary'; ?>">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Estado de la Bomba</div>
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-pump-status>
                                                            <?php echo $pump_active ? 'ACTIVA <i class="fas fa-circle pump-active ms-1"></i>' : 'INACTIVA'; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-water fa-2x text-white-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <!-- Gráfico de sensores -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="m-0 font-weight-bold text-primary">Historial de Sensores (24h)</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="sensorChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Riego y configuración -->
                                <div class="row mt-4">
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="m-0 font-weight-bold text-primary">Control de Riego</h6>
                                            </div>
                                            <div class="card-body">
                                                <form id="irrigationForm">
                                                    <div class="mb-3">
                                                        <label for="irrigationTime" class="form-label">Duración (segundos)</label>
                                                        <input type="number" class="form-control" id="irrigationTime" min="1" value="10">
                                                    </div>
                                                    <button type="button" class="btn btn-primary w-100 mb-2" onclick="startIrrigation('<?php echo $selected_device['device_id']; ?>')">
                                                        <i class="fas fa-play me-1"></i>Iniciar Riego
                                                    </button>
                                                    <button type="button" class="btn btn-danger w-100" onclick="stopIrrigation('<?php echo $selected_device['device_id']; ?>')">
                                                        <i class="fas fa-stop me-1"></i>Detener Riego
                                                    </button>
                                                </form>
                                                <hr>
                                                <h6 class="font-weight-bold">Configuración Automática</h6>
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="autoIrrigation" <?php echo $auto_irrigation ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="autoIrrigation">Riego Automático</label>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="soilThreshold" class="form-label">Umbral de Humedad: <span id="thresholdValue"><?php echo $soil_threshold; ?></span>%</label>
                                                    <input type="range" class="form-range" id="soilThreshold" min="5" max="95" value="<?php echo $soil_threshold; ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="irrigationDuration" class="form-label">Duración Riego Automático (segundos)</label>
                                                    <input type="number" class="form-control" id="irrigationDuration" min="1" value="<?php echo $irrigation_duration; ?>">
                                                </div>

                                                <button type="button" class="btn btn-success w-100" onclick="saveIrrigationConfig('<?php echo $selected_device['device_id']; ?>')">
                                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Programación de Riego -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h6 class="m-0 font-weight-bold text-primary">Programación de Riego</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($irrigation_schedules)): ?>
                                                    <div class="list-group mb-3">
                                                        <?php foreach ($irrigation_schedules as $schedule): ?>
                                                        <div class="list-group-item">
                                                            <div class="d-flex w-100 justify-content-between">
                                                                <h6 class="mb-1"><?php echo $schedule['start_time']; ?> - <?php echo $schedule['duration']; ?>s</h6>
                                                                <small class="text-<?php echo $schedule['active'] ? 'success' : 'secondary'; ?>">
                                                                    <?php echo $schedule['active'] ? 'Activo' : 'Inactivo'; ?>
                                                                </small>
                                                            </div>
                                                            <p class="mb-1">Días: <?php echo $schedule['days_of_week']; ?></p>
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-primary" onclick="editSchedule(<?php echo $schedule['id']; ?>)">Editar</button>
                                                                <button class="btn btn-outline-danger" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">Eliminar</button>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted">No hay programaciones configuradas.</p>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                                    <i class="fas fa-plus me-1"></i>Añadir Programación
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Historial de riego -->
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="m-0 font-weight-bold text-primary">Historial de Riego</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" id="irrigationTable" width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Fecha y Hora</th>
                                                                <th>Duración (seg)</th>
                                                                <th>Modo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($irrigation_logs as $log): ?>
                                                            <tr>
                                                                <td><?php echo $log['created_at']; ?></td>
                                                                <td><?php echo $log['duration']; ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?php 
                                                                        if ($log['mode'] == 'manual') echo 'primary';
                                                                        elseif ($log['mode'] == 'auto') echo 'success';
                                                                        else echo 'warning';
                                                                    ?>">
                                                                        <?php echo $log['mode']; ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Alertas del dispositivo -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h6 class="m-0 font-weight-bold text-primary">Alertas del Dispositivo</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $stmt = $pdo->prepare("
                                                    SELECT * FROM alerts 
                                                    WHERE device_id = ? 
                                                    ORDER BY created_at DESC 
                                                    LIMIT 5
                                                ");
                                                $stmt->execute([$selected_device['device_id']]);
                                                $device_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>
                                                
                                                <?php if (!empty($device_alerts)): ?>
                                                    <div class="list-group">
                                                        <?php foreach ($device_alerts as $alert): ?>
                                                        <div class="list-group-item list-group-item-<?php echo $alert['priority'] == 'high' ? 'danger' : ($alert['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                                            <div class="d-flex w-100 justify-content-between">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($alert['title']); ?></h6>
                                                                <small><?php echo date('d/m/Y H:i', strtotime($alert['created_at'])); ?></small>
                                                            </div>
                                                            <p class="mb-1"><?php echo htmlspecialchars($alert['message']); ?></p>
                                                            <?php if (!$alert['resolved']): ?>
                                                            <button class="btn btn-sm btn-success" onclick="resolveAlert(<?php echo $alert['id']; ?>)">Marcar como Resuelta</button>
                                                            <?php else: ?>
                                                            <span class="badge bg-success">Resuelta</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted">No hay alertas para este dispositivo.</p>
                                                <?php endif; ?>
                                                <div class="mt-3">
                                                    <a href="alerts.php?device=<?php echo $selected_device['device_id']; ?>" class="btn btn-primary btn-sm">Ver Todas las Alertas</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal Añadir Dispositivo -->
    <?php include 'includes/modal_addDevice.php'; ?>

    <!-- Modal Añadir Programación -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">Añadir Programación de Riego</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addScheduleForm">
                        <input type="hidden" name="device_id" value="<?php echo $selected_device['device_id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="scheduleStartTime" class="form-label">Hora de Inicio</label>
                            <input type="time" class="form-control" id="scheduleStartTime" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="scheduleDuration" class="form-label">Duración (segundos)</label>
                            <input type="number" class="form-control" id="scheduleDuration" name="duration" min="1" max="300" value="10" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Días de la Semana</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="1" id="day1">
                                <label class="form-check-label" for="day1">Lunes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="2" id="day2">
                                <label class="form-check-label" for="day2">Martes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="3" id="day3">
                                <label class="form-check-label" for="day3">Miércoles</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="4" id="day4">
                                <label class="form-check-label" for="day4">Jueves</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="5" id="day5">
                                <label class="form-check-label" for="day5">Viernes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="6" id="day6">
                                <label class="form-check-label" for="day6">Sábado</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="7" id="day7">
                                <label class="form-check-label" for="day7">Domingo</label>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="scheduleActive" name="active" checked>
                            <label class="form-check-label" for="scheduleActive">Programación Activa</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="addIrrigationSchedule()">Guardar Programación</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        const selectedDeviceId = '<?php echo $selected_device['device_id'] ?? ''; ?>';
        let sensorChart = null;
        let updateInterval = null;

        // Inicializar cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar actualización automática si hay un dispositivo seleccionado
            if (selectedDeviceId) {
                initSensorChart();
                startAutoUpdate();
            }

            // Configurar el rango de umbral
            const soilThreshold = document.getElementById('soilThreshold');
            const thresholdValue = document.getElementById('thresholdValue');
            
            if (soilThreshold && thresholdValue) {
                soilThreshold.addEventListener('input', function() {
                    thresholdValue.textContent = this.value;
                });
            }
        });

        // Inicializar gráfico de sensores
        function initSensorChart() {
            const ctx = document.getElementById('sensorChart').getContext('2d');
            
            // Preparar datos para el gráfico
            const labels = <?php echo json_encode(array_map(function($data) {
                return date('H:i', strtotime($data['created_at']));
            }, $sensor_data)); ?>.reverse();
            
            const tempData = <?php echo json_encode(array_map(function($data) {
                return $data['temperature'];
            }, $sensor_data)); ?>.reverse();
            
            const airHumidityData = <?php echo json_encode(array_map(function($data) {
                return $data['air_humidity'];
            }, $sensor_data)); ?>.reverse();
            
            const soilMoistureData = <?php echo json_encode(array_map(function($data) {
                return $data['soil_moisture'];
            }, $sensor_data)); ?>.reverse();

            // Crear el gráfico
            sensorChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Temperatura (°C)',
                            data: tempData,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Humedad Aire (%)',
                            data: airHumidityData,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Humedad Suelo (%)',
                            data: soilMoistureData,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Temp. / Hum. Aire'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Humedad Suelo'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Iniciar actualización automática de datos
        function startAutoUpdate() {
            // Actualizar cada 10 segundos
            updateInterval = setInterval(updateDeviceData, 10000);
        }

        // Actualizar datos del dispositivo
        function updateDeviceData() {
            fetch(`api/get_device_data.php?device_id=${selectedDeviceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar estado en línea/desconectado
                        const statusElement = document.querySelector('[data-device-status]');
                        if (statusElement) {
                            statusElement.textContent = data.online_status ? 'En línea' : 'Desconectado';
                            statusElement.className = `badge bg-${data.online_status ? 'success' : 'danger'}`;
                        }

                        // Actualizar valores de sensores
                        const tempElement = document.querySelector('[data-temperature]');
                        const airHumidityElement = document.querySelector('[data-air-humidity]');
                        const soilMoistureElement = document.querySelector('[data-soil-moisture]');
                        const pumpStatusElement = document.querySelector('[data-pump-status]');

                        if (tempElement) tempElement.textContent = data.temperature !== null ? data.temperature : 'N/A';
                        if (airHumidityElement) airHumidityElement.textContent = data.air_humidity !== null ? data.air_humidity : 'N/A';
                        if (soilMoistureElement) soilMoistureElement.textContent = data.soil_moisture !== null ? data.soil_moisture : 'N/A';
                        
                        if (pumpStatusElement) {
                            pumpStatusElement.innerHTML = data.pump_status ? 
                                'ACTIVA <i class="fas fa-circle pump-active ms-1"></i>' : 'INACTIVA';
                        }

                        // Actualizar gráfico si hay nuevos datos
                        if (sensorChart && data.sensor_data) {
                            const newLabels = data.sensor_data.map(item => item.time).reverse();
                            const newTempData = data.sensor_data.map(item => item.temperature).reverse();
                            const newAirHumidityData = data.sensor_data.map(item => item.air_humidity).reverse();
                            const newSoilMoistureData = data.sensor_data.map(item => item.soil_moisture).reverse();

                            sensorChart.data.labels = newLabels;
                            sensorChart.data.datasets[0].data = newTempData;
                            sensorChart.data.datasets[1].data = newAirHumidityData;
                            sensorChart.data.datasets[2].data = newSoilMoistureData;
                            sensorChart.update();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar datos:', error);
                });
        }

        // Marcar alerta como resuelta
        function resolveAlert(alertId) {
            fetch('api/resolve_alert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    alert_id: alertId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Alerta Resuelta',
                        text: 'La alerta se ha marcado como resuelta.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo resolver la alerta.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al resolver la alerta.'
                });
            });
        }

        // Limpiar intervalo cuando se cambie de página
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html>