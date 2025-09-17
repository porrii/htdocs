<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();

// Handle form submissions
$mensaje = '';
$tipo_mensaje = '';

if ($_POST) {
    if (isset($_POST['agregar_video'])) {
        $titulo = sanitizeInput($_POST['titulo']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $youtube_url = sanitizeInput($_POST['youtube_url']);
        $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        
        // Extract YouTube ID from URL
        $youtube_id = extractYoutubeId($youtube_url);
        
        if (empty($youtube_id)) {
            $mensaje = 'URL de YouTube no válida.';
            $tipo_mensaje = 'error';
        } else {
            if (agregarVideo($titulo, $descripcion, $youtube_id, $categoria_id)) {
                $mensaje = 'Video agregado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al agregar el video.';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['editar_video'])) {
        $id = intval($_POST['id']);
        $titulo = sanitizeInput($_POST['titulo']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $youtube_url = sanitizeInput($_POST['youtube_url']);
        $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        
        // Extract YouTube ID from URL
        $youtube_id = extractYoutubeId($youtube_url);
        
        if (empty($youtube_id)) {
            $mensaje = 'URL de YouTube no válida.';
            $tipo_mensaje = 'error';
        } else {
            if (actualizarVideo($id, $titulo, $descripcion, $youtube_id, $categoria_id)) {
                $mensaje = 'Video actualizado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar el video.';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['eliminar_video'])) {
        $id = intval($_POST['id']);
        
        if (eliminarVideo($id)) {
            $mensaje = 'Video eliminado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar el video.';
            $tipo_mensaje = 'error';
        }
    }
    
    // Category management
    if (isset($_POST['agregar_categoria'])) {
        $nombre = sanitizeInput($_POST['categoria_nombre']);
        $descripcion = sanitizeInput($_POST['categoria_descripcion']);
        
        if (agregarCategoriaVideo($nombre, $descripcion)) {
            $mensaje = 'Categoría agregada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al agregar la categoría.';
            $tipo_mensaje = 'error';
        }
    }
}

// Get all videos with categories
$videos = $db->fetchAll("
    SELECT v.*, c.nombre as categoria_nombre 
    FROM videos v 
    LEFT JOIN categorias_videos c ON v.categoria_id = c.id 
    WHERE v.activo = 1 
    ORDER BY v.fecha_creacion DESC
");

// Get all categories
$categorias = getCategoriesVideos();

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Videos - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
            <!-- Header -->
            <div class="dashboard-header fade-in-up">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="dashboard-title">
                        <i class="fas fa-video"></i>
                        Gestión de Videos
                    </h1>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-outline-modern" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-tags"></i>
                            Gestionar Categorías
                        </button>
                        <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                            <i class="fas fa-plus"></i>
                            Agregar Video
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($mensaje): ?>
            <div class="alert-modern alert-<?php echo $tipo_mensaje; ?> fade-in-up">
                <div class="alert-icon">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                </div>
                <div><?php echo $mensaje; ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Videos Grid -->
            <div class="row g-4">
                <?php foreach ($videos as $video): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="data-card fade-in-up">
                        <div class="position-relative">
                            <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/maxresdefault.jpg" 
                                 alt="<?php echo htmlspecialchars($video['titulo']); ?>" 
                                 class="w-100" style="height: 200px; object-fit: cover;">
                            
                            <?php if ($video['categoria_nombre']): ?>
                            <span class="position-absolute top-0 start-0 m-2 badge bg-primary">
                                <?php echo htmlspecialchars($video['categoria_nombre']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <button class="btn btn-light btn-lg rounded-circle" 
                                        onclick="previewVideo('<?php echo htmlspecialchars($video['youtube_id']); ?>')">
                                    <i class="fas fa-play text-primary"></i>
                                </button>
                            </div>
                            
                            <div class="position-absolute top-0 end-0 p-2">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="editVideo(<?php echo $video['id']; ?>)">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteVideo(<?php echo $video['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="data-body">
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($video['titulo']); ?></h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($video['descripcion']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($video['fecha_creacion'])); ?>
                                </small>
                                <a href="https://www.youtube.com/watch?v=<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fab fa-youtube"></i>
                                    Ver en YouTube
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($videos)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-video"></i>
                        <h3>No hay videos</h3>
                        <p>Comienza agregando el primer video.</p>
                        <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                            <i class="fas fa-plus"></i>
                            Agregar Video
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Video Modal -->
<div class="modal fade" id="addVideoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="titulo" class="form-label">Título del Video *</label>
                        <input type="text" class="form-control-modern" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="descripcion" class="form-label">Descripción *</label>
                        <textarea class="form-control-modern" id="descripcion" name="descripcion" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="categoria_id" class="form-label">Categoría</label>
                        <select class="form-control-modern form-select-modern" id="categoria_id" name="categoria_id">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="youtube_url" class="form-label">URL de YouTube *</label>
                        <input type="url" class="form-control-modern" id="youtube_url" name="youtube_url" 
                               placeholder="https://www.youtube.com/watch?v=..." required>
                        <small class="text-muted">Pega la URL completa del video de YouTube</small>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar_video" class="btn-modern">
                        <i class="fas fa-plus"></i>
                        Agregar Video
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Video Modal -->
<div class="modal fade" id="editVideoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editVideoForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="edit_titulo" class="form-label">Título del Video *</label>
                        <input type="text" class="form-control-modern" id="edit_titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción *</label>
                        <textarea class="form-control-modern" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_categoria_id" class="form-label">Categoría</label>
                        <select class="form-control-modern form-select-modern" id="edit_categoria_id" name="categoria_id">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_youtube_url" class="form-label">URL de YouTube *</label>
                        <input type="url" class="form-control-modern" id="edit_youtube_url" name="youtube_url" required>
                    </div>
                    
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_video" class="btn-modern">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Categorías</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Add new category form -->
                <form method="POST" class="mb-4">
                    <h6>Agregar Nueva Categoría</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control-modern" name="categoria_nombre" 
                                   placeholder="Nombre de la categoría" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="agregar_categoria" class="btn-modern w-100">
                                <i class="fas fa-plus"></i>
                                Agregar
                            </button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <textarea class="form-control-modern" name="categoria_descripcion" 
                                  placeholder="Descripción (opcional)" rows="2"></textarea>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </form>
                
                <!-- Existing categories -->
                <h6>Categorías Existentes</h6>
                <?php if (!empty($categorias)): ?>
                <div class="list-group">
                    <?php foreach ($categorias as $categoria): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                            <?php if ($categoria['descripcion']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($categoria['descripcion']); ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteCategory(<?php echo $categoria['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay categorías creadas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Preview Video Modal -->
<div class="modal fade" id="previewVideoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="previewFrame" src="/placeholder.svg" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Video data for editing
    const videos = <?php echo json_encode($videos); ?>;
    
    function editVideo(id) {
        const video = videos.find(v => v.id == id);
        if (video) {
            document.getElementById('edit_id').value = video.id;
            document.getElementById('edit_titulo').value = video.titulo;
            document.getElementById('edit_descripcion').value = video.descripcion;
            document.getElementById('edit_categoria_id').value = video.categoria_id || '';
            document.getElementById('edit_youtube_url').value = `https://www.youtube.com/watch?v=${video.youtube_id}`;
            
            const modal = new bootstrap.Modal(document.getElementById('editVideoModal'));
            modal.show();
        }
    }
    
    function deleteVideo(id) {
        const video = videos.find(v => v.id == id);
        if (video && confirm(`¿Estás seguro de que quieres eliminar el video "${video.titulo}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="eliminar_video" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteCategory(id) {
        if (confirm('¿Estás seguro de que quieres eliminar esta categoría? Los videos asignados quedarán sin categoría.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="categoria_id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="eliminar_categoria" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function previewVideo(youtubeId) {
        const iframe = document.getElementById('previewFrame');
        iframe.src = `https://www.youtube.com/embed/${youtubeId}`;
        
        const modal = new bootstrap.Modal(document.getElementById('previewVideoModal'));
        modal.show();
    }
    
    // Clear iframe when modal is closed
    document.getElementById('previewVideoModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('previewFrame').src = '';
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-modern');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>
</body>
</html>
