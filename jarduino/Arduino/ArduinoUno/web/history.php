<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener dispositivos del usuario
$stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
$stmt->execute([$user_id]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$device_filter = $_GET['device'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Obtener datos históricos
$sensor_data = [];
$irrigation_logs = [];

if (!empty($devices)) {
    $device_ids = array_column($devices, 'device_id');
    
    // Construir consulta para datos de sensores
    $sensor_where = "sd.device_id IN (" . implode(',', array_fill(0, count($device_ids), '?')) . ")";
    $sensor_params = $device_ids;
    
    if (!empty($device_filter)) {
        $sensor_where .= " AND sd.device_id = ?";
        $sensor_params[] = $device_filter;
    }
    
    if (!empty($date_from)) {
        $sensor_where .= " AND DATE(sd.created_at) >= ?";
        $sensor_params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $sensor_where .= " AND DATE(sd.created_at) <= ?";
        $sensor_params[] = $date_to;
    }
    
    $stmt = $pdo->prepare("
        SELECT sd.*, d.name as device_name 
        FROM sensor_data sd 
        JOIN devices d ON sd.device_id = d.device_id 
        WHERE $sensor_where 
        ORDER BY sd.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute($sensor_params);
    $sensor_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir consulta para registros de riego
    $irrigation_where = "il.device_id IN (" . implode(',', array_fill(0, count($device_ids), '?')) . ")";
    $irrigation_params = $device_ids;
    
    if (!empty($device_filter)) {
        $irrigation_where .= " AND il.device_id = ?";
        $irrigation_params[] = $device_filter;
    }
    
    if (!empty($date_from)) {
        $irrigation_where .= " AND DATE(il.created_at) >= ?";
        $irrigation_params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $irrigation_where .= " AND DATE(il.created_at) <= ?";
        $irrigation_params[] = $date_to;
    }
    
    $stmt = $pdo->prepare("
        SELECT il.*, d.name as device_name 
        FROM irrigation_logs il 
        JOIN devices d ON il.device_id = d.device_id 
        WHERE $irrigation_where 
        ORDER BY il.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute($irrigation_params);
    $irrigation_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - SmartGarden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Historial</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="device" class="form-label">Dispositivo</label>
                                <select class="form-select" id="device" name="device">
                                    <option value="">Todos los dispositivos</option>
                                    <?php foreach ($devices as $device): ?>
                                    <option value="<?php echo $device['device_id']; ?>" <?php echo $device_filter == $device['device_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($device['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                                <a href="history.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($sensor_data)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos de Sensores</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="sensorChart"></canvas>
                        </div>
                        
                        <div class="table-responsive mt-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha y Hora</th>
                                        <th>Dispositivo</th>
                                        <th>Temperatura</th>
                                        <th>Humedad Aire</th>
                                        <th>Humedad Suelo</th>
                                        <th>Bomba</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sensor_data as $data): ?>
                                    <tr>
                                        <td><?php echo $data['created_at']; ?></td>
                                        <td><?php echo htmlspecialchars($data['device_name']); ?></td>
                                        <td><?php echo $data['temperature']; ?>°C</td>
                                        <td><?php echo $data['air_humidity']; ?>%</td>
                                        <td><?php echo $data['soil_moisture']; ?>%</td>
                                        <td>
                                            <span class="badge bg-<?php echo $data['pump_status'] ? 'warning' : 'secondary'; ?>">
                                                <?php echo $data['pump_status'] ? 'ACTIVA' : 'INACTIVA'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($irrigation_logs)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Registros de Riego</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha y Hora</th>
                                        <th>Dispositivo</th>
                                        <th>Duración</th>
                                        <th>Modo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($irrigation_logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['created_at']; ?></td>
                                        <td><?php echo htmlspecialchars($log['device_name']); ?></td>
                                        <td><?php echo $log['duration']; ?> segundos</td>
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
                <?php endif; ?>

                <?php if (empty($sensor_data) && empty($irrigation_logs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No hay datos históricos para los filtros seleccionados.
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($sensor_data)): ?>
    <script>
        // Preparar datos para el gráfico
        const dates = [];
        const temperatures = [];
        const airHumidities = [];
        const soilMoistures = [];
        
        <?php 
        // Limitar a 50 puntos para el gráfico
        $chart_data = array_slice($sensor_data, 0, 50);
        foreach (array_reverse($chart_data) as $data): 
        ?>
            dates.push('<?php echo date('H:i', strtotime($data["created_at"])); ?>');
            temperatures.push(<?php echo $data['temperature']; ?>);
            airHumidities.push(<?php echo $data['air_humidity']; ?>);
            soilMoistures.push(<?php echo $data['soil_moisture']; ?>);
        <?php endforeach; ?>
        
        // Crear gráfico
        const ctx = document.getElementById('sensorChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Temperatura (°C)',
                        data: temperatures,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Humedad Aire (%)',
                        data: airHumidities,
                        borderColor: 'rgb(54, 162, 235)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Humedad Suelo (%)',
                        data: soilMoistures,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Temperatura/Humedad Aire'
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
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>