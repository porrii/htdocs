<?php
/**
 * Script para测试 del sistema de cron jobs
 */
require_once '../config/database.php';
require_once 'system_monitor.php';

echo "=== Test del Sistema de Cron Jobs ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Test de programaciones
echo "1. Verificando programaciones de riego...\n";
$current_time = date('H:i:00');
$current_day = date('N');

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM irrigation_schedules 
    WHERE active = 1 
    AND start_time = ?
    AND FIND_IN_SET(?, days_of_week) > 0
");
$stmt->execute([$current_time, $current_day]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   Programaciones activas para ahora ($current_time, día $current_day): {$result['count']}\n";

// 2. Test de monitorización
echo "2. Ejecutando monitor del sistema...\n";
$monitor = new SystemMonitor($pdo);
$results = $monitor->runAllChecks();

echo "   - Dispositivos offline: {$results['offline_devices']}\n";
echo "   - Alertas ambientales: {$results['environmental_alerts']}\n";
echo "   - Alertas de uso de agua: {$results['water_usage_alerts']}\n";

// 3. Test de alertas existentes
echo "3. Verificando alertas existentes...\n";
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN resolved = 0 THEN 1 ELSE 0 END) as unresolved
    FROM alerts
");
$alerts = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   - Alertas totales: {$alerts['total']}\n";
echo "   - Alertas no resueltas: {$alerts['unresolved']}\n";

echo "\n=== Test completado ===\n";
?>