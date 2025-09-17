<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Enable output compression for better performance
enableOutputCompression();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Start session
session_start();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no válido');
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Token de seguridad inválido. Por favor, recarga la página e inténtalo de nuevo.');
    }

    // Sanitize and validate input
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $fecha = sanitizeInput($_POST['fecha'] ?? '');
    $hora = sanitizeInput($_POST['hora'] ?? '');
    $servicio = sanitizeInput($_POST['servicio'] ?? '');

    // Comprehensive validation
    $errors = [];

    // Validate required fields
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es obligatorio';
    } elseif (strlen($nombre) < 2) {
        $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
    } elseif (strlen($nombre) > 100) {
        $errors['nombre'] = 'El nombre no puede exceder 100 caracteres';
    }

    if (empty($email)) {
        $errors['email'] = 'El email es obligatorio';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'El formato del email no es válido';
    }

    if (empty($telefono)) {
        $errors['telefono'] = 'El teléfono es obligatorio';
    } elseif (!isValidPhone($telefono)) {
        $errors['telefono'] = 'El formato del teléfono no es válido';
    }

    if (empty($fecha)) {
        $errors['fecha'] = 'La fecha es obligatoria';
    } else {
        // Validate date format and that it's not in the past
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            $errors['fecha'] = 'Formato de fecha inválido';
        } else {
            $hoy = new DateTime();
            $hoy->setTime(0, 0, 0);
            if ($fechaObj < $hoy) {
                $errors['fecha'] = 'No se pueden reservar citas en fechas pasadas';
            }
        }
    }

    if (empty($hora)) {
        $errors['hora'] = 'La hora es obligatoria';
    } else {
        // Validate time format
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora)) {
            $errors['hora'] = 'Formato de hora inválido';
        }
    }

    if (empty($servicio)) {
        $errors['servicio'] = 'El servicio es obligatorio';
    } else {
        // Validate service against allowed values
        $serviciosPermitidos = [
            'Corte de Cabello',
            'Corte y Peinado',
            'Coloración',
            'Mechas',
            'Tratamiento Capilar',
            'Peinado Especial',
            'Asesoría de Imagen'
        ];
        
        if (!in_array($servicio, $serviciosPermitidos)) {
            $errors['servicio'] = 'Servicio no válido';
        }
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Por favor, corrige los errores en el formulario';
        echo json_encode($response);
        exit;
    }

    // Additional business logic validation
    if (!empty($fecha) && !empty($hora)) {
        // Check if the selected date/time is available
        $slotsDisponibles = getAvailableTimeSlots($fecha);
        $horaDisponible = false;
        
        foreach ($slotsDisponibles as $slot) {
            if ($slot['hora'] === $hora && $slot['disponible']) {
                $horaDisponible = true;
                break;
            }
        }
        
        if (!$horaDisponible) {
            throw new Exception('La hora seleccionada ya no está disponible. Por favor, elige otra hora.');
        }

        // Check for duplicate appointments (same email, date, and time)
        $citaExistente = $db->fetchOne(
            "SELECT id FROM citas WHERE email = ? AND fecha = ? AND hora = ? AND estado != 'cancelada'",
            [$email, $fecha, $hora]
        );
        
        if ($citaExistente) {
            throw new Exception('Ya tienes una cita reservada para esta fecha y hora.');
        }
    }

    // Create the appointment
    $citaId = crearCita($nombre, $email, $telefono, $fecha, $hora, $servicio);
    
    if (!$citaId) {
        throw new Exception('Error al procesar la reserva. Por favor, inténtalo de nuevo.');
    }

    // Success response
    $response['success'] = true;
    $response['message'] = 'Tu cita ha sido reservada correctamente. Te hemos enviado un email de confirmación.';
    $response['cita_id'] = $citaId;
    
    // Log successful appointment creation
    logError("Nueva cita creada - ID: $citaId, Email: $email, Fecha: $fecha, Hora: $hora", 'INFO');

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log the error
    logError("Error al procesar cita: " . $e->getMessage(), 'ERROR');
    
    // In production, you might want to show a generic error message
    // $response['message'] = 'Ha ocurrido un error. Por favor, inténtalo de nuevo más tarde.';
}

// Return JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
