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
    if (isset($_POST['agregar_servicio'])) {
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $icono = sanitizeInput($_POST['icono']);
        $duracion = intval($_POST['duracion']);
        $precio = floatval($_POST['precio']);
        
        if (agregarServicio($nombre, $descripcion, $icono, $duracion, $precio)) {
            $mensaje = 'Servicio agregado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al agregar el servicio.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['editar_servicio'])) {
        $id = intval($_POST['id']);
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $icono = sanitizeInput($_POST['icono']);
        $duracion = intval($_POST['duracion']);
        $precio = floatval($_POST['precio']);
        
        if (actualizarServicio($id, $nombre, $descripcion, $icono, $duracion, $precio)) {
            $mensaje = 'Servicio actualizado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar el servicio.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['eliminar_servicio'])) {
        $id = intval($_POST['id']);
        
        if (eliminarServicio($id)) {
            $mensaje = 'Servicio eliminado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar el servicio.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['reordenar_servicios'])) {
        $orden = $_POST['orden'];
        
        if (reordenarServicios($orden)) {
            $mensaje = 'Orden actualizado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar el orden.';
            $tipo_mensaje = 'error';
        }
    }
}

// Get all services
$servicios = getAllServicios();

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">
    
    <style>
        .service-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            cursor: move;
        }
        
        .service-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .service-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        .service-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .service-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .service-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .service-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .service-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .service-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .drag-handle {
            color: var(--text-secondary);
            cursor: move;
            padding: 0.5rem;
            margin-right: 1rem;
        }
        
        .drag-handle:hover {
            color: var(--primary-color);
        }
        
        .sortable-ghost {
            opacity: 0.4;
        }
        
        .sortable-chosen {
            transform: rotate(5deg);
        }

        .data-card.fade-in-up {
            height: auto !important;         /* Evita que se estire verticalmente */
            min-height: unset !important;    /* Elimina mínimos forzados */
            overflow: visible;               /* Asegura que no se recorte el contenido */
        }
    </style>
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
                        <i class="fas fa-concierge-bell"></i>
                        Gestión de Servicios
                    </h1>
                    <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="fas fa-plus"></i>
                        Agregar Servicio
                    </button>
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
            
            <!-- Services List -->
            <div class="data-card fade-in-up">
                <div class="data-header">
                    <h3 class="data-title">
                        <i class="fas fa-list"></i>
                        Lista de Servicios
                    </h3>
                    <small class="text-muted">Arrastra para reordenar</small>
                </div>
                <div class="data-body">
                    <?php if (!empty($servicios)): ?>
                    <div id="servicesList">
                        <?php foreach ($servicios as $servicio): ?>
                        <div class="service-card" data-id="<?php echo $servicio['id']; ?>">
                            <div class="d-flex">
                                <div class="drag-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="service-header">
                                        <div class="flex-grow-1">
                                            <h4 class="service-title"><?php echo htmlspecialchars($servicio['nombre']); ?></h4>
                                            <div class="service-meta">
                                                <div class="service-meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo $servicio['duracion']; ?> min</span>
                                                </div>
                                                <?php if ($servicio['precio']): ?>
                                                <div class="service-meta-item">
                                                    <i class="fas fa-euro-sign"></i>
                                                    <span><?php echo number_format($servicio['precio'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="service-meta-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?php echo date('d/m/Y', strtotime($servicio['fecha_creacion'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="service-actions">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editService(<?php echo $servicio['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteService(<?php echo $servicio['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php if ($servicio['descripcion']): ?>
                                    <p class="service-description"><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-concierge-bell"></i>
                        <h3>No hay servicios</h3>
                        <p>Comienza agregando el primer servicio.</p>
                        <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="fas fa-plus"></i>
                            Agregar Servicio
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="nombre" class="form-label">Nombre del Servicio *</label>
                        <input type="text" class="form-control-modern" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control-modern" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="icono" class="form-label">Icono</label>
                        <div class="input-group">
                            <span class="input-group-text" id="icon-preview"><i class="fas fa-scissors"></i></span>
                            <select class="form-select form-control-modern" id="icono" name="icono">
                                <option value="fa-scissors" selected>✂️ Tijeras</option>
                                <option value="fa-brush">🖌️ Brocha</option>
                                <option value="fa-hair-dryer">💨 Secador</option>
                                <option value="fa-user">👤 Estilista</option>
                                <option value="fa-razor">🪒 Navaja</option>
                                <option value="fa-scissors" selected>✂️ Corte de Cabello</option>
                                <option value="fa-cut">💇 Corte y Peinado</option>
                                <option value="fa-palette">🎨 Coloración</option>
                                <option value="fa-spa">🧖 Tratamientos</option>
                                <option value="fa-magic">✨ Peinado Especial</option>
                                <option value="fa-user-tie">🕴️ Asesoría de Imagen</option>
                                <option value="fa-crown">👑 Servicios Premium</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="duracion" class="form-label">Duración (minutos) *</label>
                            <input type="number" class="form-control-modern" id="duracion" name="duracion" min="15" max="300" value="30" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="precio" class="form-label">Precio (€)</label>
                            <input type="number" class="form-control-modern" id="precio" name="precio" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar_servicio" class="btn-modern">
                        <i class="fas fa-plus"></i>
                        Agregar Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editServiceForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="edit_nombre" class="form-label">Nombre del Servicio *</label>
                        <input type="text" class="form-control-modern" id="edit_nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control-modern" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_icono" class="form-label">Icono</label>
                        <div class="input-group">
                            <span class="input-group-text" id="icon-preview"><i class="fas fa-scissors"></i></span>
                            <select class="form-select form-control-modern" id="edit_icono" name="icono">
                                <option value="fa-scissors" selected>✂️ Tijeras</option>
                                <option value="fa-brush">🖌️ Brocha</option>
                                <option value="fa-hair-dryer">💨 Secador</option>
                                <option value="fa-user">👤 Estilista</option>
                                <option value="fa-razor">🪒 Navaja</option>
                                <option value="fa-scissors" selected>✂️ Corte de Cabello</option>
                                <option value="fa-cut">💇 Corte y Peinado</option>
                                <option value="fa-palette">🎨 Coloración</option>
                                <option value="fa-spa">🧖 Tratamientos</option>
                                <option value="fa-magic">✨ Peinado Especial</option>
                                <option value="fa-user-tie">🕴️ Asesoría de Imagen</option>
                                <option value="fa-crown">👑 Servicios Premium</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_duracion" class="form-label">Duración (minutos) *</label>
                            <input type="number" class="form-control-modern" id="edit_duracion" name="duracion" min="15" max="300" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_precio" class="form-label">Precio (€)</label>
                            <input type="number" class="form-control-modern" id="edit_precio" name="precio" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_servicio" class="btn-modern">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    // Service data for editing
    const servicios = <?php echo json_encode($servicios); ?>;
    
    // Initialize sortable
    const servicesList = document.getElementById('servicesList');
    if (servicesList) {
        const sortable = Sortable.create(servicesList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                // Get new order
                const orden = {};
                const items = servicesList.querySelectorAll('.service-card');
                items.forEach((item, index) => {
                    orden[item.dataset.id] = index + 1;
                });
                
                // Send to server
                updateOrder(orden);
            }
        });
    }
    
    function updateOrder(orden) {
        const formData = new FormData();
        formData.append('reordenar_servicios', '1');
        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
        
        Object.keys(orden).forEach(id => {
            formData.append(`orden[${id}]`, orden[id]);
        });
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            // Show success message
            showToast('Orden actualizado correctamente', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al actualizar el orden', 'error');
        });
    }
    
    function editService(id) {
        const servicio = servicios.find(s => s.id == id);
        if (servicio) {
            document.getElementById('edit_id').value = servicio.id;
            document.getElementById('edit_nombre').value = servicio.nombre;
            document.getElementById('edit_descripcion').value = servicio.descripcion || '';
            document.getElementById('edit_icono').value = servicio.icono || '';
            document.getElementById('edit_duracion').value = servicio.duracion;
            document.getElementById('edit_precio').value = servicio.precio || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editServiceModal'));
            modal.show();
        }
    }
    
    function deleteService(id) {
        const servicio = servicios.find(s => s.id == id);
        if (servicio && confirm(`¿Estás seguro de que quieres eliminar el servicio "${servicio.nombre}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="eliminar_servicio" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>
                ${message}
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
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
