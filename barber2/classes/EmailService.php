<?php
chdir(__DIR__ . '/..');
require_once 'config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

class EmailService {
    private $config;
    
    public function __construct() {
        $this->config = getConfig();
    }
    
    public function sendConfirmationEmail($appointment) {
        $to = $appointment['client_email'];
        $subject = "Confirmación de Cita - " . SITE_NAME;
        
        $message = $this->getConfirmationEmailTemplate($appointment);
        
        $result = $this->sendEmail($to, $subject, $message);
        
        // Log the email attempt
        if ($result) {
            $this->logEmailSent($appointment['booking_id'], 'confirmation', 'sent');
        } else {
            $this->logEmailSent($appointment['booking_id'], 'confirmation', 'failed');
        }
        
        return $result;
    }
    
    public function sendReminderEmail($appointment) {
        $to = $appointment['client_email'];
        $subject = "Recordatorio de Cita - " . SITE_NAME;
        
        $message = $this->getReminderEmailTemplate($appointment);
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    public function sendCancellationEmail($appointment) {
        $to = $appointment['client_email'];
        $subject = "Cita Cancelada - " . SITE_NAME;
        
        $message = $this->getCancellationEmailTemplate($appointment);
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    private function sendEmail($to, $subject, $message) {
        // Check if SMTP is configured
        if (empty($this->config['smtp_host']) || empty($this->config['smtp_username'])) {
            error_log("SMTP not configured, using PHP mail() function");
            return $this->sendWithPHPMail($to, $subject, $message);
        }
        
        // Use PHPMailer for SMTP
        return $this->sendWithSMTP($to, $subject, $message);
    }
    
    private function sendWithPHPMail($to, $subject, $message) {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . SITE_NAME . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Reply-To: ' . ($this->config['smtp_username'] ?: 'noreply@' . $_SERVER['HTTP_HOST']),
            'X-Mailer: PHP/' . phpversion()
        );
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    private function sendWithSMTP($to, $subject, $message) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp_username'];
            $mail->Password   = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'] ?: PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['smtp_port'] ?: 587;
            $mail->CharSet    = 'UTF-8';
            
            // Recipients
            $mail->setFrom($this->config['smtp_username'], SITE_NAME);
            $mail->addAddress($to);
            $mail->addReplyTo($this->config['smtp_username'], SITE_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            // Fallback to PHP mail if SMTP fails
            return $this->sendWithPHPMail($to, $subject, $message);
        }
    }
    
    private function logEmailSent($appointmentId, $type, $status) {
        try {
            require_once '../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO email_reminders (appointment_id, reminder_type, status) VALUES (:appointment_id, :type, :status)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':appointment_id', $appointmentId);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }
    
    private function getConfirmationEmailTemplate($appointment) {
        $date = date('d/m/Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Space Grotesk', Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #475569; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f8fafc;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #164e63 0%, #f59e0b 100%); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: bold;
                }
                .content { 
                    padding: 30px 20px; 
                }
                .appointment-details {
                    background: #ecfeff;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #e2e8f0;
                }
                .detail-row:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                    padding-bottom: 0;
                }
                .detail-label {
                    font-weight: 600;
                    color: #164e63;
                }
                .detail-value {
                    color: #475569;
                }
                .footer { 
                    background: #1e293b; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 14px;
                }
                .button { 
                    display: inline-block;
                    background: #f59e0b; 
                    color: white; 
                    padding: 12px 24px; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    font-weight: 600;
                    margin: 20px 0;
                }
                .success-icon {
                    width: 60px;
                    height: 60px;
                    background: #10b981;
                    border-radius: 50%;
                    margin: 0 auto 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Tu barbería de confianza</p>
                </div>
                <div class='content'>
                    <div class='success-icon'>✓</div>
                    <h2 style='text-align: center; color: #164e63; margin-bottom: 10px;'>¡Cita Confirmada!</h2>
                    <p style='text-align: center; margin-bottom: 30px;'>Hola <strong>{$appointment['client_name']}</strong>,</p>
                    <p>Tu cita ha sido confirmada exitosamente. Aquí tienes todos los detalles:</p>
                    
                    <div class='appointment-details'>
                        <div class='detail-row'>
                            <span class='detail-label'>Servicio:</span>
                            <span class='detail-value'>{$appointment['service_name']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Id de cita:</span>
                            <span class='detail-value'>{$appointment['booking_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Fecha:</span>
                            <span class='detail-value'>{$date}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Hora:</span>
                            <span class='detail-value'>{$time}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Cliente:</span>
                            <span class='detail-value'>{$appointment['client_name']}</span>
                        </div>
                    </div>
                    
                    <p><strong>¿Qué esperar?</strong></p>
                    <ul style='color: #64748b; margin-left: 20px;'>
                        <li>Llega 5 minutos antes de tu cita</li>
                        <li>Te enviaremos un recordatorio 24 horas antes</li>
                        <li>Si necesitas cancelar, hazlo con al menos 2 horas de antelación</li>
                    </ul>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "' class='button'>Visitar Nuestra Web</a>
                    </div>
                    
                    <p style='font-size: 14px; color: #64748b; text-align: center;'>
                        Si necesitas cancelar o modificar tu cita, por favor contáctanos lo antes posible.
                    </p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>&copy; " . date('Y') . " " . SITE_NAME . ". Todos los derechos reservados.</p>
                    <p style='margin: 5px 0 0 0; opacity: 0.8;'> " . SITE_LOCATION . " | " . SITE_NUMBER . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getReminderEmailTemplate($appointment) {
        $date = date('d/m/Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Space Grotesk', Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #475569; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f8fafc;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #164e63 0%, #f59e0b 100%); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: bold;
                }
                .content { 
                    padding: 30px 20px; 
                }
                .reminder-box {
                    background: linear-gradient(135deg, #f59e0b, #f97316);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                    margin: 20px 0;
                }
                .appointment-details {
                    background: #ecfeff;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #e2e8f0;
                }
                .detail-row:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                    padding-bottom: 0;
                }
                .detail-label {
                    font-weight: 600;
                    color: #164e63;
                }
                .detail-value {
                    color: #475569;
                }
                .footer { 
                    background: #1e293b; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 14px;
                }
                .clock-icon {
                    width: 60px;
                    height: 60px;
                    background: #f59e0b;
                    border-radius: 50%;
                    margin: 0 auto 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Tu barbería de confianza</p>
                </div>
                <div class='content'>
                    <div class='clock-icon'>⏰</div>
                    
                    <div class='reminder-box'>
                        <h2 style='margin: 0 0 10px 0; font-size: 24px;'>¡Recordatorio de Cita!</h2>
                        <p style='margin: 0; font-size: 18px; opacity: 0.9;'>Tu cita es mañana</p>
                    </div>
                    
                    <p>Hola <strong>{$appointment['client_name']}</strong>,</p>
                    <p>Te recordamos que tienes una cita programada para mañana:</p>
                    
                    <div class='appointment-details'>
                        <div class='detail-row'>
                            <span class='detail-label'>Servicio:</span>
                            <span class='detail-value'>{$appointment['service_name']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Id de cita:</span>
                            <span class='detail-value'>{$appointment['booking_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Fecha:</span>
                            <span class='detail-value'>{$date}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Hora:</span>
                            <span class='detail-value'>{$time}</span>
                        </div>
                    </div>
                    
                    <p><strong>Recordatorios importantes:</strong></p>
                    <ul style='color: #64748b; margin-left: 20px;'>
                        <li>Llega 5 minutos antes de tu cita</li>
                        <li>Si necesitas cancelar, hazlo con al menos 2 horas de antelación</li>
                        <li>Trae una identificación si es tu primera visita</li>
                    </ul>
                    
                    <p style='text-align: center; font-size: 18px; color: #164e63; font-weight: 600; margin: 30px 0;'>
                        ¡Te esperamos!
                    </p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>&copy; " . date('Y') . " " . SITE_NAME . ". Todos los derechos reservados.</p>
                    <p style='margin: 5px 0 0 0; opacity: 0.8;'> " . SITE_LOCATION . " | " . SITE_NUMBER . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getCancellationEmailTemplate($appointment) {
        $date = date('d/m/Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Space Grotesk', Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #475569; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f8fafc;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #164e63 0%, #f59e0b 100%); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: bold;
                }
                .content { 
                    padding: 30px 20px; 
                }
                .cancellation-box {
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .appointment-details {
                    background: #f8fafc;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #e2e8f0;
                }
                .detail-row:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                    padding-bottom: 0;
                }
                .detail-label {
                    font-weight: 600;
                    color: #164e63;
                }
                .detail-value {
                    color: #475569;
                }
                .footer { 
                    background: #1e293b; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 14px;
                }
                .button { 
                    display: inline-block;
                    background: #164e63; 
                    color: white; 
                    padding: 12px 24px; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    font-weight: 600;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Tu barbería de confianza</p>
                </div>
                <div class='content'>
                    <div class='cancellation-box'>
                        <h2 style='color: #dc2626; margin: 0 0 10px 0;'>Cita Cancelada</h2>
                        <p style='margin: 0; color: #7f1d1d;'>Tu cita ha sido cancelada exitosamente</p>
                    </div>
                    
                    <p>Hola <strong>{$appointment['client_name']}</strong>,</p>
                    <p>Confirmamos que tu cita ha sido cancelada:</p>
                    
                    <div class='appointment-details'>
                        <div class='detail-row'>
                            <span class='detail-label'>Servicio:</span>
                            <span class='detail-value'>{$appointment['service_name']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Id de cita:</span>
                            <span class='detail-value'>{$appointment['booking_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Fecha:</span>
                            <span class='detail-value'>{$date}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Hora:</span>
                            <span class='detail-value'>{$time}</span>
                        </div>
                    </div>
                    
                    <p>Lamentamos que no puedas acompañarnos en esta ocasión. Esperamos verte pronto en " . SITE_NAME . ".</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "/booking.php' class='button'>Reservar Nueva Cita</a>
                    </div>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>&copy; " . date('Y') . " " . SITE_NAME . ". Todos los derechos reservados.</p>
                    <p style='margin: 5px 0 0 0; opacity: 0.8;'> " . SITE_LOCATION . " | " . SITE_NUMBER . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
