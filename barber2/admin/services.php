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
            
            if ($stmt->execute()) $message = 'Servicio creado exitosamente';
            else $error = 'Error al crear el servicio';
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
            
            if ($stmt->execute()) $message = 'Servicio actualizado exitosamente';
            else $error = 'Error al actualizar el servicio';
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $query = "UPDATE services SET active = 0 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) $message = 'Servicio eliminado exitosamente';
            else $error = 'Error al eliminar el servicio';
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
<title>Gestión de Servicios - Panel Admin</title>

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
                <a href="services.php" class="block px-6 py-3 bg-gray-700">Servicios</a>
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
                    <a href="appointments.php" class="block px-4 py-2 rounded hover:bg-gray-700">Citas</a>
                    <a href="services.php" class="block px-4 py-2 rounded bg-gray-700">Servicios</a>
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
                    <h1 class="text-2xl font-bold text-primary">Gestión de Servicios</h1>
                    <p class="text-muted">Administra todos los servicios de la barbería</p>
                </div>
                <div>
                    <button onclick="showCreateModal()" class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 text-sm sm:text-base">
                        Nuevo Servicio
                    </button>
                </div>
            </header>

            <!-- Content -->
            <main class="p-4 sm:p-6 lg:p-8">
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
                <div class="overflow-x-auto bg-white rounded-xl shadow-sm">
                    <table class="w-full min-w-[600px]">
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
                                                src="<?php echo $service['image_url'] ?: '../public/placeholder.svg?height=48&width=48'; ?>" 
                                                alt="<?php echo htmlspecialchars($service['name']); ?>">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-primary"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-muted"><?php echo htmlspecialchars($service['description']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted"><?php echo $service['duration']; ?> min</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-secondary">€<?php echo number_format($service['price'],2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $service['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $service['active'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-3">
                                    <a href="?edit=<?php echo $service['id']; ?>" class="text-primary hover:text-primary/80">Editar</a>
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
    <div id="serviceModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden p-4">
        <div class="bg-white rounded-2xl p-6 sm:p-8 max-w-md w-full mx-auto">
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                
                <div class="flex justify-end space-x-4 mt-6 flex-wrap">
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
