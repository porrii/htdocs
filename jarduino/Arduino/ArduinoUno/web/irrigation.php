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

// Obtener registros de riego para todos los dispositivos del usuario
$irrigation_logs = [];
if (!empty($devices)) {
    $device_ids = array_column($devices, 'device_id');
    $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
    
    $stmt = $pdo->prepare("
        SELECT il.*, d.name as device_name 
        FROM irrigation_logs il 
        JOIN devices d ON il.device_id = d.device_id 
        WHERE il.device_id IN ($placeholders) 
        ORDER BY il.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute($device_ids);
    $irrigation_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Riego - SmartGarden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Control de Riego</h1>
                </div>

                <div class="row">
                    <!-- Riego Manual -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Riego Manual</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="deviceSelect" class="form-label">Seleccionar Dispositivo</label>
                                    <select class="form-select" id="deviceSelect" required>
                                        <option value="">Seleccionar dispositivo...</option>
                                        <?php foreach ($devices as $device): ?>
                                            <option value="<?php echo $device['device_id']; ?>"><?php echo htmlspecialchars($device['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="irrigationTime" class="form-label">Duración (segundos)</label>
                                    <input type="number" class="form-control" id="irrigationTime" min="1" max="300" value="10">
                                </div>
                                <button type="button" class="btn btn-primary w-100 mb-2"
                                    onclick="startIrrigation(document.getElementById('deviceSelect').value)">
                                    <i class="fas fa-play me-1"></i>Iniciar Riego
                                </button>
                                <button type="button" class="btn btn-danger w-100"
                                    onclick="stopIrrigation(document.getElementById('deviceSelect').value)">
                                    <i class="fas fa-stop me-1"></i>Detener Riego
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas de Riego -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Estadísticas de Riego</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($devices)): ?>
                                    <p class="text-muted">No hay dispositivos registrados.</p>
                                <?php else: ?>
                                    <?php
                                    $total_irrigation = 0;
                                    $today_irrigation = 0;
                                    $device_irrigation = [];
                                    foreach ($irrigation_logs as $log) {
                                        $total_irrigation += $log['duration'];
                                        if (date('Y-m-d', strtotime($log['created_at'])) == date('Y-m-d')) {
                                            $today_irrigation += $log['duration'];
                                        }
                                        if (!isset($device_irrigation[$log['device_id']])) {
                                            $device_irrigation[$log['device_id']] = ['name'=>$log['device_name'],'total'=>0,'count'=>0];
                                        }
                                        $device_irrigation[$log['device_id']]['total'] += $log['duration'];
                                        $device_irrigation[$log['device_id']]['count']++;
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <h6>Total de riego: <?php echo $total_irrigation; ?> segundos</h6>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo min($total_irrigation / 1000 * 100, 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Riego hoy: <?php echo $today_irrigation; ?> segundos</h6>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo min($today_irrigation / 300 * 100, 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <h6>Por dispositivo:</h6>
                                    <?php foreach ($device_irrigation as $device): ?>
                                        <div class="mb-2">
                                            <small><?php echo $device['name']; ?>: <?php echo $device['total']; ?>s (<?php echo $device['count']; ?> veces)</small>
                                            <div class="progress" style="height:5px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo min($device['total']/500*100,100); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Riego -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Historial de Riego</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($irrigation_logs)): ?>
                            <p class="text-muted">No hay registros de riego.</p>
                        <?php else: ?>
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
                                        <tr data-device-id="<?php echo $log['device_id']; ?>">
                                            <td><?php echo $log['created_at']; ?></td>
                                            <td><?php echo htmlspecialchars($log['device_name']); ?></td>
                                            <td><?php echo $log['duration']; ?> segundos</td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $log['mode']=='manual'?'primary':($log['mode']=='auto'?'success':'warning');
                                                ?>"><?php echo $log['mode']; ?></span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    // Filtrar historial según dispositivo seleccionado
    document.getElementById('deviceSelect').addEventListener('change', function() {
        const selectedDevice = this.value;
        const rows = document.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            if (!selectedDevice || row.dataset.deviceId === selectedDevice) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Filtrar automáticamente al cargar la página si hay un dispositivo seleccionado
    window.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('deviceSelect');
        if (select.value) {
            select.dispatchEvent(new Event('change'));
        }
    });
    </script>
</body>
</html>
