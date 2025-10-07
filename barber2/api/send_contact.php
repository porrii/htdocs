<?php
require_once '../classes/EmailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $nombre = $input['nombre'] ?? '';
    $email = $input['email'] ?? '';
    $mensaje = $input['mensaje'] ?? '';

    if (empty($nombre) || empty($email) || empty($mensaje)) {
        error_log("❌ Faltan datos obligatorios");
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    try {
        $emailService = new EmailService();

        // Llama al nuevo método público de EmailService
        $sent = $emailService->sendContactEmail($nombre, $email, $mensaje);

        if ($sent) {
            echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo enviar el mensaje']);
        }

    } catch (Exception $e) {
        error_log("🔥 Error en send_contact.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
