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

// Obtener dispositivos del usuario
$stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
$stmt->execute([$user_id]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si se ha seleccionado un dispositivo
$selected_device = null;
$sensor_data = [];
$irrigation_logs = [];

if (isset($_GET['device']) && !empty($_GET['device'])) {
    $device_id = $_GET['device'];
    
    // Verificar que el dispositivo pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE device_id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    $selected_device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_device) {
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
            SELECT * FROM irrigation_logs 
            WHERE device_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$device_id]);
        $irrigation_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Panel de Control</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                                <i class="fas fa-plus me-1"></i>Añadir Dispositivo
                            </button>
                        </div>
                    </div>
                </div>

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
                        $statuses = [];
                        if (!empty($device_ids)) {
                            $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
                            
                            $stmt = $pdo->prepare("
                                SELECT d.device_id, 
                                    COALESCE(sd.online_status, 0) AS online_status,
                                    COALESCE(TIMESTAMPDIFF(SECOND, sd.created_at, NOW()) > 30, 1) AS is_offline
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
                                ];
                            }
                        }

                        foreach ($devices as $device): 
                            $is_online = isset($statuses[$device['device_id']]) 
                                ? ($statuses[$device['device_id']]['online_status'] && !$statuses[$device['device_id']]['is_offline']) 
                                : false;
                        ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card device-card h-100" 
                                    onclick="location.href='?device=<?php echo $device['device_id']; ?>'" 
                                    id="device-<?php echo $device['device_id']; ?>">
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
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-temperature><?php echo $temperature; ?>°C</div>
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
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-air-humidity><?php echo $air_humidity; ?>%</div>
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
                                                        <div class="h5 mb-0 font-weight-bold text-white" data-soil-moisture><?php echo $soil_moisture; ?>%</div>
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
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" id="autoIrrigation" <?php echo $selected_device['auto_irrigation'] ? 'checked' : ''; ?> onchange="toggleAutoIrrigation('<?php echo $selected_device['device_id']; ?>', this.checked)">
                                                    <label class="form-check-label" for="autoIrrigation">Riego Automático</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="soilThreshold" class="form-label">Umbral de Humedad: <span id="thresholdValue"><?php echo $selected_device['soil_threshold']; ?></span>%</label>
                                                    <input type="range" class="form-range" id="soilThreshold" min="5" max="95" value="<?php echo $selected_device['soil_threshold']; ?>" onchange="updateThreshold('<?php echo $selected_device['device_id']; ?>', this.value)">
                                                </div>
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
    <div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDeviceModalLabel">Añadir Nuevo Dispositivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDeviceForm">
                        <div class="mb-3">
                            <label for="deviceId" class="form-label">ID del Dispositivo</label>
                            <input type="text" class="form-control" id="deviceId" required>
                            <div class="form-text">Este es el ID único que configuraste en el Arduino (ej: ARD1)</div>
                        </div>
                        <div class="mb-3">
                            <label for="deviceName" class="form-label">Nombre del Dispositivo</label>
                            <input type="text" class="form-control" id="deviceName" required>
                        </div>
                        <div class="mb-3">
                            <label for="deviceDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="deviceDescription" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="addDevice()">Añadir Dispositivo</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script>
        const SELECTED_DEVICE_ID = "<?php echo $selected_device ? $selected_device['device_id'] : ''; ?>";
    </script>
    <script src="assets/js/script.js"></script>
    
    <?php if ($selected_device && !empty($sensor_data)): ?>
    <script>
        const sensorCtx = document.getElementById('sensorChart').getContext('2d');
        const maxPoints = 20; // Máximo de puntos en el gráfico

        // Inicializar datos desde PHP
        const labels = [<?php 
            $maxPoints = 20;
            $labels = [];
            foreach (array_slice($sensor_data, -$maxPoints) as $data) {
                $labels[] = "'" . date('H:i', strtotime($data['created_at'])) . "'";
            }
            echo implode(',', $labels);
        ?>];

        const tempData = [<?php echo implode(',', array_map(fn($d) => $d['temperature'] ?? 0, array_slice($sensor_data, -$maxPoints))); ?>];
        const airHumData = [<?php echo implode(',', array_map(fn($d) => $d['air_humidity'] ?? 0, array_slice($sensor_data, -$maxPoints))); ?>];
        const soilHumData = [<?php echo implode(',', array_map(fn($d) => $d['soil_moisture'] ?? 0, array_slice($sensor_data, -$maxPoints))); ?>];

        const sensorChart = new Chart(sensorCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Temperatura (°C)', data: tempData, borderColor: 'rgb(255, 99, 132)', tension: 0.1, yAxisID: 'y' },
                    { label: 'Humedad Aire (%)', data: airHumData, borderColor: 'rgb(54, 162, 235)', tension: 0.1, yAxisID: 'y' },
                    { label: 'Humedad Suelo (%)', data: soilHumData, borderColor: 'rgb(75, 192, 192)', tension: 0.1, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Temperatura/Humedad Aire' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Humedad Suelo' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
