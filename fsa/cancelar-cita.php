<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Generate CSRF token
session_start();
$csrf_token = generateCSRFToken();

$mensaje = '';
$tipo_mensaje = '';
$cita = null;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['buscar_cita'])) {
        $email = sanitizeInput($_POST['email']);
        $cita_id = intval($_POST['cita_id']);
        
        if (!empty($email) && !empty($cita_id)) {
            $cita = $db->fetchOne("SELECT * FROM citas WHERE id = ? AND email = ? AND estado != 'cancelada'", [$cita_id, $email]);
            
            if (!$cita) {
                $mensaje = 'No se encontró ninguna cita con esos datos o ya está cancelada.';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Por favor, introduce todos los datos requeridos.';
            $tipo_mensaje = 'error';
        }
    }
    
    if (isset($_POST['cancelar_cita'])) {
        $cita_id = intval($_POST['cita_id']);
        $email = sanitizeInput($_POST['email']);
        
        if (cancelarCita($cita_id, $email)) {
            $mensaje = 'Tu cita ha sido cancelada correctamente. Te hemos enviado un email de confirmación.';
            $tipo_mensaje = 'success';
            $cita = null;
        } else {
            $mensaje = 'Error al cancelar la cita. Por favor, inténtalo de nuevo.';
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Cita - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Cancela tu cita de forma rápida y sencilla usando tu email y número de cita.">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">

</head>
<body>
    <!-- Navigation -->
    <?php include_once "includes/navbar.php";?>

    <!-- Main Content -->
    <div class="cancel-wrapper">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header fade-in-up">
                <div class="page-header-content">
                    <h1 class="page-title">Cancelar Cita</h1>
                    <p class="page-subtitle">
                        Introduce tu email y número de cita para cancelar tu reserva.
                    </p>
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

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if (!$cita): ?>
                    <!-- Search Form -->
                    <div class="form-container fade-in-up">
                        <form method="POST">
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <div class="form-section-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    Buscar tu Cita
                                </h3>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" id="email" name="email" class="form-control-modern" 
                                                   placeholder="tu@email.com" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cita_id" class="form-label">Número de Cita *</label>
                                            <input type="number" id="cita_id" name="cita_id" class="form-control-modern" 
                                                   placeholder="123" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" name="buscar_cita" class="btn-modern">
                                        <i class="fas fa-search"></i>
                                        Buscar Cita
                                    </button>
                                </div>
                            </div>
                            
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Appointment Details -->
                    <div class="form-container fade-in-up">
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <div class="form-section-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                Detalles de tu Cita
                            </h3>
                            
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <strong>Número de Cita:</strong><br>
                                            <span class="text-muted"><?php echo $cita['id']; ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Estado:</strong><br>
                                            <span class="status-badge <?php echo $cita['estado']; ?>">
                                                <?php echo ucfirst($cita['estado']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Nombre:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($cita['nombre']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Servicio:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($cita['servicio']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Fecha:</strong><br>
                                            <span class="text-muted"><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Hora:</strong><br>
                                            <span class="text-muted"><?php echo substr($cita['hora'], 0, 5); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($cita['email']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <button type="submit" name="cancelar_cita" class="btn btn-danger me-3" 
                                            onclick="return confirm('¿Estás seguro de que quieres cancelar esta cita?')">
                                        <i class="fas fa-times"></i>
                                        Cancelar Cita
                                    </button>
                                </form>
                                
                                <a href="cancelar-cita.php" class="btn-outline-modern">
                                    <i class="fas fa-arrow-left"></i>
                                    Buscar Otra Cita
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once "includes/footer.php";?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
