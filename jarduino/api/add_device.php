<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Leer datos JSON enviados desde JS
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos no enviados']);
    exit();
}

// Extraer variables
$device_id = trim($input['device_id'] ?? '');
$user_id = trim($input['user_id'] ?? '');
$name = trim($input['name'] ?? '');
// $description = trim($input['description'] ?? '');
$location = trim($input['location'] ?? '');
$latitude = trim($input['latitude'] ?? '');
$longitude = trim($input['longitude'] ?? '');

// Validación básica
if (!$device_id || !$name) {
    echo json_encode(['success' => false, 'message' => 'El ID y nombre del dispositivo son obligatorios']);
    exit();
}

// Validar formato del ID (igual que JS)
if (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $device_id)) {
    echo json_encode(['success' => false, 'message' => 'El ID del dispositivo debe tener entre 3 y 20 caracteres y solo puede contener letras, números, guiones y guiones bajos.']);
    exit();
}

try {
    // Comprobar si el device_id ya existe
    $stmt = $pdo->prepare("SELECT device_id FROM devices WHERE device_id = ?");
    $stmt->execute([$device_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un dispositivo con ese ID']);
        exit();
    }

    // Insertar nuevo dispositivo
    $stmt = $pdo->prepare("
        INSERT INTO devices (device_id, user_id, name, location, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$device_id, $user_id, $name, $location, $latitude, $longitude]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
