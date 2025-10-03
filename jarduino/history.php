<?php
    $title = 'Historial - SmartGarden';
    include 'includes/header.php';

    // Obtener dispositivos del usuario
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filtros
    $device_filter = $_GET['device'] ?? '';
    $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $type_filter = $_GET['type'] ?? 'all';

    // Obtener datos históricos
    $sensor_data = [];
    $irrigation_logs = [];

    if (!empty($devices)) {
        $device_ids = array_column($devices, 'device_id');
        
        // Construir consulta para datos de sensores
        if ($type_filter === 'all' || $type_filter === 'sensors') {
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
                LIMIT 100
            ");
            $stmt->execute($sensor_params);
            $sensor_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Construir consulta para registros de riego
        if ($type_filter === 'all' || $type_filter === 'irrigation') {
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
                FROM irrigation_log il 
                JOIN devices d ON il.device_id = d.device_id 
                WHERE $irrigation_where 
                ORDER BY il.created_at DESC 
                LIMIT 100
            ");
            $stmt->execute($irrigation_params);
            $irrigation_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Obtener estadísticas
    $stats = [
        'total_readings' => count($sensor_data),
        'total_irrigations' => count($irrigation_logs),
        'total_water' => array_sum(array_column($irrigation_logs, 'duration'))
    ];
?>

<body>
    
    <div class="container">
        <div class="row">
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Historial</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportData('sensor')">
                                <i class="fas fa-download me-1"></i>Exportar Sensores
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportData('irrigation')">
                                <i class="fas fa-download me-1"></i>Exportar Riegos
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Registros de Sensores</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_readings']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Riegos Realizados</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_irrigations']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tint fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tiempo Total de Riego</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $hours = floor($stats['total_water'] / 3600);
                                            $minutes = floor(($stats['total_water'] % 3600) / 60);
                                            $seconds = $stats['total_water'] % 60;
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
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Consumo Estimado</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $water_consumption = $stats['total_water'] * 2; // 2L por segundo
                                            echo number_format($water_consumption, 2) . ' L';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-water fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                            <div class="col-md-2">
                                <label for="type" class="form-label">Tipo</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="sensors" <?php echo $type_filter == 'sensors' ? 'selected' : ''; ?>>Sensores</option>
                                    <option value="irrigation" <?php echo $type_filter == 'irrigation' ? 'selected' : ''; ?>>Riego</option>
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
                            <div class="col-md-1 d-flex align-items-end">
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
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Humedad Aire (%)',
                        data: airHumidities,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Humedad Suelo (%)',
                        data: soilMoistures,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
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