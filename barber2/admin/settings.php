<?php
require_once '../config/config.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_port = (int)($_POST['smtp_port'] ?? 587);
    $smtp_username = $_POST['smtp_username'] ?? '';
    $smtp_password = $_POST['smtp_password'] ?? '';
    $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';
    $appointment_interval = (int)($_POST['appointment_interval'] ?? 60);
    $booking_restriction = (int)($_POST['booking_restriction'] ?? 30);
    
    try {
        $query = "UPDATE config SET 
                  smtp_host = :smtp_host,
                  smtp_port = :smtp_port,
                  smtp_username = :smtp_username,
                  smtp_password = :smtp_password,
                  smtp_encryption = :smtp_encryption,
                  appointment_interval = :appointment_interval,
                  booking_restriction = :booking_restriction
                  WHERE id = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':smtp_host', $smtp_host);
        $stmt->bindParam(':smtp_port', $smtp_port);
        $stmt->bindParam(':smtp_username', $smtp_username);
        $stmt->bindParam(':smtp_password', $smtp_password);
        $stmt->bindParam(':smtp_encryption', $smtp_encryption);
        $stmt->bindParam(':appointment_interval', $appointment_interval);
        $stmt->bindParam(':booking_restriction', $booking_restriction);
        
        if ($stmt->execute()) {
            $message = 'Configuración actualizada exitosamente';
        } else {
            $error = 'Error al actualizar la configuración';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get current configuration
$config = getConfig();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - FSA Studio Admin</title>
    
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
                
                <a href="appointments.php" class="sidebar-link flex items-center px-6 py-3 text-muted hover:text-primary">
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
                
                <a href="settings.php" class="sidebar-link active flex items-center px-6 py-3 text-primary">
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
                        <h1 class="text-2xl font-bold text-primary">Configuración</h1>
                        <p class="text-muted">Configura los parámetros del sistema</p>
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

                <form method="POST" class="space-y-8">
                    <!-- SMTP Configuration -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-primary mb-6">Configuración SMTP</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Servidor SMTP</label>
                                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="smtp.gmail.com">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Puerto</label>
                                <input type="number" name="smtp_port" value="<?php echo $config['smtp_port'] ?? 587; ?>" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Usuario</label>
                                <input type="email" name="smtp_username" value="<?php echo htmlspecialchars($config['smtp_username'] ?? ''); ?>" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="tu-email@gmail.com">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Contraseña</label>
                                <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($config['smtp_password'] ?? ''); ?>" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Contraseña de aplicación">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Encriptación</label>
                                <select name="smtp_encryption" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="tls" <?php echo ($config['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($config['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Configuration -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-primary mb-6">Configuración de Reservas</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Intervalo entre citas (minutos)</label>
                                <input type="number" name="appointment_interval" value="<?php echo $config['appointment_interval'] ?? 60; ?>" 
                                       min="15" max="120" step="15"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <p class="text-xs text-muted mt-1">Tiempo entre cada cita disponible</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Restricción de reserva (minutos)</label>
                                <input type="number" name="booking_restriction" value="<?php echo $config['booking_restriction'] ?? 30; ?>" 
                                       min="0" max="1440"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <p class="text-xs text-muted mt-1">Tiempo mínimo antes de la cita para poder reservar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg hover:bg-primary/90 transition-colors font-medium">
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
