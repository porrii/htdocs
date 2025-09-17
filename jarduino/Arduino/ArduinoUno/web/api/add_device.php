<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del cuerpo de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
$deviceId = $input['deviceId'] ?? '';
$deviceName = $input['deviceName'] ?? '';
$description = $input['description'] ?? '';

if (empty($deviceId) || empty($deviceName)) {
    echo json_encode(['success' => false, 'message' => 'ID y nombre del dispositivo son obligatorios']);
    exit();
}

// Añadir dispositivo
$success = addDevice($_SESSION['user_id'], $deviceId, $deviceName, $description);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Dispositivo añadido correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'El dispositivo ya existe o ocurrió un error']);
}
?>