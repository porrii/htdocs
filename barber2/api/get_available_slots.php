<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $date = $input['date'];
    $serviceDuration = (int)$input['service_duration'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get day of week (0 = Sunday, 1 = Monday, etc.)
    $dayOfWeek = date('w', strtotime($date));
    
    // Get work schedule for this day
    $query = "SELECT * FROM work_schedules WHERE day_of_week = :day_of_week AND is_working_day = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':day_of_week', $dayOfWeek);
    $stmt->execute();
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode([]);
        exit;
    }
    
    // Get existing appointments for this date
    $query = "SELECT appointment_time, duration FROM appointments a 
              JOIN services s ON a.service_id = s.id 
              WHERE appointment_date = :date AND status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $existingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get configuration
    $config = getConfig();
    $appointmentInterval = $config['appointment_interval'];
    $bookingRestriction = $config['booking_restriction'];
    
    // Generate available time slots
    $availableSlots = [];
    
    // Morning slots
    if ($schedule['morning_start'] && $schedule['morning_end']) {
        $slots = generateTimeSlots(
            $schedule['morning_start'], 
            $schedule['morning_end'], 
            $appointmentInterval, 
            $serviceDuration,
            $existingAppointments,
            $date,
            $bookingRestriction
        );
        $availableSlots = array_merge($availableSlots, $slots);
    }
    
    // Afternoon slots
    if ($schedule['afternoon_start'] && $schedule['afternoon_end']) {
        $slots = generateTimeSlots(
            $schedule['afternoon_start'], 
            $schedule['afternoon_end'], 
            $appointmentInterval, 
            $serviceDuration,
            $existingAppointments,
            $date,
            $bookingRestriction
        );
        $availableSlots = array_merge($availableSlots, $slots);
    }
    
    echo json_encode($availableSlots);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar horarios disponibles']);
}

function generateTimeSlots($startTime, $endTime, $interval, $serviceDuration, $existingAppointments, $date, $bookingRestriction) {
    $slots = [];
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    
    // Check if it's today and apply booking restriction
    $now = new DateTime();
    $appointmentDate = new DateTime($date);
    $isToday = $appointmentDate->format('Y-m-d') === $now->format('Y-m-d');
    
    while ($start < $end) {
        $slotTime = $start->format('H:i');
        $slotEnd = clone $start;
        $slotEnd->add(new DateInterval('PT' . $serviceDuration . 'M'));
        
        // Check if slot fits within working hours
        if ($slotEnd <= $end) {
            $available = true;
            
            // Check if it's too close to current time (for today only)
            if ($isToday) {
                $slotDateTime = new DateTime($date . ' ' . $slotTime);
                $restrictionTime = clone $now;
                $restrictionTime->add(new DateInterval('PT' . $bookingRestriction . 'M'));
                
                if ($slotDateTime <= $restrictionTime) {
                    $available = false;
                }
            }
            
            // Check against existing appointments
            if ($available) {
                foreach ($existingAppointments as $appointment) {
                    $appointmentStart = new DateTime($appointment['appointment_time']);
                    $appointmentEnd = clone $appointmentStart;
                    $appointmentEnd->add(new DateInterval('PT' . $appointment['duration'] . 'M'));
                    
                    // Check for overlap
                    if (($start >= $appointmentStart && $start < $appointmentEnd) ||
                        ($slotEnd > $appointmentStart && $slotEnd <= $appointmentEnd) ||
                        ($start <= $appointmentStart && $slotEnd >= $appointmentEnd)) {
                        $available = false;
                        break;
                    }
                }
            }
            
            $slots[] = [
                'time' => $slotTime,
                'available' => $available
            ];
        }
        
        $start->add(new DateInterval('PT' . $interval . 'M'));
    }
    
    return $slots;
}
?>
