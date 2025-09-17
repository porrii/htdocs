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
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '7days';

// Obtener dispositivos para el filtro
$stmt = $pdo->prepare("SELECT device_id, name FROM devices WHERE user_id = ?");
$stmt->execute([$user_id]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular fechas según el rango seleccionado
$date_conditions = [];
$params = [$user_id];

switch ($date_range) {
    case 'today':
        $date_conditions[] = "DATE(il.created_at) = CURDATE()";
        break;
    case 'yesterday':
        $date_conditions[] = "DATE(il.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case '7days':
        $date_conditions[] = "il.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30days':
        $date_conditions[] = "il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'month':
        $date_conditions[] = "MONTH(il.created_at) = MONTH(CURDATE()) AND YEAR(il.created_at) = YEAR(CURDATE())";
        break;
    case 'custom':
        if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
            $date_conditions[] = "DATE(il.created_at) BETWEEN ? AND ?";
            $params[] = $_GET['start_date'];
            $params[] = $_GET['end_date'];
        }
        break;
}

// Añadir filtro de dispositivo si está seleccionado
if ($device_id) {
    $date_conditions[] = "il.device_id = ?";
    $params[] = $device_id;
}

$where_clause = !empty($date_conditions) ? "AND " . implode(" AND ", $date_conditions) : "";

// Obtener estadísticas de riego
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_irrigations,
        SUM(il.duration) as total_duration,
        AVG(il.duration) as avg_duration,
        d.name as device_name,
        DATE(il.created_at) as irrigation_date
    FROM irrigation_log il
    JOIN devices d ON il.device_id = d.device_id
    WHERE d.user_id = ? $where_clause
    GROUP BY DATE(il.created_at), il.device_id
    ORDER BY irrigation_date DESC
");
$stmt->execute($params);
$irrigation_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para gráficos
$chart_data = [];
if ($device_id) {
    // Datos de humedad del suelo para el gráfico
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            AVG(soil_moisture) as avg_moisture,
            MIN(soil_moisture) as min_moisture,
            MAX(soil_moisture) as max_moisture
        FROM sensor_data 
        WHERE device_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$device_id]);
    $moisture_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para el gráfico
    $chart_labels = [];
    $chart_avg = [];
    $chart_min = [];
    $chart_max = [];
    
    foreach ($moisture_data as $data) {
        $chart_labels[] = date('d M', strtotime($data['date']));
        $chart_avg[] = $data['avg_moisture'];
        $chart_min[] = $data['min_moisture'];
        $chart_max[] = $data['max_moisture'];
    }
    
    $chart_data = [
        'labels' => $chart_labels,
        'avg' => $chart_avg,
        'min' => $chart_min,
        'max' => $chart_max
    ];
}

// Generar reporte PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    require_once 'includes/pdf_generator.php';
    generateIrrigationReportPDF($irrigation_stats, $date_range, $device_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartGarden - Reportes y Análisis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="row">
            <?php // include 'includes/sidebar.php'; ?>
            
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reportes y Análisis</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i>Exportar PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="device" class="form-label">Dispositivo</label>
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
                                <label for="date_range" class="form-label">Rango de Fechas</label>
                                <select class="form-select" id="date_range" name="date_range">
                                    <option value="today" <?php echo $date_range == 'today' ? 'selected' : ''; ?>>Hoy</option>
                                    <option value="yesterday" <?php echo $date_range == 'yesterday' ? 'selected' : ''; ?>>Ayer</option>
                                    <option value="7days" <?php echo $date_range == '7days' ? 'selected' : ''; ?>>Últimos 7 días</option>
                                    <option value="30days" <?php echo $date_range == '30days' ? 'selected' : ''; ?>>Últimos 30 días</option>
                                    <option value="month" <?php echo $date_range == 'month' ? 'selected' : ''; ?>>Este mes</option>
                                    <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="custom_range" style="display: <?php echo $date_range == 'custom' ? 'block' : 'none'; ?>">
                                <label for="start_date" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                            </div>
                            <div class="col-md-3" id="custom_range_end" style="display: <?php echo $date_range == 'custom' ? 'block' : 'none'; ?>">
                                <label for="end_date" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Tipo de Reporte</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Diario</option>
                                    <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Mensual</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Generar Reporte</button>
                                <a href="reports.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resumen Estadístico -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Riegos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo array_sum(array_column($irrigation_stats, 'total_irrigations')); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tint fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tiempo Total de Riego</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $total_seconds = array_sum(array_column($irrigation_stats, 'total_duration'));
                                            $hours = floor($total_seconds / 3600);
                                            $minutes = floor(($total_seconds % 3600) / 60);
                                            $seconds = $total_seconds % 60;
                                            echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Duración Promedio</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $avg_duration = !empty($irrigation_stats) ? 
                                                array_sum(array_column($irrigation_stats, 'avg_duration')) / count($irrigation_stats) : 0;
                                            echo number_format($avg_duration, 2) . ' seg';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Dispositivos Activos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $active_devices = array_unique(array_column($irrigation_stats, 'device_name'));
                                            echo count($active_devices);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-microchip fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Humedad del Suelo -->
                <?php if (!empty($chart_data)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Humedad del Suelo (Últimos 30 días)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="moistureChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabla de Estadísticas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Estadísticas de Riego</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($irrigation_stats)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No hay datos de riego para el rango seleccionado.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Dispositivo</th>
                                            <th>N° de Riegos</th>
                                            <th>Duración Total</th>
                                            <th>Duración Promedio</th>
                                            <th>Consumo Estimado (L)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($irrigation_stats as $stat): 
                                            // Estimación: 2 litros por segundo de riego (ajustable)
                                            $water_consumption = $stat['total_duration'] * 2;
                                        ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($stat['irrigation_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($stat['device_name']); ?></td>
                                                <td><?php echo $stat['total_irrigations']; ?></td>
                                                <td><?php echo gmdate('H:i:s', $stat['total_duration']); ?></td>
                                                <td><?php echo number_format($stat['avg_duration'], 2); ?> seg</td>
                                                <td><?php echo number_format($water_consumption, 2); ?> L</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <th colspan="2">TOTALES</th>
                                            <th><?php echo array_sum(array_column($irrigation_stats, 'total_irrigations')); ?></th>
                                            <th><?php 
                                                $total_seconds = array_sum(array_column($irrigation_stats, 'total_duration'));
                                                echo gmdate('H:i:s', $total_seconds);
                                            ?></th>
                                            <th><?php 
                                                $avg_duration = !empty($irrigation_stats) ? 
                                                    array_sum(array_column($irrigation_stats, 'avg_duration')) / count($irrigation_stats) : 0;
                                                echo number_format($avg_duration, 2) . ' seg';
                                            ?></th>
                                            <th><?php 
                                                $total_water = $total_seconds * 2;
                                                echo number_format($total_water, 2) . ' L';
                                            ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar campos de fecha personalizada
        document.getElementById('date_range').addEventListener('change', function() {
            const customRange = document.getElementById('custom_range');
            const customRangeEnd = document.getElementById('custom_range_end');
            
            if (this.value === 'custom') {
                customRange.style.display = 'block';
                customRangeEnd.style.display = 'block';
            } else {
                customRange.style.display = 'none';
                customRangeEnd.style.display = 'none';
            }
        });

        // Gráfico de humedad del suelo
        <?php if (!empty($chart_data)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('moistureChart').getContext('2d');
            const moistureChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_data['labels']); ?>,
                    datasets: [
                        {
                            label: 'Humedad Promedio',
                            data: <?php echo json_encode($chart_data['avg']); ?>,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            fill: true,
                            tension: 0.1
                        },
                        {
                            label: 'Humedad Mínima',
                            data: <?php echo json_encode($chart_data['min']); ?>,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            fill: false,
                            tension: 0.1,
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Humedad Máxima',
                            data: <?php echo json_encode($chart_data['max']); ?>,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            fill: false,
                            tension: 0.1,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Humedad (%)'
                            },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>