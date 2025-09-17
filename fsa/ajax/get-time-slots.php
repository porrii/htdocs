<?php
// Configurar headers para JSON y evitar salida HTML
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}

// Deshabilitar mostrar errores para evitar HTML en respuesta JSON
ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/functions.php';

    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Leer datos JSON del cuerpo de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si no hay datos JSON, intentar con POST tradicional
    if (!$input) {
        $input = $_POST;
    }

    // Verificar que se envió la fecha
    if (!isset($input['fecha']) || empty($input['fecha'])) {
        throw new Exception('Fecha no proporcionada');
    }

    $fecha = $input['fecha'];

    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new Exception('Formato de fecha inválido');
    }

    // Verificar que la fecha no sea en el pasado (comparar solo fechas, no horas)
    $fechaSeleccionada = new DateTime($fecha);
    $fechaHoy = new DateTime();
    $fechaHoy->setTime(0, 0, 0); // Resetear hora para comparar solo fechas
    $fechaSeleccionada->setTime(0, 0, 0);

    if ($fechaSeleccionada < $fechaHoy) {
        throw new Exception('No se pueden reservar citas en fechas pasadas');
    }

    // Obtener horarios disponibles
    $timeSlots = getAvailableTimeSlots($fecha);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'slots' => $timeSlots,
        'fecha' => $fecha,
        'message' => count($timeSlots) > 0 ? 'Horarios cargados correctamente' : 'No hay horarios disponibles para esta fecha'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Respuesta de error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'slots' => []
    ], JSON_UNESCAPED_UNICODE);
}

// Asegurar que no hay salida adicional
exit;
?>
