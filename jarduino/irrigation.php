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
        FROM irrigation_log il 
        JOIN devices d ON il.device_id = d.device_id 
        WHERE il.device_id IN ($placeholders) 
        ORDER BY il.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute($device_ids);
    $irrigation_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener programaciones de riego
$schedules = [];
if (!empty($devices)) {
    $device_ids = array_column($devices, 'device_id');
    $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
    
    $stmt = $pdo->prepare("
        SELECT s.*, d.name as device_name 
        FROM irrigation_schedules s 
        JOIN devices d ON s.device_id = d.device_id 
        WHERE s.device_id IN ($placeholders) 
        ORDER BY s.active DESC, s.start_time ASC
    ");
    $stmt->execute($device_ids);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="container">
        <div class="row">
            <?php // include 'includes/sidebar.php'; ?>
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Control de Riego</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="fas fa-plus me-1"></i>Nueva Programación
                        </button>
                    </div>
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
                                            $device_irrigation[$log['device_id']] = [
                                                'name' => $log['device_name'],
                                                'total' => 0,
                                                'count' => 0
                                            ];
                                        }
                                        $device_irrigation[$log['device_id']]['total'] += $log['duration'];
                                        $device_irrigation[$log['device_id']]['count']++;
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <h6>Total de riego: <?php echo $total_irrigation; ?> segundos</h6>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo min(($total_irrigation / 1000) * 100, 100); ?>%"
                                                 aria-valuenow="<?php echo min(($total_irrigation / 1000) * 100, 100); ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Riego hoy: <?php echo $today_irrigation; ?> segundos</h6>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo min(($today_irrigation / 300) * 100, 100); ?>%"
                                                 aria-valuenow="<?php echo min(($today_irrigation / 300) * 100, 100); ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <h6>Por dispositivo:</h6>
                                    <?php foreach ($device_irrigation as $device_id => $device): ?>
                                        <div class="mb-2 device-stats" data-device-id="<?php echo $device_id; ?>">
                                            <small><?php echo htmlspecialchars($device['name']); ?>: <?php echo $device['total']; ?>s (<?php echo $device['count']; ?> veces)</small>
                                            <div class="progress" style="height:5px;">
                                                <div class="progress-bar" role="progressbar" 
                                                    style="width: <?php echo min(($device['total'] / 500) * 100, 100); ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Historial de Riego -->
                    <div class="col-md-6">
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
                                                    <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($log['device_name']); ?></td>
                                                    <td><?php echo $log['duration']; ?> segundos</td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                            echo $log['mode'] == 'manual' ? 'primary' : 
                                                                 ($log['mode'] == 'auto' ? 'success' : 'warning');
                                                        ?>">
                                                            <?php echo $log['mode']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                
                    <!-- Programaciones de Riego -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Programaciones de Riego</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($schedules)): ?>
                                    <p class="text-muted">No hay programaciones configuradas.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($schedules as $schedule): ?>
                                        <div class="list-group-item schedule-item" data-device-id="<?php echo $schedule['device_id']; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($schedule['device_name']); ?></h6>
                                                    <p class="mb-1"><?php echo $schedule['start_time']; ?> - <?php echo $schedule['duration']; ?> segundos</p>
                                                    <small class="text-muted">Días: <?php echo $schedule['days_of_week']; ?></small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input schedule-status" type="checkbox" 
                                                           data-schedule-id="<?php echo $schedule['id']; ?>"
                                                           <?php echo $schedule['active'] ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editSchedule(<?php echo $schedule['id']; ?>)">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">
                                                    <i class="fas fa-trash me-1"></i>Eliminar
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

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
                        <div class="mb-3">
                            <label for="device_id" class="form-label">Dispositivo</label>
                            <select class="form-select" id="device_id" name="device_id" required>
                                <option value="">Seleccionar dispositivo...</option>
                                <?php foreach ($devices as $device): ?>
                                    <option value="<?php echo $device['device_id']; ?>"><?php echo htmlspecialchars($device['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Hora de Inicio</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duración (segundos)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1" max="300" value="10" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/script.js"></script>
    <script>
    // Filtrar historial según dispositivo seleccionado
    document.getElementById('deviceSelect').addEventListener('change', function() {
        const selectedDevice = this.value;

        // Filtrar historial
        document.querySelectorAll('table tbody tr').forEach(row => {
            row.style.display = (!selectedDevice || row.getAttribute('data-device-id') === selectedDevice) ? '' : 'none';
        });

        // Filtrar estadísticas
        document.querySelectorAll('.device-stats').forEach(stat => {
            stat.style.display = (!selectedDevice || stat.getAttribute('data-device-id') === selectedDevice) ? '' : 'none';
        });

        // Filtrar programaciones
        document.querySelectorAll('.schedule-item').forEach(schedule => {
            schedule.style.display = (!selectedDevice || schedule.getAttribute('data-device-id') === selectedDevice) ? '' : 'none';
        });
    });

    // Filtrar automáticamente al cargar la página si hay un dispositivo seleccionado
    window.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('deviceSelect');
        if (select.value) {
            select.dispatchEvent(new Event('change'));
        }

        // Switches de programación
        document.querySelectorAll('.schedule-status').forEach(switchEl => {
            switchEl.addEventListener('change', function() {
                const scheduleId = this.getAttribute('data-schedule-id');
                const active = this.checked ? 1 : 0;
                updateScheduleStatus(scheduleId, active);
            });
        });
    });
    </script>
</body>
</html>