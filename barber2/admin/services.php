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

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $duration = (int)($_POST['duration'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $image_url = $_POST['image_url'] ?? '';
        
        if ($name && $duration && $price) {
            $query = "INSERT INTO services (name, description, duration, price, image_url) VALUES (:name, :description, :duration, :price, :image_url)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':duration', $duration);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':image_url', $image_url);
            
            if ($stmt->execute()) {
                $message = 'Servicio creado exitosamente';
            } else {
                $error = 'Error al crear el servicio';
            }
        } else {
            $error = 'Por favor, completa todos los campos requeridos';
        }
    }
    
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $duration = (int)($_POST['duration'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $image_url = $_POST['image_url'] ?? '';
        $active = isset($_POST['active']) ? 1 : 0;
        
        if ($id && $name && $duration && $price) {
            $query = "UPDATE services SET name = :name, description = :description, duration = :duration, price = :price, image_url = :image_url, active = :active WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':duration', $duration);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':active', $active);
            
            if ($stmt->execute()) {
                $message = 'Servicio actualizado exitosamente';
            } else {
                $error = 'Error al actualizar el servicio';
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $query = "UPDATE services SET active = 0 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $message = 'Servicio eliminado exitosamente';
            } else {
                $error = 'Error al eliminar el servicio';
            }
        }
    }
}

// Get all services
$query = "SELECT * FROM services ORDER BY active DESC, name";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get service for editing
$editService = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $query = "SELECT * FROM services WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $editId);
    $stmt->execute();
    $editService = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios - FSA Studio Admin</title>
    
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
                
                <a href="services.php" class="sidebar-link active flex items-center px-6 py-3 text-primary">
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
                        <h1 class="text-2xl font-bold text-primary">Gestión de Servicios</h1>
                        <p class="text-muted">Administra los servicios de la barbería</p>
                    </div>
                    <button onclick="showCreateModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                        Nuevo Servicio
                    </button>
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

                <!-- Services Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Duración</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($services as $service): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <img class="h-12 w-12 rounded-lg object-cover" 
                                                 src="<?php echo $service['image_url'] ?: '/placeholder.svg?height=48&width=48'; ?>" 
                                                 alt="<?php echo htmlspecialchars($service['name']); ?>">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-primary"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-muted"><?php echo htmlspecialchars($service['description']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">
                                    <?php echo $service['duration']; ?> min
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-secondary">
                                    €<?php echo number_format($service['price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $service['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $service['active'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="?edit=<?php echo $service['id']; ?>" class="text-primary hover:text-primary/80 mr-3">Editar</a>
                                    <button onclick="deleteService(<?php echo $service['id']; ?>)" class="text-red-600 hover:text-red-700">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="serviceModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 id="modalTitle" class="text-2xl font-bold text-primary mb-6">Nuevo Servicio</h3>
            <form id="serviceForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="serviceId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-muted mb-2">Nombre *</label>
                        <input type="text" name="name" id="serviceName" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-muted mb-2">Descripción</label>
                        <textarea name="description" id="serviceDescription" rows="3" 
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Duración (min) *</label>
                            <input type="number" name="duration" id="serviceDuration" required min="1" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Precio (€) *</label>
                            <input type="number" name="price" id="servicePrice" required min="0" step="0.01" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-muted mb-2">URL de Imagen</label>
                        <input type="url" name="image_url" id="serviceImageUrl" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div id="activeCheckbox" class="hidden">
                        <label class="flex items-center">
                            <input type="checkbox" name="active" id="serviceActive" class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-muted">Servicio activo</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="hideModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-muted hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Servicio';
            document.getElementById('formAction').value = 'create';
            document.getElementById('serviceForm').reset();
            document.getElementById('activeCheckbox').classList.add('hidden');
            document.getElementById('serviceModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('serviceModal').classList.add('hidden');
        }

        function deleteService(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este servicio?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        <?php if($editService): ?>
        // Auto-open edit modal if editing
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalTitle').textContent = 'Editar Servicio';
            document.getElementById('formAction').value = 'update';
            document.getElementById('serviceId').value = '<?php echo $editService['id']; ?>';
            document.getElementById('serviceName').value = '<?php echo htmlspecialchars($editService['name']); ?>';
            document.getElementById('serviceDescription').value = '<?php echo htmlspecialchars($editService['description']); ?>';
            document.getElementById('serviceDuration').value = '<?php echo $editService['duration']; ?>';
            document.getElementById('servicePrice').value = '<?php echo $editService['price']; ?>';
            document.getElementById('serviceImageUrl').value = '<?php echo htmlspecialchars($editService['image_url']); ?>';
            document.getElementById('serviceActive').checked = <?php echo $editService['active'] ? 'true' : 'false'; ?>;
            document.getElementById('activeCheckbox').classList.remove('hidden');
            document.getElementById('serviceModal').classList.remove('hidden');
        });
        <?php endif; ?>
    </script>
</body>
</html>
