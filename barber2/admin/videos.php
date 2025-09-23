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

// Get all videos
$stmt = $db->query("SELECT * FROM videos ORDER BY created_at DESC");
$videos = $stmt->fetchAll();

function getYouTubeThumbnail($video_url, $custom_thumbnail = null) {
    if ($custom_thumbnail) return $custom_thumbnail;

    // Extraer el ID del video
    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([\w-]+)/', $video_url, $matches);
    if (isset($matches[1])) {
        return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
    }
    // fallback
    return '../public/barbershop-video-thumbnail.jpg';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Videos - Panel Admin</title>
    
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
                <a href="videos.php" class="block px-6 py-3 bg-gray-700">Videos</a>
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
                    <a href="services.php" class="block px-4 py-2 rounded hover:bg-gray-700">Servicios</a>
                    <a href="videos.php" class="block px-4 py-2 rounded bg-gray-700">Videos</a>
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
            <header class="bg-white shadow-sm border-b px-6 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-primary">Gestión de Videos</h1>
                <button onclick="openAddModal()" class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700">
                    Agregar Video
                </button>
            </header>

            <!-- Content -->
            <main class="p-6">
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Videos Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($videos as $video): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="aspect-video bg-gray-200 relative">
                                <img src="<?php echo htmlspecialchars(getYouTubeThumbnail($video['video_url'], $video['thumbnail_url'])); ?>" 
                                    alt="<?php echo htmlspecialchars($video['title']); ?>"
                                    class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                    <button onclick="playVideo('<?php echo htmlspecialchars($video['video_url']); ?>')" 
                                            class="text-white text-4xl">▶</button>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($video['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($video['description']); ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($video['created_at'])); ?></span>
                                    <div class="flex gap-2">
                                        <button onclick="editVideo(<?php echo $video['id']; ?>)" class="text-blue-600 hover:text-blue-800">Editar</button>
                                        <button onclick="deleteVideo(<?php echo $video['id']; ?>)" class="text-red-600 hover:text-red-800">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Video Modal -->
    <div id="videoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 id="modalTitle" class="text-xl font-bold mb-4">Agregar Video</h2>
            <form id="videoForm" action="api/save_video.php" method="POST">
                <input type="hidden" id="videoId" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                    <input type="text" id="videoTitle" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea id="videoDescription" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL del Video</label>
                    <input type="url" id="videoUrl" name="video_url" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500" placeholder="https://youtube.com/watch?v=...">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL de Miniatura (opcional)</label>
                    <input type="url" id="thumbnailUrl" name="thumbnail_url" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Video Player Modal -->
    <div id="playerModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-50">
        <div class="relative w-full max-w-4xl mx-4">
            <button onclick="closePlayer()" class="absolute -top-10 right-0 text-white text-2xl">✕</button>
            <div id="videoPlayer" class="aspect-video bg-black rounded-lg overflow-hidden"></div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Agregar Video';
            document.getElementById('videoForm').reset();
            document.getElementById('videoId').value = '';
            document.getElementById('videoModal').classList.remove('hidden');
            document.getElementById('videoModal').classList.add('flex');
        }
        function editVideo(id) {
            fetch(`api/get_video.php?id=${id}`)
                .then(response => response.json())
                .then(video => {
                    document.getElementById('modalTitle').textContent = 'Editar Video';
                    document.getElementById('videoId').value = video.id;
                    document.getElementById('videoTitle').value = video.title;
                    document.getElementById('videoDescription').value = video.description;
                    document.getElementById('videoUrl').value = video.video_url;
                    document.getElementById('thumbnailUrl').value = video.thumbnail_url || '';
                    document.getElementById('videoModal').classList.remove('hidden');
                    document.getElementById('videoModal').classList.add('flex');
                });
        }
        function deleteVideo(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este video?')) {
                fetch('api/delete_video.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert('Error al eliminar el video');
                });
            }
        }
        function closeModal() {
            document.getElementById('videoModal').classList.add('hidden');
            document.getElementById('videoModal').classList.remove('flex');
        }
        function playVideo(url) {
            const player = document.getElementById('videoPlayer');
            const modal = document.getElementById('playerModal');
            let embedUrl = url;
            if (url.includes('youtube.com/watch?v=')) {
                const videoId = url.split('v=')[1].split('&')[0];
                embedUrl = `https://www.youtube.com/embed/${videoId}`;
            } else if (url.includes('youtu.be/')) {
                const videoId = url.split('youtu.be/')[1];
                embedUrl = `https://www.youtube.com/embed/${videoId}`;
            }
            player.innerHTML = `<iframe src="${embedUrl}" class="w-full h-full" frameborder="0" allowfullscreen></iframe>`;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closePlayer() {
            document.getElementById('playerModal').classList.add('hidden');
            document.getElementById('playerModal').classList.remove('flex');
            document.getElementById('videoPlayer').innerHTML = '';
        }
        document.getElementById('videoModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
        document.getElementById('playerModal').addEventListener('click', e => { if (e.target === e.currentTarget) closePlayer(); });
    </script>
</body>
</html>
