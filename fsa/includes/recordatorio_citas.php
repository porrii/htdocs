<?php
require_once 'db.php';

// Carga manual de las clases de PHPMailer 
require 'phpmailer/src/Exception.php'; 
require 'phpmailer/src/PHPMailer.php'; 
require 'phpmailer/src/SMTP.php';  

use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception;

// Buscar citas dentro de las próximas 24h que no hayan sido recordadas
$ahora = new DateTime();
$limite = new DateTime('+24 hours');
$ahora_str = $ahora->format('Y-m-d H:i:s');
$limite_str = $limite->format('Y-m-d H:i:s');

$sql = "SELECT *, CONCAT(fecha, ' ', hora) AS fecha_hora FROM citas 
        WHERE CONCAT(fecha, ' ', hora) BETWEEN ? AND ? 
        AND recordatorio_enviado = 0 
        AND estado = 'confirmada'";
$stmt = $conn->prepare($sql);
$stmt->execute([$ahora_str, $limite_str]);
$citas = $stmt->fetchAll();

foreach ($citas as $cita) {
    $nombre    = $cita['nombre'];
    $email     = $cita['email'];
    $id        = $cita['id'];
    $fechaHora = new DateTime($cita['fecha_hora']);
    $fecha     = $fechaHora->format('Y-m-d');
    $hora      = $fechaHora->format('H:i');
    $servicio  = $cita['servicio'];

    if (enviarEmailRecordatorio($nombre, $email, $fecha, $hora, $servicio, $id)) {
        $update = $conn->prepare("UPDATE citas SET recordatorio_enviado = 1 WHERE id = ?");
        $update->execute([$id]);
    }
}

// ========================
// Función para enviar recordatorio
// ========================
function enviarEmailRecordatorio($nombre, $email, $fecha, $hora, $servicio, $id) {
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = 'Recordatorio de Cita - ' . SITE_NAME;

        // Formato de fecha
        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        $diaSemana = date('l', strtotime($fecha));
        $diasEspanol = [
            'Monday'    => 'Lunes',
            'Tuesday'   => 'Martes', 
            'Wednesday' => 'Miércoles',
            'Thursday'  => 'Jueves',
            'Friday'    => 'Viernes',
            'Saturday'  => 'Sábado',
            'Sunday'    => 'Domingo'
        ];
        $diaFormateado = $diasEspanol[$diaSemana] . ', ' . $fechaFormateada;

        // Cuerpo del mensaje
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
            <h2 style="color: #f57c00; text-align: center;">⏰ Recordatorio de tu cita</h2>
            <p>Hola <strong>' . $nombre . '</strong>,</p>
            <p>Este es un recordatorio de tu cita agendada en <strong>' . SITE_NAME . '</strong>. Aquí tienes los detalles:</p>
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p><strong>Número de Cita:</strong> ' . $id . '</p>
                <p><strong>Fecha:</strong> ' . $diaFormateado . '</p>
                <p><strong>Hora:</strong> ' . $hora . '</p>
                <p><strong>Servicio:</strong> ' . $servicio . '</p>
            </div>
            <p>Si no puedes asistir, por favor cancela tu cita con antelación desde nuestra página web.</p>
            <p>¡Te esperamos!</p>
            <p style="text-align: center; margin-top: 30px; color: #999;">
                <small>Este es un correo automático, por favor no lo respondas.</small>
            </p>
        </div>
        ';

        $mail->AltBody = '
        ⏰ Recordatorio de tu cita

        Hola ' . $nombre . ',

        Este es un recordatorio de tu cita agendada en ' . SITE_NAME . ':

        Número de Cita: ' . $id . '
        Fecha: ' . $diaFormateado . '
        Hora: ' . $hora . '
        Servicio: ' . $servicio . '

        Si no puedes asistir, por favor cancela tu cita con antelación desde nuestra página web.

        Este es un correo automático, por favor no lo respondas.
        ';

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error al enviar recordatorio: " . $mail->ErrorInfo);
        return false;
    }
}
