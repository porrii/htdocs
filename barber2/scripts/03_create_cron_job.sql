-- Create a table to track email reminders
CREATE TABLE IF NOT EXISTS email_reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    reminder_type ENUM('confirmation', 'reminder_24h') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    UNIQUE KEY unique_reminder (appointment_id, reminder_type)
);
