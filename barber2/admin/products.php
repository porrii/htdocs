<?php
require_once '../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get all products
$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT * FROM tools_products ORDER BY name");
$products = $stmt->fetchAll();
?>

<?php
require_once '../config/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

require_once '../config/database.php';
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get all products
$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT * FROM tools_products ORDER BY name");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Panel Admin</title>

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
                <a href="products.php" class="block px-6 py-3 bg-gray-700">Productos</a>
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
                    <a href="services.php" class="block px-4 py-2 rounded hover:bg-gray-700">Servicios</a>
                    <a href="videos.php" class="block px-4 py-2 rounded hover:bg-gray-700">Videos</a>
                    <a href="products.php" class="block px-4 py-2 rounded bg-gray-700">Productos</a>
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
                    <h1 class="text-2xl font-bold text-primary">Gestión de Productos</h1>
                    <p class="text-muted">Administra todos los productos de la barbería</p>
                </div>
                <div>
                    <button onclick="openAddModal()" class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 text-sm sm:text-base">
                        Agregar Producto
                    </button>
                </div>
            </header>

            <!-- Content -->
            <main class="p-4 sm:p-8">
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col">
                            <div class="aspect-square bg-gray-200 relative flex-shrink-0">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-500">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($product['description']); ?></p>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs text-gray-500 capitalize">
                                        <?php echo htmlspecialchars($product['type']); ?>
                                    </span>
                                    <div class="flex gap-2">
                                        <button onclick="editProduct(<?php echo $product['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            Editar
                                        </button>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 id="modalTitle" class="text-xl font-bold mb-4">Agregar Producto</h2>
            <form id="productForm" action="api/save_product.php" method="POST">
                <input type="hidden" id="productId" name="id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                    <input type="text" id="productName" name="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea id="productDescription" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                    <select id="productCategory" name="category" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">Seleccionar categoría</option>
                        <option value="tool">Herramientas</option>
                        <option value="product">Productos</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL de Imagen (opcional)</label>
                    <input type="url" id="productImage" name="image_url" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div class="flex justify-end gap-3 flex-wrap">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Agregar Producto';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function editProduct(id) {
            fetch(`api/get_product.php?id=${id}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('modalTitle').textContent = 'Editar Producto';
                    document.getElementById('productId').value = product.id;
                    document.getElementById('productName').value = product.name;
                    document.getElementById('productDescription').value = product.description;
                    document.getElementById('productCategory').value = product.type;
                    document.getElementById('productImage').value = product.image_url || '';
                    document.getElementById('productModal').classList.remove('hidden');
                    document.getElementById('productModal').classList.add('flex');
                });
        }

        function deleteProduct(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                fetch('api/delete_product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error al eliminar el producto');
                });
            }
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModal').classList.remove('flex');
        }

        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>