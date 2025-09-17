<?php
require_once '../config/database.php';

// Marcar como offline dispositivos sin datos en 30 segundos
$stmt = $pdo->prepare("
    INSERT INTO sensor_data (device_id, online_status)
    SELECT device_id, 0 
    FROM devices 
    WHERE device_id NOT IN (
        SELECT device_id 
        FROM sensor_data 
        WHERE created_at > NOW() - INTERVAL 30 SECOND
    )
");
$stmt->execute();
?>
