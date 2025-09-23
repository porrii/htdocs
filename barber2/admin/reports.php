<?php
require_once '../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Filtros
$where = [];
$params = [];

if (!empty($_GET['from_date'])) {
    $where[] = "appointment_date >= :from_date";
    $params[':from_date'] = $_GET['from_date'];
}
if (!empty($_GET['to_date'])) {
    $where[] = "appointment_date <= :to_date";
    $params[':to_date'] = $_GET['to_date'];
}
if (!empty($_GET['client_name'])) {
    $where[] = "client_name LIKE :client_name";
    $params[':client_name'] = "%" . $_GET['client_name'] . "%";
}
if (!empty($_GET['status'])) {
    $where[] = "status = :status";
    $params[':status'] = $_GET['status'];
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Query con JOIN para sacar también info del servicio
$sql = "SELECT a.*, s.name AS service_name, s.price 
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        $whereSql
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Totales
$totalAppointments = count($appointments);
$totalRevenue = array_sum(array_map(fn($a) => $a['price'], $appointments));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Informes de Citas - Panel Admin</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">

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
                <a href="schedule.php" class="block px-6 py-3 hover:bg-gray-700">Horarios</a>
                <a href="reports.php" class="block px-6 py-3 bg-gray-700">Informes</a>
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
                    <a href="index.php" class="block px-4 py-2 hover:bg-gray-700">Dashboard</a>
                    <a href="appointments.php" class="block px-4 py-2 hover:bg-gray-700">Citas</a>
                    <a href="services.php" class="block px-4 py-2 hover:bg-gray-700">Servicios</a>
                    <a href="videos.php" class="block px-4 py-2 hover:bg-gray-700">Videos</a>
                    <a href="products.php" class="block px-4 py-2 hover:bg-gray-700">Productos</a>
                    <a href="schedule.php" class="block px-4 py-2 hover:bg-gray-700">Horarios</a>
                    <a href="reports.php" class="block px-4 py-2 bg-gray-700">Informes</a>
                    <a href="settings.php" class="block px-4 py-2 hover:bg-gray-700">Configuración</a>
                    <a href="logout.php" class="block px-4 py-2 hover:bg-gray-700">Cerrar Sesión</a>
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
                    <h1 class="text-2xl font-bold text-primary">Informes de Citas</h1>
                    <p class="text-muted">Consulta y exporta reportes detallados</p>
                </div>
                <div class="text-sm text-muted">
                    Total: <?= $totalAppointments ?> citas · Ingresos: €<?= number_format($totalRevenue, 2) ?>
                </div>
            </header>

            <!-- Filters -->
            <section class="p-6 bg-gray-50 border-b">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Desde</label>
                        <input type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>"
                            class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hasta</label>
                        <input type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>"
                            class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cliente</label>
                        <input type="text" name="client_name" placeholder="Nombre"
                            value="<?= htmlspecialchars($_GET['client_name'] ?? '') ?>"
                            class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border rounded-md">
                            <option value="">Todos</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="confirmed" <?= ($_GET['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmada</option>
                            <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                            <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completada</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex gap-3">
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-cyan-800">
                            Filtrar
                        </button>
                        <a href="reports.php" class="px-4 py-2 border rounded-md hover:bg-gray-100">
                            Limpiar
                        </a>
                        <button type="button" onclick="exportPDF()" class="bg-secondary text-white px-4 py-2 rounded-md hover:bg-amber-600">
                            Exportar PDF
                        </button>
                    </div>
                </form>
            </section>

            <!-- Results -->
            <main class="p-6 flex-1 overflow-auto">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse bg-white shadow-md rounded-lg overflow-hidden">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-left">Hora</th>
                                <th class="px-4 py-2 text-left">Cliente</th>
                                <th class="px-4 py-2 text-left">Servicio</th>
                                <th class="px-4 py-2 text-left">Estado</th>
                                <th class="px-4 py-2 text-left">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments): ?>
                                <?php foreach ($appointments as $a): ?>
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-4 py-2"><?= htmlspecialchars($a['appointment_date']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars(substr($a['appointment_time'],0,5)) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($a['client_name']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($a['service_name']) ?></td>
                                        <td class="px-4 py-2 capitalize">
                                            <?php 
                                            $colors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'confirmed' => 'bg-blue-100 text-blue-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                            ];
                                            $statusClass = $colors[$a['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 rounded text-xs <?= $statusClass ?>">
                                                <?= htmlspecialchars($a['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">€<?= number_format($a['price'],2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        No se encontraron citas con los filtros seleccionados
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        function exportPDF() {
            const params = new URLSearchParams(window.location.search);
            const url = "api/reports_export.php?" + params.toString();
            window.open(url, '_blank'); // '_blank' abre en nueva pestaña
        }
    </script>
</body>
</html>
