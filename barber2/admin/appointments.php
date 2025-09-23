<?php
require_once '../config/config.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';
require_once '../classes/EmailService.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle appointment actions (same as before)
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    
    if ($action === 'cancel' && $appointmentId) {
        try {
            $query = "SELECT a.*, s.name as service_name FROM appointments a 
                      JOIN services s ON a.service_id = s.id 
                      WHERE a.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $appointmentId);
            $stmt->execute();
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($appointment) {
                $query = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $appointmentId);
                if ($stmt->execute()) {
                    $emailService = new EmailService();
                    $emailService->sendCancellationEmail($appointment);
                    $message = 'Cita cancelada exitosamente';
                } else {
                    $error = 'Error al cancelar la cita';
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($action === 'update_status' && $appointmentId) {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            $query = "UPDATE appointments SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $appointmentId);
            if ($stmt->execute()) {
                $message = 'Estado actualizado exitosamente';
            } else {
                $error = 'Error al actualizar el estado';
            }
        }
    }
}

// Handle GET filters
$filter_date = $_GET['filter_date'] ?? '';
$filter_client = $_GET['filter_client'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$whereClauses = [];
$params = [];

if ($filter_date) {
    $whereClauses[] = "a.appointment_date = :filter_date";
    $params[':filter_date'] = $filter_date;
}
if ($filter_client) {
    $whereClauses[] = "a.client_name LIKE :filter_client";
    $params[':filter_client'] = "%$filter_client%";
}
if ($filter_status) {
    $whereClauses[] = "a.status = :filter_status";
    $params[':filter_status'] = $filter_status;
}

$whereSQL = $whereClauses ? "WHERE " . implode(' AND ', $whereClauses) : "";

$query = "SELECT a.*, s.name as service_name 
          FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          $whereSQL
          ORDER BY a.appointment_date DESC, a.appointment_time DESC
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM appointments a $whereSQL";
$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$totalAppointments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalAppointments / $limit);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Citas - FSA Studio Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

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
.sidebar-link { transition: all 0.2s ease; }
.sidebar-link:hover { background: rgba(22, 78, 99, 0.1); transform: translateX(4px); }
.sidebar-link.active { background: #164e63; color: white; }
</style>
</head>
<body class="font-body bg-gray-50">

    <div class="flex h-screen">
        <!-- Sidebar Desktop -->
        <div class="w-64 bg-gray-900 text-white hidden md:block">
            <div class="p-6"><h2 class="text-xl font-bold">Panel Admin</h2></div>
            <nav class="mt-6">
                <a href="index.php" class="block px-6 py-3 hover:bg-gray-700">Dashboard</a>
                <a href="appointments.php" class="block px-6 py-3 bg-gray-700">Citas</a>
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
                    <a href="index.php" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a>
                    <a href="appointments.php" class="block px-4 py-2 rounded bg-gray-700">Citas</a>
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
                btn.addEventListener('click', () => menu.classList.toggle('hidden'));
            </script>

            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-4 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Gestión de Citas</h1>
                    <p class="text-muted">Administra todas las citas de la barbería</p>
                </div>
                <div class="text-sm text-muted">
                    Total: <?php echo $totalAppointments; ?> citas
                </div>
            </header>

            <!-- Filters Panel -->
            <div class="p-4 sm:p-6 bg-white rounded-xl shadow-sm mt-4 max-w-full sm:max-w-7xl mx-auto">
                <!-- Toggle button móvil -->
                <button id="toggleFilters" class="sm:hidden w-full text-left text-primary font-semibold mb-2 flex justify-between items-center">
                    Filtros
                    <svg class="w-5 h-5 transform transition-transform" id="toggleIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Formulario de filtros -->
                <form id="filtersForm" method="GET" class="flex flex-col sm:flex-row gap-4 sm:items-end hidden sm:flex w-full">
                    <!-- Fecha -->
                    <div class="flex flex-col flex-1 min-w-[120px]">
                        <label class="text-sm text-muted mb-1">Fecha</label>
                        <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date ?? ''); ?>" class="px-3 py-2 border rounded-lg text-sm w-full">
                    </div>

                    <!-- Cliente -->
                    <div class="flex flex-col flex-1 min-w-[150px]">
                        <label class="text-sm text-muted mb-1">Cliente</label>
                        <input type="text" name="filter_client" value="<?php echo htmlspecialchars($filter_client ?? ''); ?>" placeholder="Nombre del cliente" class="px-3 py-2 border rounded-lg text-sm w-full">
                    </div>

                    <!-- Estado -->
                    <div class="flex flex-col flex-1 min-w-[130px]">
                        <label class="text-sm text-muted mb-1">Estado</label>
                        <select name="filter_status" class="px-3 py-2 border rounded-lg text-sm w-full">
                            <option value="">Todos</option>
                            <option value="pending" <?php if(($filter_status ?? '')==='pending') echo 'selected'; ?>>Pendiente</option>
                            <option value="confirmed" <?php if(($filter_status ?? '')==='confirmed') echo 'selected'; ?>>Confirmada</option>
                            <option value="cancelled" <?php if(($filter_status ?? '')==='cancelled') echo 'selected'; ?>>Cancelada</option>
                            <option value="completed" <?php if(($filter_status ?? '')==='completed') echo 'selected'; ?>>Completada</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-2 flex-wrap">
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 text-sm">Filtrar</button>
                        <a href="appointments.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">Limpiar</a>
                    </div>
                </form>
            </div>

            <script>
                const toggleBtn = document.getElementById('toggleFilters');
                const filtersForm = document.getElementById('filtersForm');
                const toggleIcon = document.getElementById('toggleIcon');

                toggleBtn.addEventListener('click', () => {
                    filtersForm.classList.toggle('hidden');
                    toggleIcon.classList.toggle('rotate-180');
                });
            </script>

            <!-- Messages -->
            <main class="p-4 sm:p-6 flex-1">
                <?php if($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <!-- Appointments Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
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
                            <?php foreach($appointments as $appointment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-primary"><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                        <div class="text-sm text-muted"><?php echo htmlspecialchars($appointment['client_email']); ?></div>
                                        <?php if($appointment['client_phone']): ?>
                                        <div class="text-sm text-muted"><?php echo htmlspecialchars($appointment['client_phone']); ?></div>
                                        <?php endif; ?>
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
                                    <select onchange="updateStatus(<?php echo $appointment['id']; ?>, this.value)" 
                                            class="px-2 py-1 text-xs leading-5 font-semibold rounded-full border-0 <?php echo $statusColors[$appointment['status']]; ?>">
                                        <?php foreach($statusLabels as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $appointment['status'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex flex-col sm:flex-row gap-2 sm:gap-3">
                                    <button onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" 
                                            class="text-red-600 hover:text-red-700"
                                            <?php echo $appointment['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                        Cancelar
                                    </button>
                                    <?php if($appointment['notes']): ?>
                                    <button onclick="showNotes('<?php echo htmlspecialchars($appointment['notes']); ?>')" 
                                            class="text-primary hover:text-primary/80">
                                        Ver Notas
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($appointments)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-muted">No hay citas registradas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($totalPages > 1): ?>
                <div class="flex justify-center mt-6 flex-wrap gap-2">
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&filter_date=<?php echo urlencode($filter_date); ?>&filter_client=<?php echo urlencode($filter_client); ?>&filter_status=<?php echo urlencode($filter_status); ?>" 
                    class="px-3 py-2 rounded-lg <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white text-muted hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Notes Modal -->
    <div id="notesModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-primary mb-4">Notas de la Cita</h3>
            <div id="notesContent" class="text-muted mb-6"></div>
            <button onclick="hideNotesModal()" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary/90">Cerrar</button>
        </div>
    </div>

    <script>
        function updateStatus(appointmentId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="appointment_id" value="${appointmentId}">
                            <input type="hidden" name="status" value="${status}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function cancelAppointment(appointmentId) {
            if(confirm('¿Estás seguro de que quieres cancelar esta cita? Se enviará un email de cancelación al cliente.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="appointment_id" value="${appointmentId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showNotes(notes) {
            document.getElementById('notesContent').textContent = notes;
            document.getElementById('notesModal').classList.remove('hidden');
        }

        function hideNotesModal() {
            document.getElementById('notesModal').classList.add('hidden');
        }
    </script>
</body>
</html>
