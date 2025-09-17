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

// Handle appointment actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    
    if ($action === 'cancel' && $appointmentId) {
        try {
            // Get appointment details before canceling
            $query = "SELECT a.*, s.name as service_name FROM appointments a 
                      JOIN services s ON a.service_id = s.id 
                      WHERE a.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $appointmentId);
            $stmt->execute();
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($appointment) {
                // Update appointment status
                $query = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $appointmentId);
                
                if ($stmt->execute()) {
                    // Send cancellation email
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

// Handle URL parameters for quick actions
if (isset($_GET['cancel'])) {
    $appointmentId = (int)$_GET['cancel'];
    // Auto-submit cancel form
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (confirm('¿Estás seguro de que quieres cancelar esta cita?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type='hidden' name='action' value='cancel'>
                    <input type='hidden' name='appointment_id' value='$appointmentId'>
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>";
}

// Get all appointments with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$query = "SELECT a.*, s.name as service_name 
          FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          ORDER BY a.appointment_date DESC, a.appointment_time DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM appointments";
$countStmt = $db->prepare($countQuery);
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
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6 border-b">
                <h1 class="text-xl font-bold text-primary">FSA Studio</h1>
                <p class="text-sm text-muted">Panel de Administración</p>
            </div>
            
            <nav class="mt-6">
                <a href="index.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
                
                <a href="appointments.php" class="sidebar-link active flex items-center px-6 py-3 text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v16a2 2 0 002 2z"></path>
                    </svg>
                    Citas
                </a>
                
                <a href="services.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Servicios
                </a>
                
                <a href="products.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Productos/Herramientas
                </a>
                
                <a href="videos.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Videos
                </a>
                
                <a href="schedule.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Horarios
                </a>
                
                <a href="settings.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Configuración
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6 border-t">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-primary"><?php echo $_SESSION['admin_username']; ?></p>
                        <p class="text-xs text-muted">Administrador</p>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center text-red-600 hover:text-red-700 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Cerrar Sesión
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-primary">Gestión de Citas</h1>
                        <p class="text-muted">Administra todas las citas de la barbería</p>
                    </div>
                    <div class="text-sm text-muted">
                        Total: <?php echo $totalAppointments; ?> citas
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="p-6">
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
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">
                                    <?php echo htmlspecialchars($appointment['service_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">
                                    <?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">
                                    <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?>
                                </td>
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
                                        <option value="<?php echo $value; ?>" <?php echo $appointment['status'] === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" 
                                            class="text-red-600 hover:text-red-700 mr-3"
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
                                <td colspan="6" class="px-6 py-4 text-center text-muted">
                                    No hay citas registradas
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($totalPages > 1): ?>
                <div class="flex justify-center mt-6">
                    <nav class="flex space-x-2">
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="px-3 py-2 rounded-lg <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white text-muted hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
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
            <button onclick="hideNotesModal()" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary/90">
                Cerrar
            </button>
        </div>
    </div>

    <script>
        function updateStatus(appointmentId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" value="${appointmentId}">
                <input type="hidden" name="status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function cancelAppointment(appointmentId) {
            if (confirm('¿Estás seguro de que quieres cancelar esta cita? Se enviará un email de cancelación al cliente.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="appointment_id" value="${appointmentId}">
                `;
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
