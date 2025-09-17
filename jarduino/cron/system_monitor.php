<?php
// Script para monitorizar el estado del sistema
// Este script puede ejecutarse periódicamente mediante cron

require_once '../config/database.php';

class SystemMonitor {
    private $pdo;
    private $alert_thresholds;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Umbrales para alertas
        $this->alert_thresholds = [
            'high_temperature' => 35, // °C
            'low_temperature' => 5,   // °C
            'low_moisture' => 20,     // %
            'device_offline' => 30,   // minutos
            'high_water_usage' => 300 // segundos por día
        ];
    }
    
    // Verificar dispositivos offline
    public function checkOfflineDevices() {
        $stmt = $this->pdo->prepare("
            SELECT d.device_id, d.name, MAX(sd.created_at) as last_seen
            FROM devices d
            LEFT JOIN sensor_data sd ON d.device_id = sd.device_id
            GROUP BY d.device_id, d.name
            HAVING last_seen IS NULL OR TIMESTAMPDIFF(MINUTE, last_seen, NOW()) > ?
        ");
        $stmt->execute([$this->alert_thresholds['device_offline']]);
        $offline_devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($offline_devices as $device) {
            $this->createAlert(
                $device['device_id'],
                'Dispositivo Offline',
                "El dispositivo {$device['name']} no ha reportado datos en más de {$this->alert_thresholds['device_offline']} minutos.",
                'high'
            );
        }
        
        return count($offline_devices);
    }
    
    // Verificar condiciones ambientales críticas
    public function checkEnvironmentalConditions() {
        $stmt = $this->pdo->prepare("
            SELECT sd.device_id, d.name, sd.temperature, sd.soil_moisture, sd.created_at
            FROM sensor_data sd
            JOIN devices d ON sd.device_id = d.device_id
            WHERE sd.created_at = (
                SELECT MAX(created_at) FROM sensor_data WHERE device_id = sd.device_id
            )
        ");
        $stmt->execute();
        $latest_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $alerts_created = 0;
        
        foreach ($latest_readings as $reading) {
            // Verificar temperatura alta
            if ($reading['temperature'] > $this->alert_thresholds['high_temperature']) {
                $this->createAlert(
                    $reading['device_id'],
                    'Temperatura Alta',
                    "Temperatura crítica ({$reading['temperature']}°C) en el dispositivo {$reading['name']}.",
                    'high'
                );
                $alerts_created++;
            }
            
            // Verificar temperatura baja
            if ($reading['temperature'] < $this->alert_thresholds['low_temperature']) {
                $this->createAlert(
                    $reading['device_id'],
                    'Temperatura Baja',
                    "Temperatura crítica ({$reading['temperature']}°C) en el dispositivo {$reading['name']}.",
                    'high'
                );
                $alerts_created++;
            }
            
            // Verificar humedad baja
            if ($reading['soil_moisture'] < $this->alert_thresholds['low_moisture']) {
                $this->createAlert(
                    $reading['device_id'],
                    'Humedad Baja',
                    "Humedad del suelo crítica ({$reading['soil_moisture']}%) en el dispositivo {$reading['name']}.",
                    'medium'
                );
                $alerts_created++;
            }
        }
        
        return $alerts_created;
    }
    
    // Verificar uso excesivo de agua
    public function checkWaterUsage() {
        $stmt = $this->pdo->prepare("
            SELECT device_id, SUM(duration) as total_duration
            FROM irrigation_log
            WHERE DATE(created_at) = CURDATE()
            GROUP BY device_id
            HAVING total_duration > ?
        ");
        $stmt->execute([$this->alert_thresholds['high_water_usage']]);
        $high_usage_devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($high_usage_devices as $device) {
            $stmt = $this->pdo->prepare("SELECT name FROM devices WHERE device_id = ?");
            $stmt->execute([$device['device_id']]);
            $device_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->createAlert(
                $device['device_id'],
                'Uso Excesivo de Agua',
                "El dispositivo {$device_info['name']} ha usado {$device['total_duration']} segundos de agua hoy, superando el umbral de {$this->alert_thresholds['high_water_usage']} segundos.",
                'medium'
            );
        }
        
        return count($high_usage_devices);
    }
    
    // Crear una alerta en la base de datos
    private function createAlert($device_id, $title, $message, $priority = 'medium') {
        // Verificar si ya existe una alerta similar no resuelta
        $stmt = $this->pdo->prepare("
            SELECT id FROM alerts 
            WHERE device_id = ? AND title = ? AND resolved = 0 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$device_id, $title]);
        
        if ($stmt->rowCount() == 0) {
            $stmt = $this->pdo->prepare("
                INSERT INTO alerts (device_id, title, message, priority) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$device_id, $title, $message, $priority]);
            
            // Opcional: enviar notificación por email
            $this->sendEmailNotification($device_id, $title, $message);
            
            return true;
        }
        
        return false;
    }
    
    // Enviar notificación por email
    private function sendEmailNotification($device_id, $title, $message) {
        // Obtener información del propietario del dispositivo
        $stmt = $this->pdo->prepare("
            SELECT u.email, u.username, d.name as device_name
            FROM devices d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.device_id = ?
        ");
        $stmt->execute([$device_id]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($owner) {
            $to = $owner['email'];
            $subject = "SmartGarden Alerta: $title";
            $body = "
                Hola {$owner['username']},
                
                Has recibido una alerta de tu dispositivo {$owner['device_name']}:
                
                $message
                
                Por favor, inicia sesión en tu panel de SmartGarden para más detalles.
                
                Saludos,
                Equipo SmartGarden
            ";
            
            // En un entorno real, usarías una librería de email como PHPMailer
            // mail($to, $subject, $body);
            
            error_log("Alerta enviada a: $to - Asunto: $subject");
        }
    }
    
    // Ejecutar todas las verificaciones
    public function runAllChecks() {
        $results = [
            'offline_devices' => $this->checkOfflineDevices(),
            'environmental_alerts' => $this->checkEnvironmentalConditions(),
            'water_usage_alerts' => $this->checkWaterUsage()
        ];
        
        return $results;
    }
}

// Ejecutar el monitor si este script se llama directamente
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0])) {
    require_once 'config/database.php';
    $monitor = new SystemMonitor($pdo);
    $results = $monitor->runAllChecks();
    
    echo "Monitor del Sistema ejecutado:\n";
    echo "- Dispositivos offline: {$results['offline_devices']}\n";
    echo "- Alertas ambientales: {$results['environmental_alerts']}\n";
    echo "- Alertas de uso de agua: {$results['water_usage_alerts']}\n";
}
?>