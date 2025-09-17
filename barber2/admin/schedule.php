<?php
require_once '../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get current schedule
$stmt = $db->query("SELECT * FROM work_schedules ORDER BY day_of_week");
$schedule = $stmt->fetchAll();

// Convert to associative array for easier access
$scheduleByDay = [];
foreach ($schedule as $day) {
    $scheduleByDay[$day['day_of_week']] = $day;
}

$days = [
    1 => 'Lunes',
    2 => 'Martes', 
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios - Panel Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Space Grotesk', 'system-ui', 'sans-serif'],
                        'body': ['DM Sans', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: '#164e63',
                        secondary: '#f59e0b',
                        accent: '#f59e0b',
                        muted: '#475569',
                    }
                }
            }
        }
    </script>
    
    <style>
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: rgba(22, 78, 99, 0.1);
            transform: translateX(4px);
        }
        .sidebar-link.active {
            background: #164e63;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white">
            <div class="p-6">
                <h2 class="text-xl font-bold">Panel Admin</h2>
            </div>
            <nav class="mt-6">
                <a href="index.php" class="block px-6 py-3 hover:bg-gray-700">Dashboard</a>
                <a href="appointments.php" class="block px-6 py-3 hover:bg-gray-700">Citas</a>
                <a href="services.php" class="block px-6 py-3 hover:bg-gray-700">Servicios</a>
                <a href="videos.php" class="block px-6 py-3 hover:bg-gray-700">Videos</a>
                <a href="products.php" class="block px-6 py-3 hover:bg-gray-700">Productos</a>
                <a href="schedule.php" class="block px-6 py-3 bg-gray-700">Horarios</a>
                <a href="settings.php" class="block px-6 py-3 hover:bg-gray-700">Configuración</a>
                <a href="logout.php" class="block px-6 py-3 hover:bg-gray-700">Cerrar Sesión</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">Gestión de Horarios</h1>

                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="api/save_schedule.php" method="POST" class="space-y-6">
                    <?php foreach ($days as $dayNum => $dayName): ?>
                        <?php 
                        $daySchedule = $scheduleByDay[$dayNum] ?? null;
                        $isWorking = $daySchedule ? $daySchedule['is_working_day'] : 0;
                        $morningStart = $daySchedule ? $daySchedule['morning_start'] : '';
                        $morningEnd = $daySchedule ? $daySchedule['morning_end'] : '';
                        $afternoonStart = $daySchedule ? $daySchedule['afternoon_start'] : '';
                        $afternoonEnd = $daySchedule ? $daySchedule['afternoon_end'] : '';
                        ?>
                        
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo $dayName; ?></h3>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="working[<?php echo $dayNum; ?>]" 
                                           value="1"
                                           <?php echo $isWorking ? 'checked' : ''; ?>
                                           onchange="toggleDay(<?php echo $dayNum; ?>)"
                                           class="mr-2 h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                                    <span class="text-sm text-gray-700">Día laborable</span>
                                </label>
                            </div>

                            <div id="schedule-<?php echo $dayNum; ?>" class="<?php echo $isWorking ? '' : 'hidden'; ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Morning Schedule -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium text-gray-900">Horario de Mañana</h4>
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm text-gray-700 w-16">Desde:</label>
                                            <input type="time" 
                                                   name="morning_start[<?php echo $dayNum; ?>]" 
                                                   value="<?php echo $morningStart; ?>"
                                                   class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm text-gray-700 w-16">Hasta:</label>
                                            <input type="time" 
                                                   name="morning_end[<?php echo $dayNum; ?>]" 
                                                   value="<?php echo $morningEnd; ?>"
                                                   class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        </div>
                                    </div>

                                    <!-- Afternoon Schedule -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium text-gray-900">Horario de Tarde</h4>
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm text-gray-700 w-16">Desde:</label>
                                            <input type="time" 
                                                   name="afternoon_start[<?php echo $dayNum; ?>]" 
                                                   value="<?php echo $afternoonStart; ?>"
                                                   class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm text-gray-700 w-16">Hasta:</label>
                                            <input type="time" 
                                                   name="afternoon_end[<?php echo $dayNum; ?>]" 
                                                   value="<?php echo $afternoonEnd; ?>"
                                                   class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-gray-50 rounded-md">
                                    <p class="text-sm text-gray-600">
                                        <strong>Nota:</strong> Deja los campos vacíos si no trabajas en ese turno. 
                                        Por ejemplo, si solo trabajas por la mañana, deja los campos de tarde vacíos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 font-medium">
                            Guardar Horarios
                        </button>
                    </div>
                </form>

                <!-- Current Schedule Preview -->
                <div class="mt-12 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Vista Previa del Horario</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($days as $dayNum => $dayName): ?>
                            <?php 
                            $daySchedule = $scheduleByDay[$dayNum] ?? null;
                            $isWorking = $daySchedule ? $daySchedule['is_working_day'] : 0;
                            ?>
                            <div class="p-4 border rounded-lg <?php echo $isWorking ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'; ?>">
                                <h3 class="font-medium text-gray-900 mb-2"><?php echo $dayName; ?></h3>
                                <?php if ($isWorking): ?>
                                    <?php if ($daySchedule['morning_start'] && $daySchedule['morning_end']): ?>
                                        <p class="text-sm text-gray-700">
                                            Mañana: <?php echo substr($daySchedule['morning_start'], 0, 5); ?> - <?php echo substr($daySchedule['morning_end'], 0, 5); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($daySchedule['afternoon_start'] && $daySchedule['afternoon_end']): ?>
                                        <p class="text-sm text-gray-700">
                                            Tarde: <?php echo substr($daySchedule['afternoon_start'], 0, 5); ?> - <?php echo substr($daySchedule['afternoon_end'], 0, 5); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!$daySchedule['morning_start'] && !$daySchedule['afternoon_start']): ?>
                                        <p class="text-sm text-amber-600">Sin horarios configurados</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">Cerrado</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDay(dayNum) {
            const checkbox = document.querySelector(`input[name="working[${dayNum}]"]`);
            const scheduleDiv = document.getElementById(`schedule-${dayNum}`);
            
            if (checkbox.checked) {
                scheduleDiv.classList.remove('hidden');
            } else {
                scheduleDiv.classList.add('hidden');
                // Clear all time inputs for this day
                const timeInputs = scheduleDiv.querySelectorAll('input[type="time"]');
                timeInputs.forEach(input => input.value = '');
            }
        }

        // Validate time ranges
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let hasError = false;
                
                // Check each day's schedule
                for (let day = 1; day <= 7; day++) {
                    const workingCheckbox = document.querySelector(`input[name="working[${day}]"]`);
                    
                    if (workingCheckbox && workingCheckbox.checked) {
                        const morningStart = document.querySelector(`input[name="morning_start[${day}]"]`).value;
                        const morningEnd = document.querySelector(`input[name="morning_end[${day}]"]`).value;
                        const afternoonStart = document.querySelector(`input[name="afternoon_start[${day}]"]`).value;
                        const afternoonEnd = document.querySelector(`input[name="afternoon_end[${day}]"]`).value;
                        
                        // Validate morning schedule
                        if (morningStart && morningEnd && morningStart >= morningEnd) {
                            alert(`Error en el horario de mañana del día ${day}: La hora de inicio debe ser anterior a la hora de fin.`);
                            hasError = true;
                            break;
                        }
                        
                        // Validate afternoon schedule
                        if (afternoonStart && afternoonEnd && afternoonStart >= afternoonEnd) {
                            alert(`Error en el horario de tarde del día ${day}: La hora de inicio debe ser anterior a la hora de fin.`);
                            hasError = true;
                            break;
                        }
                        
                        // Validate that afternoon starts after morning ends
                        if (morningEnd && afternoonStart && morningEnd >= afternoonStart) {
                            alert(`Error en el día ${day}: El horario de tarde debe comenzar después del horario de mañana.`);
                            hasError = true;
                            break;
                        }
                        
                        // Check that at least one schedule is set for working days
                        if (!morningStart && !afternoonStart) {
                            alert(`Error en el día ${day}: Debe configurar al menos un horario (mañana o tarde) para los días laborables.`);
                            hasError = true;
                            break;
                        }
                    }
                }
                
                if (hasError) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
