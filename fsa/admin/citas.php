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
    if (isset($_POST['actualizar_estado'])) {
        $id = intval($_POST['id']);
        $estado = sanitizeInput($_POST['estado']);
        
        if (actualizarEstadoCita($id, $estado)) {
            $mensaje = 'Estado de la cita actualizado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar el estado de la cita.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['eliminar_cita'])) {
        $id = intval($_POST['id']);
        
        if ($db->delete("DELETE FROM citas WHERE id = ?", [$id])) {
            $mensaje = 'Cita eliminada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar la cita.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['crear_cita'])) {
        $nombre = sanitizeInput($_POST['nombre']);
        $email = sanitizeInput($_POST['email']);
        $telefono = sanitizeInput($_POST['telefono']);
        $fecha = sanitizeInput($_POST['fecha']);
        $hora = sanitizeInput($_POST['hora']);
        $servicio = sanitizeInput($_POST['servicio']);
        $notas = sanitizeInput($_POST['notas']);
        
        // Create appointment with 'confirmada' status instead of 'pendiente'
        $id = $db->insert(
            "INSERT INTO citas (nombre, email, telefono, fecha, hora, servicio, estado, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, 'confirmada', NOW())",
            [$nombre, $email, $telefono, $fecha, $hora, $servicio]
        );
        
        if ($id) {
            if (!empty($notas)) {
                $db->update("UPDATE citas SET notas = ? WHERE id = ?", [$notas, $id]);
            }
            // Send confirmation email
            enviarEmailConfirmacion($nombre, $email, $fecha, $hora, $servicio, $id);
            $mensaje = 'Cita creada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al crear la cita.';
            $tipo_mensaje = 'error';
        }
    }
}

// Get filters
$filtro_estado = isset($_GET['estado']) ? sanitizeInput($_GET['estado']) : '';
$filtro_fecha = isset($_GET['fecha']) ? sanitizeInput($_GET['fecha']) : '';
$buscar = isset($_GET['buscar']) ? sanitizeInput($_GET['buscar']) : '';

// Get appointments with filters
$filtros = [];
if (!empty($filtro_estado)) $filtros['estado'] = $filtro_estado;
if (!empty($filtro_fecha)) $filtros['fecha'] = $filtro_fecha;
if (!empty($buscar)) $filtros['buscar'] = $buscar;

$citas = getAllCitas($filtros);

// Get services for dropdown
$servicios = [
    'Corte de Cabello',
    'Corte y Peinado',
    'Coloración',
    'Mechas',
    'Tratamiento Capilar',
    'Peinado Especial',
    'Asesoría de Imagen'
];

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">

    <style>
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
                        <i class="fas fa-calendar-alt"></i>
                        Gestión de Citas
                    </h1>
                    <button type="button" class="btn-modern" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                        <i class="fas fa-plus"></i>
                        Nueva Cita
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
            
            <!-- Filters - CORREGIDO -->
            <div class="data-card fade-in-up">
                <div class="data-header">
                    <h3 class="data-title">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </h3>
                </div>
                <div class="data-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select id="estado" name="estado" class="form-control-modern">
                                <option value="">Todos los estados</option>
                                <option value="confirmada" <?php echo $filtro_estado === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="completada" <?php echo $filtro_estado === 'completada' ? 'selected' : ''; ?>>Completada</option>
                                <option value="cancelada" <?php echo $filtro_estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" id="fecha" name="fecha" class="form-control-modern" value="<?php echo $filtro_fecha; ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="buscar" class="form-label">Buscar</label>
                            <input type="text" id="buscar" name="buscar" class="form-control-modern" placeholder="Nombre, email o teléfono..." value="<?php echo $buscar; ?>">
                        </div>

                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn-modern w-100">
                                <i class="fas fa-search"></i>
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <br>
            
            <!-- Appointments Table -->
            <div class="data-card fade-in-up">
                <div class="data-header">
                    <h3 class="data-title">
                        <i class="fas fa-list"></i>
                        Citas (<?php echo count($citas); ?>)
                    </h3>
                </div>
                <div class="data-body">
                    <?php if (!empty($citas)): ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Contacto</th>
                                    <th>Fecha & Hora</th>
                                    <th>Servicio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas as $cita): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($cita['nombre']); ?></div>
                                        <small class="text-muted">ID: <?php echo $cita['id']; ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($cita['email']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($cita['telefono']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></div>
                                        <small class="text-muted"><?php echo substr($cita['hora'], 0, 5); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($cita['servicio']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $cita['estado']; ?>">
                                            <?php echo ucfirst($cita['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editAppointment(<?php echo $cita['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteAppointment(<?php echo $cita['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No hay citas</h3>
                        <p>No se encontraron citas con los filtros aplicados.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control-modern" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control-modern" id="email" name="email" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control-modern" id="telefono" name="telefono" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="servicio" class="form-label">Servicio *</label>
                            <select class="form-control-modern" id="servicio" name="servicio" required>
                                <option value="">Seleccionar servicio</option>
                                <?php foreach ($servicios as $servicio): ?>
                                <option value="<?php echo htmlspecialchars($servicio); ?>">
                                    <?php echo htmlspecialchars($servicio); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fecha" class="form-label">Fecha *</label>
                            <input type="date" class="form-control-modern" id="fecha" name="fecha" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="hora" class="form-label">Hora *</label>
                            <input type="time" class="form-control-modern" id="hora" name="hora" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control-modern" id="notas" name="notas" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_cita" class="btn-modern">
                        <i class="fas fa-plus"></i>
                        Crear Cita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Status Modal -->
<div class="modal fade" id="editStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editStatusForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_estado" class="form-label">Nuevo Estado</label>
                        <select class="form-control-modern" id="edit_estado" name="estado" required>
                            <option value="confirmada">Confirmada</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                    
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="actualizar_estado" class="btn-modern">
                        <i class="fas fa-save"></i>
                        Actualizar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Appointment data for editing
    const citas = <?php echo json_encode($citas); ?>;
    
    function editAppointment(id) {
        const cita = citas.find(c => c.id == id);
        if (cita) {
            document.getElementById('edit_id').value = cita.id;
            document.getElementById('edit_estado').value = cita.estado;
            
            const modal = new bootstrap.Modal(document.getElementById('editStatusModal'));
            modal.show();
        }
    }
    
    function deleteAppointment(id) {
        const cita = citas.find(c => c.id == id);
        if (cita && confirm(`¿Estás seguro de que quieres eliminar la cita de ${cita.nombre}?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="eliminar_cita" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fecha').setAttribute('min', today);
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
