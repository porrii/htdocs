<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener parámetros
$type = $_GET['type'] ?? 'all';
$include_sensors = isset($_GET['sensors']) && $_GET['sensors'] == '1';
$device_id = $_GET['device'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

try {
    // Preparar datos para exportación
    $export_data = [
        'metadata' => [
            'export_date' => date('Y-m-d H:i:s'),
            'user_id' => $user_id,
            'export_type' => $type,
            'include_sensors' => $include_sensors
        ],
        'user_info' => [],
        'devices' => [],
        'irrigation_logs' => [],
        'sensor_data' => [],
        'schedules' => [],
        'alerts' => []
    ];

    // Obtener información del usuario
    $stmt = $pdo->prepare("SELECT user_id, username, email, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $export_data['user_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener dispositivos del usuario
    $device_where = "user_id = ?";
    $device_params = [$user_id];
    
    if (!empty($device_id)) {
        $device_where .= " AND device_id = ?";
        $device_params[] = $device_id;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE $device_where");
    $stmt->execute($device_params);
    $export_data['devices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener configuraciones de dispositivos
    if (!empty($export_data['devices'])) {
        $device_ids = array_column($export_data['devices'], 'device_id');
        $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
        
        $stmt = $pdo->prepare("SELECT * FROM device_config WHERE device_id IN ($placeholders)");
        $stmt->execute($device_ids);
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Asociar configuraciones con dispositivos
        foreach ($export_data['devices'] as &$device) {
            $device['config'] = [];
            foreach ($configs as $config) {
                if ($config['device_id'] === $device['device_id']) {
                    $device['config'] = $config;
                    break;
                }
            }
        }
    }

    // Obtener registros de riego
    if ($type === 'all' || $type === 'irrigation') {
        $irrigation_where = "d.user_id = ?";
        $irrigation_params = [$user_id];
        
        if (!empty($device_id)) {
            $irrigation_where .= " AND il.device_id = ?";
            $irrigation_params[] = $device_id;
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
        ");
        $stmt->execute($irrigation_params);
        $export_data['irrigation_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener datos de sensores (si se solicita)
    if ($include_sensors && ($type === 'all' || $type === 'sensors')) {
        $sensor_where = "d.user_id = ?";
        $sensor_params = [$user_id];
        
        if (!empty($device_id)) {
            $sensor_where .= " AND sd.device_id = ?";
            $sensor_params[] = $device_id;
        }
        
        if (!empty($date_from)) {
            $sensor_where .= " AND DATE(sd.created_at) >= ?";
            $sensor_params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $sensor_where .= " AND DATE(sd.created_at) <= ?";
            $sensor_params[] = $date_to;
        }
        
        // Limitar a 1000 registros por dispositivo para evitar archivos demasiado grandes
        $stmt = $pdo->prepare("
            SELECT sd.*, d.name as device_name 
            FROM sensor_data sd 
            JOIN devices d ON sd.device_id = d.device_id 
            WHERE $sensor_where 
            ORDER BY sd.created_at DESC 
            LIMIT 1000
        ");
        $stmt->execute($sensor_params);
        $export_data['sensor_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener programaciones de riego
    if ($type === 'all') {
        $stmt = $pdo->prepare("
            SELECT s.*, d.name as device_name 
            FROM irrigation_schedules s 
            JOIN devices d ON s.device_id = d.device_id 
            WHERE d.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $export_data['schedules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener alertas
    if ($type === 'all') {
        $stmt = $pdo->prepare("
            SELECT a.*, d.name as device_name 
            FROM alerts a 
            JOIN devices d ON a.device_id = d.device_id 
            WHERE d.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $export_data['alerts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Determinar el formato de exportación
    $format = $_GET['format'] ?? 'json';
    
    if ($format === 'csv') {
        exportToCSV($export_data, $type);
    } else {
        exportToJSON($export_data);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}

// Función para exportar a JSON
function exportToJSON($data) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="smartgarden_export_' . date('Y-m-d') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

// Función para exportar a CSV
function exportToCSV($data, $type) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="smartgarden_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($type) {
        case 'irrigation':
            // Encabezados para riego
            fputcsv($output, ['Fecha', 'Dispositivo', 'Duración (segundos)', 'Modo', 'Creado']);
            
            foreach ($data['irrigation_logs'] as $log) {
                fputcsv($output, [
                    $log['created_at'],
                    $log['device_name'],
                    $log['duration'],
                    $log['mode'],
                    $log['created_at']
                ]);
            }
            break;
            
        case 'sensors':
            // Encabezados para sensores
            fputcsv($output, ['Fecha', 'Dispositivo', 'Temperatura (°C)', 'Humedad Aire (%)', 'Humedad Suelo (%)', 'Estado Bomba', 'En Línea']);
            
            foreach ($data['sensor_data'] as $sensor) {
                fputcsv($output, [
                    $sensor['created_at'],
                    $sensor['device_name'],
                    $sensor['temperature'],
                    $sensor['air_humidity'],
                    $sensor['soil_moisture'],
                    $sensor['pump_status'] ? 'ACTIVA' : 'INACTIVA',
                    $sensor['online_status'] ? 'SÍ' : 'NO'
                ]);
            }
            break;
            
        default:
            // Encabezados para exportación completa
            fputcsv($output, ['Tipo', 'Fecha', 'Dispositivo', 'Detalles']);
            
            // Agregar riegos
            foreach ($data['irrigation_logs'] as $log) {
                fputcsv($output, [
                    'RIEGO',
                    $log['created_at'],
                    $log['device_name'],
                    "Duración: {$log['duration']}s - Modo: {$log['mode']}"
                ]);
            }
            
            // Agregar datos de sensores
            foreach ($data['sensor_data'] as $sensor) {
                fputcsv($output, [
                    'SENSOR',
                    $sensor['created_at'],
                    $sensor['device_name'],
                    "Temp: {$sensor['temperature']}°C - HumAire: {$sensor['air_humidity']}% - HumSuelo: {$sensor['soil_moisture']}%"
                ]);
            }
            
            // Agregar alertas
            foreach ($data['alerts'] as $alert) {
                fputcsv($output, [
                    'ALERTA',
                    $alert['created_at'],
                    $alert['device_name'],
                    "{$alert['title']}: {$alert['message']} - Prioridad: {$alert['priority']}"
                ]);
            }
            break;
    }
    
    fclose($output);
}
?>