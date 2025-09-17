<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/EmailService.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!$input['service_id'] || !$input['appointment_date'] || !$input['appointment_time'] || 
        !$input['client_name'] || !$input['client_email']) {
        throw new Exception('Faltan campos requeridos');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if slot is still available
    $query = "SELECT COUNT(*) as count FROM appointments 
              WHERE appointment_date = :date AND appointment_time = :time AND status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date', $input['appointment_date']);
    $stmt->bindParam(':time', $input['appointment_time']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        throw new Exception('Este horario ya no está disponible');
    }
    
    // Generate unique booking_id
    do {
        $bookingId = strtoupper(bin2hex(random_bytes(4))); // Ej: 8 caracteres hexadecimales
        $checkQuery = "SELECT COUNT(*) as count FROM appointments WHERE booking_id = :booking_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':booking_id', $bookingId);
        $checkStmt->execute();
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    } while ($exists);
    
    // Insert appointment
    $query = "INSERT INTO appointments (service_id, appointment_date, appointment_time, client_name, client_email, client_phone, notes, status, booking_id) 
              VALUES (:service_id, :appointment_date, :appointment_time, :client_name, :client_email, :client_phone, :notes, 'confirmed', :booking_id)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':service_id', $input['service_id']);
    $stmt->bindParam(':appointment_date', $input['appointment_date']);
    $stmt->bindParam(':appointment_time', $input['appointment_time']);
    $stmt->bindParam(':client_name', $input['client_name']);
    $stmt->bindParam(':client_email', $input['client_email']);
    $stmt->bindParam(':client_phone', $input['client_phone']);
    $stmt->bindParam(':notes', $input['notes']);
    $stmt->bindParam(':booking_id', $bookingId);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al crear la cita');
    }
    
    $appointmentId = $db->lastInsertId();
    
    // Get appointment details with service info for email
    $query = "SELECT a.*, s.name as service_name FROM appointments a 
              JOIN services s ON a.service_id = s.id 
              WHERE a.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $appointmentId);
    $stmt->execute();
    $appointmentData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $db->commit();
    
    // Send confirmation email
    try {
        $emailService = new EmailService();
        $emailService->sendConfirmationEmail($appointmentData);
    } catch (Exception $e) {
        // Log email error but don't fail the booking
        error_log("Error sending confirmation email: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'appointment_id' => $appointmentId,
        'booking_id' => $bookingId,
        'message' => 'Cita creada exitosamente'
    ]);
    
} catch(Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
