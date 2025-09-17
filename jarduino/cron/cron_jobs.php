<?php
/**
 * Script de tareas programadas para SmartGarden
 * Debe ejecutarse cada minuto mediante cron job
 * 
 * Ejemplo de cron job:
 * * * * * /usr/bin/php /ruta/a/tu/proyecto/cron_jobs.php > /dev/null 2>&1
 */

require_once '../config/database.php';
require_once 'system_monitor.php';

// Registrar ejecución
error_log("SmartGarden Cron Jobs ejecutado: " . date('Y-m-d H:i:s'));

try {
    // 1. Verificar programaciones de riego
    checkIrrigationSchedules();
    
    // 2. Monitorizar sistema y generar alertas
    $monitor = new SystemMonitor($pdo);
    $monitor->runAllChecks();
    
    // 3. Limpiar alertas antiguas (opcional)
    cleanOldAlerts();
    
    error_log("Tareas completadas exitosamente");
    
} catch (Exception $e) {
    error_log("Error en cron jobs: " . $e->getMessage());
}

/**
 * Verificar y ejecutar programaciones de riego
 */
function checkIrrigationSchedules() {
    global $pdo;
    
    $current_time = date('H:i:00');
    $current_day = date('N'); // 1 (lunes) a 7 (domingo)
    
    // Obtener programaciones activas para este momento
    $stmt = $pdo->prepare("
        SELECT s.*, d.device_id, d.name as device_name 
        FROM irrigation_schedules s 
        JOIN devices d ON s.device_id = d.device_id 
        WHERE s.active = 1 
        AND s.start_time = ?
        AND FIND_IN_SET(?, s.days_of_week) > 0
    ");
    
    $stmt->execute([$current_time, $current_day]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($schedules as $schedule) {
        try {
            // Ejecutar riego programado
            executeScheduledIrrigation($schedule);
            
            error_log("Riego programado ejecutado: " . $schedule['device_name'] . 
                     " - " . $schedule['duration'] . " segundos");
                     
        } catch (Exception $e) {
            error_log("Error ejecutando programación: " . $e->getMessage());
        }
    }
}

/**
 * Ejecutar riego programado
 */
function executeScheduledIrrigation($schedule) {
    global $pdo;
    
    // 1. Registrar en log de riego
    $stmt = $pdo->prepare("
        INSERT INTO irrigation_log (device_id, duration, mode, created_at) 
        VALUES (?, ?, 'schedule', NOW())
    ");
    $stmt->execute([$schedule['device_id'], $schedule['duration']]);
    
    // 2. Enviar comando al dispositivo (simulado)
    // En un sistema real, aquí enviarías el comando MQTT o HTTP al ESP32
    sendIrrigationCommand($schedule['device_id'], $schedule['duration']);
    
    // 3. Crear notificación para el usuario
    createIrrigationNotification($schedule);
}

/**
 * Enviar comando de riego al dispositivo
 */
function sendIrrigationCommand($device_id, $duration) {
    // Implementar envío real según tu protocolo (MQTT, HTTP, etc.)
    // Esta es una implementación simulada
    
    global $pdo;
    
    try {
        // Simular envío MQTT (debes implementar tu cliente MQTT real)
        $client = null;
        
        // Si tienes configuración MQTT, descomenta y adapta:
        /*
        $client = new Mosquitto\Client();
        $client->connect('localhost', 1883, 5);
        $client->publish('riego', json_encode([
            'device' => $device_id,
            'duration' => $duration,
            'mode' => 'schedule'
        ]));
        $client->disconnect();
        */
        
        // Actualizar estado en base de datos como fallback
        $stmt = $pdo->prepare("
            UPDATE sensor_data 
            SET pump_status = 1 
            WHERE device_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$device_id]);
        
    } catch (Exception $e) {
        error_log("Error enviando comando a dispositivo: " . $e->getMessage());
        
        // Crear alerta de error de comunicación
        createAlert(
            $device_id,
            'Error de Comunicación',
            "No se pudo enviar comando de riego programado al dispositivo",
            'high'
        );
    }
}

/**
 * Crear notificación de riego programado
 */
function createIrrigationNotification($schedule) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO alerts (device_id, title, message, priority) 
        VALUES (?, ?, ?, 'low')
    ");
    
    $title = "Riego Programado Ejecutado";
    $message = "Se ejecutó riego programado en {$schedule['device_name']} " .
               "por {$schedule['duration']} segundos";
    
    $stmt->execute([$schedule['device_id'], $title, $message]);
}

/**
 * Limpiar alertas antiguas
 */
function cleanOldAlerts() {
    global $pdo;
    
    // Eliminar alertas resueltas con más de 30 días
    $stmt = $pdo->prepare("
        DELETE FROM alerts 
        WHERE resolved = 1 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    
    $deleted = $stmt->rowCount();
    if ($deleted > 0) {
        error_log("Alertas antiguas eliminadas: " . $deleted);
    }
}
?>