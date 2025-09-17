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
    if (isset($_POST['agregar_producto'])) {
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        
        // Handle image upload
        $imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/productos/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadPath)) {
                    $imagen = 'uploads/productos/' . $fileName;
                } else {
                    $mensaje = 'Error al subir la imagen.';
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'Formato de imagen no válido. Use JPG, PNG, GIF o WebP.';
                $tipo_mensaje = 'error';
            }
        }
        
        if (empty($mensaje)) {
            if (agregarProducto($nombre, $descripcion, $precio, $imagen)) {
                $mensaje = 'Equipamiento agregado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al agregar el equipamiento.';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['editar_producto'])) {
        $id = intval($_POST['id']);
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        
        // Get current product data
        $productoActual = $db->fetchOne("SELECT imagen FROM productos WHERE id = ?", [$id]);
        $imagen = $productoActual['imagen'];
        
        // Handle image upload
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/productos/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadPath)) {
                    // Delete old image if exists
                    if ($imagen && file_exists('../' . $imagen)) {
                        unlink('../' . $imagen);
                    }
                    $imagen = 'uploads/productos/' . $fileName;
                } else {
                    $mensaje = 'Error al subir la imagen.';
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'Formato de imagen no válido. Use JPG, PNG, GIF o WebP.';
                $tipo_mensaje = 'error';
            }
        }
        
        if (empty($mensaje)) {
            if (actualizarProducto($id, $nombre, $descripcion, $precio, $imagen)) {
                $mensaje = 'Equipamiento actualizado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar el equipamiento.';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['eliminar_producto'])) {
        $id = intval($_POST['id']);
        
        // Get product data to delete image
        $producto = $db->fetchOne("SELECT imagen FROM productos WHERE id = ?", [$id]);
        
        if (eliminarProducto($id)) {
            // Delete image file if exists
            if ($producto['imagen'] && file_exists('../' . $producto['imagen'])) {
                unlink('../' . $producto['imagen']);
            }
            $mensaje = 'Equipamiento eliminado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar el equipamiento.';
            $tipo_mensaje = 'error';
        }
    }
}

// Get all products
$productos = $db->fetchAll("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipamiento - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">
    
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: var(--radius-md);
            border: 2px solid var(--border-color);
        }
        
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .upload-area.dragover {
            border-color: var(--primary-color);
            background-color: rgba(102, 126, 234, 0.1);
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
                        <i class="fas fa-tools"></i>
                        Gestión de Equipamiento
                    </h1>
                    <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i>
                        Agregar Equipamiento
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
            
            <!-- Products Grid -->
            <div class="row g-4">
                <?php foreach ($productos as $producto): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="data-card fade-in-up">
                        <div class="position-relative">
                            <?php if ($producto['imagen']): ?>
                                <img src="../<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                     class="w-100" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-tools fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="position-absolute top-0 end-0 p-2">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="editProduct(<?php echo $producto['id']; ?>)">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteProduct(<?php echo $producto['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="data-body">
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary">Profesional</span>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($productos)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-tools"></i>
                        <h3>No hay equipamiento registrado</h3>
                        <p>Comienza agregando el primer elemento de equipamiento.</p>
                        <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus"></i>
                            Agregar Equipamiento
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Equipamiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del Equipamiento *</label>
                            <input type="text" class="form-control-modern" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="precio" class="form-label">Valor Aproximado (€)</label>
                            <input type="number" class="form-control-modern" id="precio" name="precio" step="0.01" min="0">
                            <small class="text-muted">Solo para referencia interna</small>
                        </div>
                        
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción *</label>
                            <textarea class="form-control-modern" id="descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Imagen del Equipamiento</label>
                            <div class="upload-area" onclick="document.getElementById('imagen').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-0">Haz clic aquí o arrastra una imagen</p>
                                <small class="text-muted">JPG, PNG, GIF o WebP - Máximo 5MB</small>
                            </div>
                            <input type="file" id="imagen" name="imagen" accept="image/*" style="display: none;" onchange="previewImage(this, 'imagePreview')">
                            <div id="imagePreview" class="mt-3" style="display: none;">
                                <img id="previewImg" class="image-preview" src="/placeholder.svg" alt="Vista previa">
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar_producto" class="btn-modern">
                        <i class="fas fa-plus"></i>
                        Agregar Equipamiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Equipamiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editProductForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_nombre" class="form-label">Nombre del Equipamiento *</label>
                            <input type="text" class="form-control-modern" id="edit_nombre" name="nombre" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_precio" class="form-label">Valor Aproximado (€)</label>
                            <input type="number" class="form-control-modern" id="edit_precio" name="precio" step="0.01" min="0">
                        </div>
                        
                        <div class="col-12">
                            <label for="edit_descripcion" class="form-label">Descripción *</label>
                            <textarea class="form-control-modern" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Imagen del Equipamiento</label>
                            <div class="upload-area" onclick="document.getElementById('edit_imagen').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-0">Haz clic aquí o arrastra una imagen</p>
                                <small class="text-muted">JPG, PNG, GIF o WebP - Máximo 5MB</small>
                            </div>
                            <input type="file" id="edit_imagen" name="imagen" accept="image/*" style="display: none;" onchange="previewImage(this, 'editImagePreview')">
                            <div id="editImagePreview" class="mt-3">
                                <img id="editPreviewImg" class="image-preview" src="/placeholder.svg" alt="Vista previa">
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_producto" class="btn-modern">
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

<script>
    // Product data for editing
    const productos = <?php echo json_encode($productos); ?>;
    
    function editProduct(id) {
        const producto = productos.find(p => p.id == id);
        if (producto) {
            document.getElementById('edit_id').value = producto.id;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_descripcion').value = producto.descripcion;
            document.getElementById('edit_precio').value = producto.precio;
            
            // Show current image if exists
            const previewDiv = document.getElementById('editImagePreview');
            const previewImg = document.getElementById('editPreviewImg');
            
            if (producto.imagen) {
                previewImg.src = '../' + producto.imagen;
                previewDiv.style.display = 'block';
            } else {
                previewDiv.style.display = 'none';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }
    }
    
    function deleteProduct(id) {
        const producto = productos.find(p => p.id == id);
        if (producto && confirm(`¿Estás seguro de que quieres eliminar "${producto.nombre}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="eliminar_producto" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function previewImage(input, previewId) {
        const previewDiv = document.getElementById(previewId);
        const previewImg = previewDiv.querySelector('img');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewDiv.style.display = 'block';
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Drag and drop functionality
    document.querySelectorAll('.upload-area').forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = this.parentElement.querySelector('input[type="file"]');
                input.files = files;
                
                // Trigger preview
                const previewId = input.getAttribute('onchange').match(/'([^']+)'/)[1];
                previewImage(input, previewId);
            }
        });
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
