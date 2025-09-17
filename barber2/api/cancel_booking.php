<?php
require_once '../config/database.php';
require_once '../classes/EmailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $bookingId = $input['booking_id'] ?? null;
    $email = $input['email'] ?? null;

    if (!$bookingId || !$email) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Verify booking exists and belongs to the email
        $stmt = $db->prepare("
            SELECT b.*, s.name as service_name 
            FROM appointments b 
            JOIN services s ON b.service_id = s.id 
            WHERE b.booking_id = ? AND b.client_email = ? AND b.status = 'confirmed'
        ");
        $stmt->execute([$bookingId, $email]);
        $booking = $stmt->fetch();

        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Cita no encontrada o ya cancelada']);
            exit;
        }

        // Check if booking is at least 2 hours in the future
        $bookingDateTime = new DateTime($booking['appointment_date'] . ' ' . $booking['appointment_time']);
        $now = new DateTime();
        $now->add(new DateInterval('PT2H')); // Add 2 hours

        if ($bookingDateTime <= $now) {
            echo json_encode(['success' => false, 'message' => 'No se puede cancelar con menos de 2 horas de anticipación']);
            exit;
        }

        // Cancel the booking
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$bookingId]);

        // Send cancellation email
        $emailService = new EmailService();
        $emailService->sendCancellationEmail($booking);

        echo json_encode(['success' => true, 'message' => 'Cita cancelada correctamente']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al cancelar la cita']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
