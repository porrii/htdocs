<?php 
// Carga manual de las clases de PHPMailer 
require 'phpmailer/src/Exception.php'; 
require 'phpmailer/src/PHPMailer.php'; 
require 'phpmailer/src/SMTP.php';  

use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception;

// Get featured services
function getFeaturedServices($limit = 6) {
    global $db;
    return $db->fetchAll("SELECT * FROM servicios WHERE activo = 1 ORDER BY orden ASC LIMIT ?", [$limit]);
}

// Get featured services for footer
function getServiciosActivos($limite = 6) {
    global $db;
    return $db->fetchAll("SELECT nombre FROM servicios WHERE activo = 1 ORDER BY orden ASC LIMIT ?", [$limite]);
}

// Get featured products
function getFeaturedProducts($limit = 3) {
    global $db;

    // Ruta de cache
    $cache_file = __DIR__ . '/../cache/featured_products.json';

    // Verificar si existe cache y no tiene más de 5 minutos
    if(file_exists($cache_file) && time() - filemtime($cache_file) < 300){
        $productos = json_decode(file_get_contents($cache_file), true);
        return $productos;
    }

    // Consulta optimizada: solo columnas necesarias
    $productos = $db->fetchAll(
        "SELECT id, nombre, descripcion, imagen FROM productos WHERE activo = 1 ORDER BY id DESC LIMIT ?",
        [$limit]
    );

    // Guardar cache
    file_put_contents($cache_file, json_encode($productos));

    return $productos;
}

// Create thumbnail for product images
// Crear miniatura de imágenes de productos
function getThumbnail($imagen, $width = 400, $height = 300) {
    // $imagen viene como "uploads/productos/685269faa8ffe.png"
    $originalPath = __DIR__ . '/../' . $imagen; // ruta completa en el servidor
    $thumbDir = __DIR__ . '/../thumbnails/';    // carpeta de miniaturas
    $filename = basename($imagen);               // extrae solo "685269faa8ffe.png"
    $thumbPath = $thumbDir . $filename;

    // Verificar que exista la imagen original
    if (!file_exists($originalPath)) {
        die("Archivo original no encontrado: $originalPath");
    }

    // Crear carpeta thumbnails si no existe
    if (!file_exists($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }

    // Si la miniatura ya existe, devolverla
    if (file_exists($thumbPath)) {
        return 'thumbnails/' . $filename;
    }

    // Obtener tamaño y tipo de imagen
    list($origWidth, $origHeight, $type) = getimagesize($originalPath);

    // Crear recurso según tipo
    switch ($type) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($originalPath); break;
        case IMAGETYPE_PNG: $image = imagecreatefrompng($originalPath); break;
        case IMAGETYPE_GIF: $image = imagecreatefromgif($originalPath); break;
        default: return '';
    }

    // Crear lienzo para miniatura
    $thumb = imagecreatetruecolor($width, $height);

    // Preservar transparencia PNG/GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    // Redimensionar imagen
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

    // Guardar miniatura
    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $thumbPath, 85); break;
        case IMAGETYPE_PNG: imagepng($thumb, $thumbPath); break;
        case IMAGETYPE_GIF: imagegif($thumb, $thumbPath); break;
    }

    imagedestroy($image);
    imagedestroy($thumb);

    return 'thumbnails/' . $filename;
}

// Get all products
function getAllProducts() {
    global $db;
    return $db->fetchAll("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
}

// Get featured videos
function getFeaturedVideos($limit = 2) {
    global $db;
    return $db->fetchAll("
        SELECT v.*, c.nombre as categoria_nombre 
        FROM videos v 
        LEFT JOIN categorias_videos c ON v.categoria_id = c.id 
        WHERE v.activo = 1 
        ORDER BY v.id DESC 
        LIMIT ?
    ", [$limit]);
}

// Get all videos with categories
function getAllVideos() {
    global $db;
    return $db->fetchAll("
        SELECT v.*, c.nombre as categoria_nombre 
        FROM videos v 
        LEFT JOIN categorias_videos c ON v.categoria_id = c.id 
        WHERE v.activo = 1 
        ORDER BY v.id DESC
    ");
}

// Get videos by category
function getVideosByCategory($categoria_id = null) {
    global $db;
    
    if ($categoria_id) {
        return $db->fetchAll("
            SELECT v.*, c.nombre as categoria_nombre 
            FROM videos v 
            LEFT JOIN categorias_videos c ON v.categoria_id = c.id 
            WHERE v.activo = 1 AND v.categoria_id = ?
            ORDER BY v.id DESC
        ", [$categoria_id]);
    } else {
        return getAllVideos();
    }
}

// Get video categories
function getCategoriesVideos() {
    global $db;
    return $db->fetchAll("SELECT * FROM categorias_videos WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
}

// Get all services
function getAllServicios() {
    global $db;
    return $db->fetchAll("SELECT * FROM servicios WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
}

// Get featured services
function getFeaturedServicios($limit = 6) {
    global $db;
    return $db->fetchAll("SELECT * FROM servicios WHERE activo = 1 ORDER BY orden ASC, nombre ASC LIMIT ?", [$limit]);
}

// Get business hours (simple version for display)
function getHorarios() {
    global $db;
    $result = $db->fetchAll("SELECT id_dia, dia, hora_apertura, hora_cierre, cerrado, horario_partido, hora_apertura_tarde, hora_cierre_tarde FROM horarios ORDER BY id_dia ASC");
    $horarios = [];

    foreach ($result as $row) {
        if ($row['cerrado']) {
            $horarios[$row['dia']] = [
                'apertura' => 'Cerrado',
                'cierre' => 'Cerrado'
            ];
        } else if ($row['horario_partido']) {
            $apertura_manana = substr($row['hora_apertura'], 0, 5);
            $cierre_manana = substr($row['hora_cierre'], 0, 5);
            $apertura_tarde = substr($row['hora_apertura_tarde'], 0, 5);
            $cierre_tarde = substr($row['hora_cierre_tarde'], 0, 5);
            
            $horarios[$row['dia']] = [
                'apertura' => $apertura_manana . '-' . $cierre_manana,
                'cierre' => $apertura_tarde . '-' . $cierre_tarde
            ];
        } else {
            $apertura = substr($row['hora_apertura'], 0, 5);
            $cierre = substr($row['hora_cierre'], 0, 5);
            
            $horarios[$row['dia']] = [
                'apertura' => $apertura,
                'cierre' => $cierre
            ];
        }
    }

    return $horarios;
}

// Get complete business hours (for admin)
function getHorariosCompletos() {
    global $db;
    $result = $db->fetchAll("SELECT * FROM horarios ORDER BY id_dia ASC");
    $horarios = [];

    foreach ($result as $row) {
        $horarios[$row['dia']] = [
            'cerrado' => (bool)$row['cerrado'],
            'horario_partido' => (bool)$row['horario_partido'],
            'hora_apertura' => $row['hora_apertura'],
            'hora_cierre' => $row['hora_cierre'],
            'hora_apertura_tarde' => $row['hora_apertura_tarde'],
            'hora_cierre_tarde' => $row['hora_cierre_tarde']
        ];
    }

    return $horarios;
}

// Get available time slots for a specific date - UPDATED to filter past times
function getAvailableTimeSlots($fecha) {
    global $db;

    // Get day of week
    $diaSemana = date('l', strtotime($fecha));
    $diasEspanol = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    $dia = $diasEspanol[$diaSemana];

    // Get business hours for that day
    $horarioQuery = $db->fetchOne("SELECT * FROM horarios WHERE dia = ?", [$dia]);

    // If closed that day or no data found, return empty array
    if (!$horarioQuery || $horarioQuery['cerrado']) {
        return [];
    }

    $slots = [];
    $intervalo = TIME_SLOT_DURATION * 60; // Convert to seconds

    // Generate morning/main schedule slots
    $horaInicio = strtotime($horarioQuery['hora_apertura']);
    $horaFin = strtotime($horarioQuery['hora_cierre']);

    for ($hora = $horaInicio; $hora < $horaFin; $hora += $intervalo) {
        $slots[] = date('H:i', $hora);
    }

    // If it's a split schedule, add afternoon slots
    if ($horarioQuery['horario_partido'] && $horarioQuery['hora_apertura_tarde'] && $horarioQuery['hora_cierre_tarde']) {
        $horaInicioTarde = strtotime($horarioQuery['hora_apertura_tarde']);
        $horaFinTarde = strtotime($horarioQuery['hora_cierre_tarde']);
        
        for ($hora = $horaInicioTarde; $hora < $horaFinTarde; $hora += $intervalo) {
            $slots[] = date('H:i', $hora);
        }
    }

    // Sort slots chronologically
    sort($slots);

    // Filter past times if it's today
    $hoy = date('Y-m-d');
    if ($fecha === $hoy) {
        $horaActual = date('H:i');
        $slots = array_filter($slots, function($slot) use ($horaActual) {
            return $slot > $horaActual;
        });
    }

    // Get booked appointments for that date
    $citas = $db->fetchAll("SELECT hora FROM citas WHERE fecha = ? AND estado != 'cancelada'", [$fecha]);
    $horasOcupadas = [];

    foreach ($citas as $cita) {
        $horasOcupadas[] = substr($cita['hora'], 0, 5);
    }

    // Check availability for each slot
    $disponibles = [];
    foreach ($slots as $slot) {
        $disponibles[] = [
            'hora' => $slot,
            'disponible' => !in_array($slot, $horasOcupadas)
        ];
    }

    return $disponibles;
}

// Create new appointment - UPDATED to create as 'confirmada'
function crearCita($nombre, $email, $telefono, $fecha, $hora, $servicio) {
    global $db;

    // Insert appointment into database with 'confirmada' status
    $id = $db->insert(
        "INSERT INTO citas (nombre, email, telefono, fecha, hora, servicio, estado, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, 'confirmada', NOW())",
        [$nombre, $email, $telefono, $fecha, $hora, $servicio]
    );

    // Send confirmation email
    if ($id) {
        enviarEmailConfirmacion($nombre, $email, $fecha, $hora, $servicio, $id);
    }

    return $id;
}

// Auto-complete appointments - NEW FUNCTION
function completarCitasAutomaticamente() {
    global $db;
    
    $ahora = new DateTime();
    $fechaActual = $ahora->format('Y-m-d');
    $horaActual = $ahora->format('H:i:s');
    
    // Get appointments that should be completed (30 minutes after their scheduled time)
    $citas = $db->fetchAll("
        SELECT * FROM citas 
        WHERE estado = 'confirmada' 
        AND (
            fecha < ? 
            OR (fecha = ? AND TIME(hora) <= TIME(DATE_SUB(?, INTERVAL 30 MINUTE)))
        )
    ", [$fechaActual, $fechaActual, $horaActual]);
    
    foreach ($citas as $cita) {
        $db->update(
            "UPDATE citas SET estado = 'completada', fecha_actualizacion = NOW() WHERE id = ?",
            [$cita['id']]
        );
    }
    
    return count($citas);
}

// Cancel appointment
function cancelarCita($id, $email) {
    global $db;

    // Update appointment status in database
    $result = $db->update(
        "UPDATE citas SET estado = 'cancelada', fecha_actualizacion = NOW() WHERE id = ? AND email = ?",
        [$id, $email]
    );

    // Send cancellation email
    if ($result) {
        // Get appointment details
        $cita = $db->fetchOne("SELECT * FROM citas WHERE id = ?", [$id]);
        
        if ($cita) {
            enviarEmailCancelacion($cita['email'], $id);
        }
    }

    return $result > 0;
}

// Update appointment status
function actualizarEstadoCita($id, $estado) {
    global $db;

    // Update appointment status in database
    $result = $db->update(
        "UPDATE citas SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?",
        [$estado, $id]
    );

    // Send status update email
    if ($result) {
        // Get appointment details
        $cita = $db->fetchOne("SELECT * FROM citas WHERE id = ?", [$id]);
        
        if ($cita) {
            enviarEmailActualizacion($cita['email'], $id, $estado, $cita['fecha'], $cita['hora']);
        }
    }

    return $result > 0;
}

// Get appointment by ID
function getCitaPorId($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM citas WHERE id = ?", [$id]);
}

// Get appointments by date
function getCitasPorFecha($fecha) {
    global $db;
    return $db->fetchAll("SELECT * FROM citas WHERE fecha = ? ORDER BY hora ASC", [$fecha]);
}

// Get appointments by status
function getCitasPorEstado($estado) {
    global $db;
    return $db->fetchAll("SELECT * FROM citas WHERE estado = ? ORDER BY fecha ASC, hora ASC", [$estado]);
}

// Get all appointments with optional filters
function getAllCitas($filtros = []) {
    global $db;

    $sql = "SELECT * FROM citas WHERE 1=1";
    $params = [];

    if (isset($filtros['estado']) && !empty($filtros['estado'])) {
        $sql .= " AND estado = ?";
        $params[] = $filtros['estado'];
    }

    if (isset($filtros['fecha']) && !empty($filtros['fecha'])) {
        $sql .= " AND fecha = ?";
        $params[] = $filtros['fecha'];
    }

    if (isset($filtros['buscar']) && !empty($filtros['buscar'])) {
        $sql .= " AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
        $buscar = "%" . $filtros['buscar'] . "%";
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
    }

    $sql .= " ORDER BY fecha ASC, hora ASC";

    return $db->fetchAll($sql, $params);
}

// Services management functions
function agregarServicio($nombre, $descripcion, $icono, $duracion, $precio) {
    global $db;
    
    // Get next order number
    $maxOrden = $db->fetchValue("SELECT MAX(orden) FROM servicios") ?: 0;
    
    return $db->insert(
        "INSERT INTO servicios (nombre, descripcion, icono, duracion, precio, orden, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())",
        [$nombre, $descripcion, $icono, $duracion, $precio, $maxOrden + 1]
    );
}

function actualizarServicio($id, $nombre, $descripcion, $icono, $duracion, $precio) {
    global $db;
    
    return $db->update(
        "UPDATE servicios SET nombre = ?, descripcion = ?, icono = ?, duracion = ?, precio = ?, fecha_actualizacion = NOW() WHERE id = ?",
        [$nombre, $descripcion, $icono, $duracion, $precio, $id]
    );
}

function eliminarServicio($id) {
    global $db;
    
    return $db->update(
        "UPDATE servicios SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?",
        [$id]
    );
}

function reordenarServicios($servicios_orden) {
    global $db;
    
    foreach ($servicios_orden as $id => $orden) {
        $db->update(
            "UPDATE servicios SET orden = ? WHERE id = ?",
            [$orden, $id]
        );
    }
    
    return true;
}

// Video categories management functions
function agregarCategoriaVideo($nombre, $descripcion) {
    global $db;
    
    // Get next order number
    $maxOrden = $db->fetchValue("SELECT MAX(orden) FROM categorias_videos") ?: 0;
    
    return $db->insert(
        "INSERT INTO categorias_videos (nombre, descripcion, orden, fecha_creacion) VALUES (?, ?, ?, NOW())",
        [$nombre, $descripcion, $maxOrden + 1]
    );
}

function actualizarCategoriaVideo($id, $nombre, $descripcion) {
    global $db;
    
    return $db->update(
        "UPDATE categorias_videos SET nombre = ?, descripcion = ? WHERE id = ?",
        [$nombre, $descripcion, $id]
    );
}

function eliminarCategoriaVideo($id) {
    global $db;
    
    // First, set videos in this category to NULL
    $db->update("UPDATE videos SET categoria_id = NULL WHERE categoria_id = ?", [$id]);
    
    return $db->update(
        "UPDATE categorias_videos SET activo = 0 WHERE id = ?",
        [$id]
    );
}

// Send confirmation email
function enviarEmailConfirmacion($nombre, $email, $fecha, $hora, $servicio, $id) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($email, $nombre);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Confirmación de Cita - ' . SITE_NAME;
        
        // Format date - CORREGIDO para mostrar la fecha correcta
        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        $diaSemana = date('l', strtotime($fecha));
        $diasEspanol = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes', 
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        $diaFormateado = $diasEspanol[$diaSemana] . ', ' . $fechaFormateada;
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
            <h2 style="color: #667eea; text-align: center;">¡Tu cita ha sido confirmada!</h2>
            <p>Hola <strong>' . $nombre . '</strong>,</p>
            <p>Gracias por reservar una cita con nosotros. A continuación, te mostramos los detalles de tu reserva:</p>
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p><strong>Número de Cita:</strong> ' . $id . '</p>
                <p><strong>Fecha:</strong> ' . $diaFormateado . '</p>
                <p><strong>Hora:</strong> ' . $hora . '</p>
                <p><strong>Servicio:</strong> ' . $servicio . '</p>
            </div>
            <p>Si necesitas cancelar tu cita, puedes hacerlo a través de nuestra página web utilizando tu correo electrónico y el número de cita.</p>
            <p>¡Esperamos verte pronto!</p>
            <p style="text-align: center; margin-top: 30px; color: #666;">
                <small>Este es un correo automático, por favor no respondas a este mensaje.</small>
            </p>
        </div>
        ';
        
        $mail->AltBody = '
        ¡Tu cita ha sido confirmada!
        
        Hola ' . $nombre . ',
        
        Gracias por reservar una cita con nosotros. A continuación, te mostramos los detalles de tu reserva:
        
        Número de Cita: ' . $id . '
        Fecha: ' . $diaFormateado . '
        Hora: ' . $hora . '
        Servicio: ' . $servicio . '
        
        Si necesitas cancelar tu cita, puedes hacerlo a través de nuestra página web utilizando tu correo electrónico y el número de cita.
        
        ¡Esperamos verte pronto!
        
        Este es un correo automático, por favor no respondas a este mensaje.
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending confirmation email: " . $mail->ErrorInfo);
        return false;
    }
}

// Send cancellation email
function enviarEmailCancelacion($email, $id) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Cita Cancelada - ' . SITE_NAME;
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
            <h2 style="color: #667eea; text-align: center;">Cita Cancelada</h2>
            <p>Hola,</p>
            <p>Tu cita con número <strong>' . $id . '</strong> ha sido cancelada correctamente.</p>
            <p>Si deseas reservar una nueva cita, puedes hacerlo a través de nuestra página web.</p>
            <p>¡Esperamos verte pronto!</p>
            <p style="text-align: center; margin-top: 30px; color: #666;">
                <small>Este es un correo automático, por favor no respondas a este mensaje.</small>
            </p>
        </div>
        ';
        
        $mail->AltBody = '
        Cita Cancelada
        
        Hola,
        
        Tu cita con número ' . $id . ' ha sido cancelada correctamente.
        
        Si deseas reservar una nueva cita, puedes hacerlo a través de nuestra página web.
        
        ¡Esperamos verte pronto!
        
        Este es un correo automático, por favor no respondas a este mensaje.
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending cancellation email: " . $mail->ErrorInfo);
        return false;
    }
}

// Send status update email
function enviarEmailActualizacion($email, $id, $estado, $fecha, $hora) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        
        // Format date
        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        
        // Different subject and content based on status
        if ($estado === 'confirmada') {
            $mail->Subject = 'Cita Confirmada - ' . SITE_NAME;
            
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
                <h2 style="color: #667eea; text-align: center;">¡Tu cita ha sido confirmada!</h2>
                <p>Hola,</p>
                <p>Nos complace informarte que tu cita con número <strong>' . $id . '</strong> ha sido confirmada.</p>
                <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>Fecha:</strong> ' . $fechaFormateada . '</p>
                    <p><strong>Hora:</strong> ' . $hora . '</p>
                </div>
                <p>¡Esperamos verte pronto!</p>
                <p style="text-align: center; margin-top: 30px; color: #666;">
                    <small>Este es un correo automático, por favor no respondas a este mensaje.</small>
                </p>
            </div>
            ';
            
            $mail->AltBody = '
            ¡Tu cita ha sido confirmada!
            
            Hola,
            
            Nos complace informarte que tu cita con número ' . $id . ' ha sido confirmada.
            
            Fecha: ' . $fechaFormateada . '
            Hora: ' . $hora . '
            
            ¡Esperamos verte pronto!
            
            Este es un correo automático, por favor no respondas a este mensaje.
            ';
        } else {
            $mail->Subject = 'Cita Cancelada - ' . SITE_NAME;
            
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
                <h2 style="color: #667eea; text-align: center;">Cita Cancelada</h2>
                <p>Hola,</p>
                <p>Lamentamos informarte que tu cita con número <strong>' . $id . '</strong> programada para el día <strong>' . $fechaFormateada . '</strong> a las <strong>' . $hora . '</strong> ha sido cancelada.</p>
                <p>Si deseas reservar una nueva cita, puedes hacerlo a través de nuestra página web.</p>
                <p>Disculpa las molestias ocasionadas.</p>
                <p style="text-align: center; margin-top: 30px; color: #666;">
                    <small>Este es un correo automático, por favor no respondas a este mensaje.</small>
                </p>
            </div>
            ';
            
            $mail->AltBody = '
            Cita Cancelada
            
            Hola,
            
            Lamentamos informarte que tu cita con número ' . $id . ' programada para el día ' . $fechaFormateada . ' a las ' . $hora . ' ha sido cancelada.
            
            Si deseas reservar una nueva cita, puedes hacerlo a través de nuestra página web.
            
            Disculpa las molestias ocasionadas.
            
            Este es un correo automático, por favor no respondas a este mensaje.
            ';
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending status update email: " . $mail->ErrorInfo);
        return false;
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../admin-login.php');
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > SESSION_TIMEOUT)) {
        // Session expired
        session_unset();
        session_destroy();
        header('Location: ../admin-login.php?expired=1');
        exit;
    }

    // Update last activity time
    $_SESSION['admin_last_activity'] = time();
}

// Sanitize input - FIXED for PHP 8+
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
        return $input;
    }
    
    // Eliminar espacios en blanco al inicio y final
    $input = trim($input);
    
    // Eliminar barras invertidas (solo si magic quotes está habilitado - PHP < 5.4)
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $input = stripslashes($input);
    }
    
    // Convertir caracteres especiales a entidades HTML
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Verificar si el token ha expirado
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validación de email mejorada
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && 
           preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $email);
}

/**
 * Validación de teléfono mejorada
 */
function isValidPhone($phone) {
    // Eliminar caracteres no numéricos para la validación
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Debe tener entre 9 y 15 dígitos (estándar internacional)
    return preg_match('/^[+]?[0-9]{9,15}$/', $cleanPhone);
}

/**
 * Compresión de salida para mejorar rendimiento
 */
function enableOutputCompression() {
    // Verificar si la compresión ya está activada
    if (ini_get('zlib.output_compression') !== '1' && 
        !in_array('ob_gzhandler', ob_list_handlers())) {
        ob_start('ob_gzhandler');
    }
}

/**
 * Optimiza las imágenes subidas
 */
function optimizeImage($source, $destination, $quality = 85) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $destination, round(9 - ($quality/10)));
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
        imagegif($image, $destination);
    }
    
    return $destination;
}

/**
 * Genera URLs amigables para SEO
 */
function slugify($text) {
    // Reemplazar caracteres no alfanuméricos con guiones
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // Transliterar
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    
    // Eliminar caracteres indeseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    // Recortar
    $text = trim($text, '-');
    
    // Eliminar guiones duplicados
    $text = preg_replace('~-+~', '-', $text);
    
    // Convertir a minúsculas
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Función para registrar errores de manera más detallada
 */
function logError($message, $severity = 'ERROR', $file = null, $line = null) {
    $logFile = dirname(__DIR__) . '/logs/error.log';
    $logDir = dirname($logFile);
    
    // Crear directorio de logs si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$severity] $message";
    
    if ($file && $line) {
        $logMessage .= " in $file on line $line";
    }
    
    $logMessage .= PHP_EOL;
    
    error_log($logMessage, 3, $logFile);
}

/**
 * Función para manejar errores de manera más elegante
 */
function handleError($errno, $errstr, $errfile, $errline) {
    $severity = match($errno) {
        E_ERROR, E_USER_ERROR => 'FATAL',
        E_WARNING, E_USER_WARNING => 'WARNING',
        E_NOTICE, E_USER_NOTICE => 'NOTICE',
        default => 'UNKNOWN'
    };
    
    logError($errstr, $severity, $errfile, $errline);
    
    // Para errores fatales, mostrar página de error amigable
    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        ob_clean();
        include dirname(__DIR__) . '/error.php';
        exit(1);
    }
    
    return true; // Permitir que PHP maneje el error también
}

// Registrar manejador de errores personalizado
set_error_handler('handleError');

// Update business hours (updated for split schedules)
function actualizarHorarios($horarios) {
    global $db;

    foreach ($horarios as $dia => $datos) {
        $cerrado = $datos['cerrado'] ? 1 : 0;
        $horario_partido = $datos['horario_partido'] ? 1 : 0;
        
        $apertura = $cerrado ? NULL : $datos['hora_apertura'];
        $cierre = $cerrado ? NULL : $datos['hora_cierre'];
        $apertura_tarde = ($cerrado || !$horario_partido) ? NULL : $datos['hora_apertura_tarde'];
        $cierre_tarde = ($cerrado || !$horario_partido) ? NULL : $datos['hora_cierre_tarde'];
        
        $db->update(
            "UPDATE horarios SET hora_apertura = ?, hora_cierre = ?, cerrado = ?, horario_partido = ?, hora_apertura_tarde = ?, hora_cierre_tarde = ? WHERE dia = ?",
            [$apertura, $cierre, $cerrado, $horario_partido, $apertura_tarde, $cierre_tarde, $dia]
        );
    }

    return true;
}

// Add product
function agregarProducto($nombre, $descripcion, $precio, $imagen) {
    global $db;

    return $db->insert(
        "INSERT INTO productos (nombre, descripcion, precio, imagen, activo, fecha_creacion) VALUES (?, ?, ?, ?, 1, NOW())",
        [$nombre, $descripcion, $precio, $imagen]
    );
}

// Update product
function actualizarProducto($id, $nombre, $descripcion, $precio, $imagen) {
    global $db;

    return $db->update(
        "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, fecha_actualizacion = NOW() WHERE id = ?",
        [$nombre, $descripcion, $precio, $imagen, $id]
    );
}

// Delete product
function eliminarProducto($id) {
    global $db;

    return $db->update(
        "UPDATE productos SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?",
        [$id]
    );
}

// Add video
function agregarVideo($titulo, $descripcion, $youtube_id, $categoria_id = null) {
    global $db;

    return $db->insert(
        "INSERT INTO videos (titulo, descripcion, youtube_id, categoria_id, activo, fecha_creacion) VALUES (?, ?, ?, ?, 1, NOW())",
        [$titulo, $descripcion, $youtube_id, $categoria_id]
    );
}

// Update video
function actualizarVideo($id, $titulo, $descripcion, $youtube_id, $categoria_id = null) {
    global $db;

    return $db->update(
        "UPDATE videos SET titulo = ?, descripcion = ?, youtube_id = ?, categoria_id = ?, fecha_actualizacion = NOW() WHERE id = ?",
        [$titulo, $descripcion, $youtube_id, $categoria_id, $id]
    );
}

// Delete video
function eliminarVideo($id) {
    global $db;

    return $db->update(
        "UPDATE videos SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?",
        [$id]
    );
}

// Extract YouTube ID from URL
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return '';
}

// Get statistics for dashboard
function getEstadisticas() {
    global $db;

    $hoy = date('Y-m-d');

    $estadisticas = [
        'citas_hoy' => $db->fetchValue("SELECT COUNT(*) FROM citas WHERE fecha = ?", [$hoy]),
        'citas_confirmadas' => $db->fetchValue("SELECT COUNT(*) FROM citas WHERE estado = 'confirmada'"),
        'citas_completadas' => $db->fetchValue("SELECT COUNT(*) FROM citas WHERE estado = 'completada'"),
        'citas_canceladas' => $db->fetchValue("SELECT COUNT(*) FROM citas WHERE estado = 'cancelada'"),
        'productos' => $db->fetchValue("SELECT COUNT(*) FROM productos WHERE activo = 1"),
        'videos' => $db->fetchValue("SELECT COUNT(*) FROM videos WHERE activo = 1")
    ];

    return $estadisticas;
}

// Verify admin credentials
function verificarCredencialesAdmin($username, $password) {
    global $db;

    $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE username = ? AND activo = 1", [$username]);

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Update last login
        $db->update("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$usuario['id']]);
        return $usuario['id'];
    }

    return false;
}

// Change admin password
function cambiarPasswordAdmin($id, $password_actual, $password_nueva) {
    global $db;

    $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);

    if ($usuario && password_verify($password_actual, $usuario['password'])) {
        $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        return $db->update("UPDATE usuarios SET password = ? WHERE id = ?", [$password_hash, $id]);
    }

    return false;
}
?>
