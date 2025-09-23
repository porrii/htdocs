<?php
require_once '../../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Process each day of the week
        for ($day = 0; $day <= 6; $day++) {
            $isWorking = isset($_POST['working'][$day]) ? 1 : 0;
            $morningStart = $_POST['morning_start'][$day] ?? null;
            $morningEnd = $_POST['morning_end'][$day] ?? null;
            $afternoonStart = $_POST['afternoon_start'][$day] ?? null;
            $afternoonEnd = $_POST['afternoon_end'][$day] ?? null;
            
            // Clean empty values
            $morningStart = $morningStart === '' ? null : $morningStart;
            $morningEnd = $morningEnd === '' ? null : $morningEnd;
            $afternoonStart = $afternoonStart === '' ? null : $afternoonStart;
            $afternoonEnd = $afternoonEnd === '' ? null : $afternoonEnd;
            
            // Check if record exists
            $stmt = $db->prepare("SELECT id FROM work_schedules WHERE day_of_week = ?");
            $stmt->execute([$day]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing record
                $stmt = $db->prepare("
                    UPDATE work_schedules 
                    SET is_working_day = ?, morning_start = ?, morning_end = ?, 
                        afternoon_start = ?, afternoon_end = ?
                    WHERE day_of_week = ?
                ");
                $stmt->execute([
                    $isWorking, $morningStart, $morningEnd, 
                    $afternoonStart, $afternoonEnd, $day
                ]);
            } else {
                // Insert new record
                $stmt = $db->prepare("
                    INSERT INTO work_schedules 
                    (day_of_week, is_working_day, morning_start, morning_end, afternoon_start, afternoon_end) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $day, $isWorking, $morningStart, $morningEnd, 
                    $afternoonStart, $afternoonEnd
                ]);
            }
        }
        
        $db->commit();
        $_SESSION['message'] = 'Horarios actualizados correctamente';
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['message'] = 'Error al actualizar los horarios: ' . $e->getMessage();
    }
    
    header('Location: ../schedule.php');
    exit;
}
?>
