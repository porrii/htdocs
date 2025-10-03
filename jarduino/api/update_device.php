<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Leer JSON enviado
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos no enviados']);
    exit();
}

// Extraer campos
$device_id        = trim($input['device_id'] ?? '');
$name             = trim($input['name'] ?? '');
$location         = trim($input['location'] ?? '');
$latitude         = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude        = isset($input['longitude']) ? floatval($input['longitude']) : null;
$auto_irrigation  = isset($input['auto_irrigation']) ? (int)$input['auto_irrigation'] : 0;
$threshold        = isset($input['soil_threshold']) ? floatval($input['soil_threshold']) : null;
$duration         = isset($input['irrigation_duration']) ? (int)$input['irrigation_duration'] : null;

// Validaciones
if (!$device_id || !$name) {
    echo json_encode(['success' => false, 'message' => 'El ID y nombre del dispositivo son obligatorios']);
    exit();
}

try {
    // Comprobar que el dispositivo existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT device_id FROM devices WHERE device_id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Dispositivo no encontrado o no tienes permisos']);
        exit();
    }

    // Actualizar tabla devices
    $stmt = $pdo->prepare("
        UPDATE devices 
        SET name = ?, location = ?, latitude = ?, longitude = ?, updated_at = NOW()
        WHERE device_id = ?
    ");
    $stmt->execute([$name, $location, $latitude, $longitude, $device_id]);

    // Actualizar o insertar configuración en device_config
    $stmt = $pdo->prepare("
        INSERT INTO device_config (device_id, auto_irrigation, threshold, duration)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            auto_irrigation = VALUES(auto_irrigation),
            threshold = VALUES(threshold),
            duration = VALUES(duration)
    ");
    $stmt->execute([$device_id, $auto_irrigation, $threshold, $duration]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
