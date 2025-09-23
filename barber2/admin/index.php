<?php
require_once '../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';

// Get dashboard statistics
$database = new Database();
$db = $database->getConnection();

// Get today's appointments
$today = date('Y-m-d');
$query = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = :today AND status != 'cancelled'";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$todayAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get this week's appointments
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$query = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN :start AND :end AND status != 'cancelled'";
$stmt = $db->prepare($query);
$stmt->bindParam(':start', $weekStart);
$stmt->bindParam(':end', $weekEnd);
$stmt->execute();
$weekAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total services
$query = "SELECT COUNT(*) as count FROM services WHERE active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$totalServices = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total videos
$query = "SELECT COUNT(*) as count FROM videos WHERE active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$totalVideos = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent appointments
$query = "SELECT a.*, s.name as service_name FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          WHERE a.appointment_date >= :today 
          ORDER BY a.appointment_date, a.appointment_time 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Administración - FSA Studio</title>

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
<body class="font-body bg-gray-50">

    <div class="flex h-screen">
        <!-- Sidebar Desktop -->
        <div class="w-64 bg-gray-900 text-white hidden md:block">
            <div class="p-6">
                <h2 class="text-xl font-bold">Panel Admin</h2>
            </div>
            <nav class="mt-6">
                <a href="index.php" class="block px-6 py-3 bg-gray-700">Dashboard</a>
                <a href="appointments.php" class="block px-6 py-3 hover:bg-gray-700">Citas</a>
                <a href="services.php" class="block px-6 py-3 hover:bg-gray-700">Servicios</a>
                <a href="videos.php" class="block px-6 py-3 hover:bg-gray-700">Videos</a>
                <a href="products.php" class="block px-6 py-3 hover:bg-gray-700">Productos</a>
                <a href="schedule.php" class="block px-6 py-3 hover:bg-gray-700">Horarios</a>
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
                    <a href="index.php" class="block px-4 py-2 bg-gray-700 rounded">Dashboard</a>
                    <a href="appointments.php" class="block px-4 py-2 rounded hover:bg-gray-700">Citas</a>
                    <a href="services.php" class="block px-4 py-2 rounded hover:bg-gray-700">Servicios</a>
                    <a href="videos.php" class="block px-4 py-2 rounded hover:bg-gray-700">Videos</a>
                    <a href="products.php" class="block px-4 py-2 rounded hover:bg-gray-700">Productos</a>
                    <a href="schedule.php" class="block px-4 py-2 rounded hover:bg-gray-700">Horarios</a>
                    <a href="reports.php" class="block px-4 py-2 rounded hover:bg-gray-700">Informes</a>
                    <a href="settings.php" class="block px-4 py-2 rounded hover:bg-gray-700">Configuración</a>
                    <a href="logout.php" class="block px-4 py-2 rounded hover:bg-gray-700">Cerrar Sesión</a>
                </nav>
            </div>

            <script>
                const btn = document.getElementById('mobile-menu-btn');
                const menu = document.getElementById('mobile-menu');
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                });
            </script>

            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-4 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Dashboard</h1>
                    <p class="text-muted">Bienvenido al panel de administración</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted"><?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../index.html" target="_blank" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors text-sm">
                        Ver Sitio Web
                    </a>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-4 sm:p-6 flex-1 overflow-auto">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Citas Hoy -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v16a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted">Citas Hoy</p>
                                <p class="text-2xl font-bold text-primary"><?php echo $todayAppointments; ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Esta Semana -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-3 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2V7a2 2 0 012-2h2a2 2 0 002 2v2a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 00-2 2h-2a2 2 0 00-2 2v6a2 2 0 01-2 2H9z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted">Esta Semana</p>
                                <p class="text-2xl font-bold text-primary"><?php echo $weekAppointments; ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Servicios -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="bg-yellow-100 p-3 rounded-lg">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted">Servicios</p>
                                <p class="text-2xl font-bold text-primary"><?php echo $totalServices; ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Videos -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="bg-purple-100 p-3 rounded-lg">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted">Videos</p>
                                <p class="text-2xl font-bold text-primary"><?php echo $totalVideos; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold text-primary">Próximas Citas</h2>
                        <a href="appointments.php" class="text-secondary hover:text-secondary/80 font-medium">Ver todas</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Servicio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($recentAppointments as $appointment): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-primary"><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                            <div class="text-sm text-muted"><?php echo htmlspecialchars($appointment['client_email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted"><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted"><?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted"><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            'completed' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Pendiente',
                                            'confirmed' => 'Confirmada',
                                            'cancelled' => 'Cancelada',
                                            'completed' => 'Completada'
                                        ];
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColors[$appointment['status']]; ?>">
                                            <?php echo $statusLabels[$appointment['status']]; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="appointments.php?edit=<?php echo $appointment['id']; ?>" class="text-primary hover:text-primary/80 mr-3">Editar</a>
                                        <a href="appointments.php?cancel=<?php echo $appointment['id']; ?>" class="text-red-600 hover:text-red-700">Cancelar</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if(empty($recentAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-muted">
                                        No hay citas próximas
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</body>
</html>
