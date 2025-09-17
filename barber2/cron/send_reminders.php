<?php
/**
 * Cron job to send reminder emails 24 hours before appointments
 * Add this to your crontab to run every hour:
 * 0 * * * * /usr/bin/php /path/to/your/project/cron/send_reminders.php
 */
chdir(__DIR__ . '/..');
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/EmailService.php';

$database = new Database();
$db = $database->getConnection();

// Get appointments that need reminders (24 hours before)
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$query = "SELECT a.*, s.name as service_name 
          FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          WHERE a.appointment_date = :tomorrow 
          AND a.status = 'confirmed' 
          AND a.reminder_sent = 0";

$stmt = $db->prepare($query);
$stmt->bindParam(':tomorrow', $tomorrow);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$emailService = new EmailService();
$sentCount = 0;
$failedCount = 0;

foreach ($appointments as $appointment) {
    try {
        // Send reminder email
        if ($emailService->sendReminderEmail($appointment)) {
            // Mark as sent
            $updateQuery = "UPDATE appointments SET reminder_sent = 1 WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $appointment['id']);
            $updateStmt->execute();
            
            // Log the reminder
            $logQuery = "INSERT INTO email_reminders (appointment_id, reminder_type, status) VALUES (:appointment_id, 'reminder_24h', 'sent')";
            $logStmt = $db->prepare($logQuery);
            $logStmt->bindParam(':appointment_id', $appointment['id']);
            $logStmt->execute();
            
            $sentCount++;
            echo "Reminder sent to: " . $appointment['client_email'] . "\n";
        } else {
            $failedCount++;
            echo "Failed to send reminder to: " . $appointment['client_email'] . "\n";
        }
    } catch (Exception $e) {
        $failedCount++;
        echo "Error sending reminder to " . $appointment['client_email'] . ": " . $e->getMessage() . "\n";
        
        // Log the failed attempt
        try {
            $logQuery = "INSERT INTO email_reminders (appointment_id, reminder_type, status) VALUES (:appointment_id, 'reminder_24h', 'failed')";
            $logStmt = $db->prepare($logQuery);
            $logStmt->bindParam(':appointment_id', $appointment['id']);
            $logStmt->execute();
        } catch (Exception $logError) {
            echo "Failed to log error: " . $logError->getMessage() . "\n";
        }
    }
}

echo "Reminder job completed. Sent: $sentCount, Failed: $failedCount\n";
?>
