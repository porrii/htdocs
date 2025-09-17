<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();

// Get statistics
$estadisticas = getEstadisticas();

// Get recent appointments
$citasRecientes = $db->fetchAll(
    "SELECT * FROM citas ORDER BY fecha_creacion DESC LIMIT 5"
);

// Get today's appointments
$hoy = date('Y-m-d');
$citasHoy = getCitasPorFecha($hoy);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid p-4">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h1 class="dashboard-title">
                        <i class="fas fa-tachometer-alt"></i>
                        Panel de Control
                    </h1>
                    <p class="text-muted mb-0">Bienvenido al panel de administración de <?php echo SITE_NAME; ?></p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card primary">
                            <div class="stat-icon primary">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <h3 class="stat-number"><?php echo $estadisticas['citas_hoy']; ?></h3>
                                <p class="stat-label">Citas Hoy</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card success">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h3 class="stat-number"><?php echo $estadisticas['citas_confirmadas']; ?></h3>
                                <p class="stat-label">Confirmadas</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card success">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h3 class="stat-number"><?php echo $estadisticas['citas_completadas']; ?></h3>
                                <p class="stat-label">Completadas</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card warning">
                            <div class="stat-icon warning">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div>
                                <h3 class="stat-number"><?php echo $estadisticas['productos']; ?></h3>
                                <p class="stat-label">Equipamiento</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="data-card">
                            <div class="data-header">
                                <h3 class="data-title">
                                    <i class="fas fa-bolt"></i>
                                    Acciones Rápidas
                                </h3>
                            </div>
                            <div class="data-body">
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="citas.php" class="quick-action-btn">
                                        <i class="fas fa-calendar-plus"></i>
                                        Nueva Cita
                                    </a>
                                    <a href="productos.php" class="quick-action-btn">
                                        <i class="fas fa-plus"></i>
                                        Añadir Equipamiento
                                    </a>
                                    <a href="videos.php" class="quick-action-btn">
                                        <i class="fas fa-video"></i>
                                        Gestionar Videos
                                    </a>
                                    <a href="horarios.php" class="quick-action-btn">
                                        <i class="fas fa-clock"></i>
                                        Configurar Horarios
                                    </a>
                                    <a href="../index.php" class="quick-action-btn" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                        Ver Sitio Web
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Content -->
                <div class="row g-4">
                    <!-- Today's Appointments -->
                    <div class="col-lg-8">
                        <div class="data-card">
                            <div class="data-header">
                                <h3 class="data-title">
                                    <i class="fas fa-calendar-day"></i>
                                    Citas de Hoy
                                </h3>
                                <span class="badge bg-primary"><?php echo count($citasHoy); ?> citas</span>
                            </div>
                            <div class="data-body">
                                <?php if (!empty($citasHoy)): ?>
                                    <?php foreach ($citasHoy as $cita): ?>
                                    <div class="appointment-item <?php echo $cita['estado']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="appointment-client"><?php echo htmlspecialchars($cita['nombre']); ?></div>
                                                <div class="appointment-service"><?php echo htmlspecialchars($cita['servicio']); ?></div>
                                                <div class="appointment-time">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo substr($cita['hora'], 0, 5); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="status-badge <?php echo $cita['estado']; ?>">
                                                    <?php echo ucfirst($cita['estado']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No hay citas programadas para hoy</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="col-lg-4">
                        <div class="data-card">
                            <div class="data-header">
                                <h3 class="data-title">
                                    <i class="fas fa-history"></i>
                                    Actividad Reciente
                                </h3>
                            </div>
                            <div class="data-body">
                                <?php if (!empty($citasRecientes)): ?>
                                    <?php foreach ($citasRecientes as $cita): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($cita['nombre'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($cita['nombre']); ?></div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($cita['fecha_creacion'])); ?>
                                            </small>
                                        </div>
                                        <span class="status-badge <?php echo $cita['estado']; ?>">
                                            <?php echo ucfirst($cita['estado']); ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No hay actividad reciente</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services Performance -->
                <div class="row g-4 mt-4">
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 class="chart-title">
                                    <i class="fas fa-chart-bar"></i>
                                    Servicios Más Solicitados
                                </h3>
                            </div>
                            <div class="chart-body">
                                <?php
                                // Get service statistics
                                $servicios = $db->fetchAll(
                                    "SELECT servicio, COUNT(*) as total FROM citas WHERE estado != 'cancelada' GROUP BY servicio ORDER BY total DESC LIMIT 5"
                                );
                                $totalCitas = array_sum(array_column($servicios, 'total'));
                                ?>
                                
                                <?php foreach ($servicios as $servicio): ?>
                                    <?php $porcentaje = $totalCitas > 0 ? ($servicio['total'] / $totalCitas) * 100 : 0; ?>
                                    <div class="service-item">
                                        <div class="service-header">
                                            <span class="service-name"><?php echo htmlspecialchars($servicio['servicio']); ?></span>
                                            <span class="service-count"><?php echo $servicio['total']; ?></span>
                                        </div>
                                        <div class="service-progress">
                                            <div class="service-progress-bar" style="width: <?php echo $porcentaje; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 class="chart-title">
                                    <i class="fas fa-chart-pie"></i>
                                    Estado de Citas
                                </h3>
                            </div>
                            <div class="chart-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="stat-item">
                                            <div class="stat-number text-warning"><?php echo $estadisticas['citas_confirmadas']; ?></div>
                                            <div class="stat-label">Confirmadas</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-item">
                                            <div class="stat-number text-danger"><?php echo $estadisticas['citas_canceladas']; ?></div>
                                            <div class="stat-label">Canceladas</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-item">
                                            <div class="stat-number text-success"><?php echo $estadisticas['citas_completadas']; ?></div>
                                            <div class="stat-label">Completadas</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
        
        // Add some interactivity to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
