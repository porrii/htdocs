<?php
require_once '../config/config.php';

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
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    0 => 'Domingo'
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
</head>
<body class="font-body bg-gray-50">

    <div class="flex h-screen">
        <!-- Sidebar Desktop -->
        <div class="w-64 bg-gray-900 text-white hidden md:block">
            <div class="p-6"><h2 class="text-xl font-bold">Panel Admin</h2></div>
            <nav class="mt-6">
                <a href="index.php" class="block px-6 py-3 hover:bg-gray-700">Dashboard</a>
                <a href="appointments.php" class="block px-6 py-3 hover:bg-gray-700">Citas</a>
                <a href="services.php" class="block px-6 py-3 hover:bg-gray-700">Servicios</a>
                <a href="videos.php" class="block px-6 py-3 hover:bg-gray-700">Videos</a>
                <a href="products.php" class="block px-6 py-3 hover:bg-gray-700">Productos</a>
                <a href="schedule.php" class="block px-6 py-3 bg-gray-700">Horarios</a>
                <a href="reports.php" class="block px-6 py-3 hover:bg-gray-700">Informes</a>
                <a href="settings.php" class="block px-6 py-3 hover:bg-gray-700">Configuración</a>
                <a href="logout.php" class="block px-6 py-3 hover:bg-gray-700">Cerrar Sesión</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-auto">
            <!-- Mobile Header -->
            <div class="md:hidden p-4 bg-gray-900 text-white flex justify-between items-center">
                <h2 class="text-lg font-bold">Panel Admin</h2>
                <button id="mobile-menu-btn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-gray-900 text-white">
                <nav class="px-4 py-2 space-y-2">
                    <a href="index.php" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a>
                    <a href="appointments.php" class="block px-4 py-2 rounded hover:bg-gray-700">Citas</a>
                    <a href="services.php" class="block px-4 py-2 rounded hover:bg-gray-700">Servicios</a>
                    <a href="videos.php" class="block px-4 py-2 rounded hover:bg-gray-700">Videos</a>
                    <a href="products.php" class="block px-4 py-2 rounded hover:bg-gray-700">Productos</a>
                    <a href="schedule.php" class="block px-4 py-2 rounded bg-gray-700">Horarios</a>
                    <a href="reports.php" class="block px-4 py-2 rounded hover:bg-gray-700">Informes</a>
                    <a href="settings.php" class="block px-4 py-2 rounded hover:bg-gray-700">Configuración</a>
                    <a href="logout.php" class="block px-4 py-2 rounded hover:bg-gray-700">Cerrar Sesión</a>
                </nav>
            </div>

            <script>
                const btn = document.getElementById('mobile-menu-btn');
                const menu = document.getElementById('mobile-menu');
                btn.addEventListener('click', () => menu.classList.toggle('hidden'));
            </script>

            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-4 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Gestión de Horarios</h1>
                </div>
            </header>

            <main class="p-4 sm:p-8">
                <?php if($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="api/save_schedule.php" method="POST" class="space-y-6">
                    <?php foreach($days as $dayNum => $dayName): 
                        $daySchedule = $scheduleByDay[$dayNum] ?? null;
                        $isWorking = $daySchedule ? $daySchedule['is_working_day'] : 0;
                        $morningStart = $daySchedule['morning_start'] ?? '';
                        $morningEnd = $daySchedule['morning_end'] ?? '';
                        $afternoonStart = $daySchedule['afternoon_start'] ?? '';
                        $afternoonEnd = $daySchedule['afternoon_end'] ?? '';
                    ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo $dayName; ?></h3>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                    name="working[<?php echo $dayNum; ?>]" 
                                    value="1" <?php echo $isWorking ? 'checked' : ''; ?>
                                    onchange="toggleDay(<?php echo $dayNum; ?>)"
                                    class="mr-2 h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                                <span class="text-sm text-gray-700">Día laborable</span>
                            </label>
                        </div>

                        <div id="schedule-<?php echo $dayNum; ?>" class="<?php echo $isWorking ? '' : 'hidden'; ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Morning -->
                                <div class="space-y-2">
                                    <h4 class="font-medium text-gray-900">Horario de Mañana</h4>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm text-gray-700 w-16">Desde:</label>
                                        <input type="time" name="morning_start[<?php echo $dayNum; ?>]" value="<?php echo $morningStart; ?>"
                                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 w-full">
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm text-gray-700 w-16">Hasta:</label>
                                        <input type="time" name="morning_end[<?php echo $dayNum; ?>]" value="<?php echo $morningEnd; ?>"
                                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 w-full">
                                    </div>
                                </div>
                                <!-- Afternoon -->
                                <div class="space-y-2">
                                    <h4 class="font-medium text-gray-900">Horario de Tarde</h4>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm text-gray-700 w-16">Desde:</label>
                                        <input type="time" name="afternoon_start[<?php echo $dayNum; ?>]" value="<?php echo $afternoonStart; ?>"
                                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 w-full">
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm text-gray-700 w-16">Hasta:</label>
                                        <input type="time" name="afternoon_end[<?php echo $dayNum; ?>]" value="<?php echo $afternoonEnd; ?>"
                                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 w-full">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-gray-50 rounded">
                                <p class="text-sm text-gray-600"><strong>Nota:</strong> Deja los campos vacíos si no trabajas en ese turno.</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 font-medium">
                            Guardar Horarios
                        </button>
                    </div>
                </form>

                <!-- Preview -->
                <div class="mt-8 bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Vista Previa del Horario</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach($days as $dayNum => $dayName): 
                            $daySchedule = $scheduleByDay[$dayNum] ?? null;
                            $isWorking = $daySchedule['is_working_day'] ?? 0;
                        ?>
                        <div class="p-4 border rounded <?php echo $isWorking ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'; ?>">
                            <h3 class="font-medium text-gray-900 mb-2"><?php echo $dayName; ?></h3>
                            <?php if($isWorking): ?>
                                <?php if($daySchedule['morning_start'] && $daySchedule['morning_end']): ?>
                                    <p class="text-sm text-gray-700">Mañana: <?php echo substr($daySchedule['morning_start'],0,5); ?> - <?php echo substr($daySchedule['morning_end'],0,5); ?></p>
                                <?php endif; ?>
                                <?php if($daySchedule['afternoon_start'] && $daySchedule['afternoon_end']): ?>
                                    <p class="text-sm text-gray-700">Tarde: <?php echo substr($daySchedule['afternoon_start'],0,5); ?> - <?php echo substr($daySchedule['afternoon_end'],0,5); ?></p>
                                <?php endif; ?>
                                <?php if(!$daySchedule['morning_start'] && !$daySchedule['afternoon_start']): ?>
                                    <p class="text-sm text-amber-600">Sin horarios configurados</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-500">Cerrado</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleDay(dayNum) {
            const checkbox = document.querySelector(`input[name="working[${dayNum}]"]`);
            const scheduleDiv = document.getElementById(`schedule-${dayNum}`);
            if(checkbox.checked){
                scheduleDiv.classList.remove('hidden');
            } else {
                scheduleDiv.classList.add('hidden');
                scheduleDiv.querySelectorAll('input[type="time"]').forEach(i => i.value='');
            }
        }

        // Validate time ranges
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e){
                for(let day=0; day<=6; day++){
                    const chk = document.querySelector(`input[name="working[${day}]"]`);
                    if(chk && chk.checked){
                        const ms = document.querySelector(`input[name="morning_start[${day}]"]`).value;
                        const me = document.querySelector(`input[name="morning_end[${day}]"]`).value;
                        const as = document.querySelector(`input[name="afternoon_start[${day}]"]`).value;
                        const ae = document.querySelector(`input[name="afternoon_end[${day}]"]`).value;
                        if(ms && me && ms>=me){ alert(`Error en la mañana del día ${day}`); e.preventDefault(); return; }
                        if(as && ae && as>=ae){ alert(`Error en la tarde del día ${day}`); e.preventDefault(); return; }
                        if(me && as && me>=as){ alert(`El turno de tarde debe comenzar después de la mañana del día ${day}`); e.preventDefault(); return; }
                        if(!ms && !as){ alert(`Debe configurar al menos un horario para el día ${day}`); e.preventDefault(); return; }
                    }
                }
            });
        });
    </script>
</body>
</html>
