<?php
// Verificar si la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartGarden - Sistema de Riego Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf me-2"></i>
                <strong>SmartGarden</strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="devices.php"><i class="fas fa-microchip me-1"></i> Dispositivos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="irrigation.php"><i class="fas fa-tint me-1"></i> Riego</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i> Historial</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reportes</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php"><i class="fas fa-cog me-1"></i> Administración</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['username'] ?? 'Usuario'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="themeToggle"><i class="fas fa-moon me-2"></i> Modo Oscuro</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <form method="post" action="includes/auth.php" style="display:inline;">
                                <input type="hidden" name="action" value="logout">
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                </button>
                            </form>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger notification-badge" id="notificationCount">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Modal Notificaciones -->
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notificaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="notificationsList">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="markAllRead">Marcar todas como leídas</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle modo oscuro
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        // Aplicar tema guardado
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeToggle.innerHTML = '<i class="fas fa-sun me-2"></i> Modo Claro';
        }
        
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const currentTheme = document.documentElement.getAttribute('data-theme');
            
            if (currentTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeToggle.innerHTML = '<i class="fas fa-moon me-2"></i> Modo Oscuro';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeToggle.innerHTML = '<i class="fas fa-sun me-2"></i> Modo Claro';
            }
        });
        
        // Cargar notificaciones
        loadNotifications();
        
        // Marcar todas como leídas
        document.getElementById('markAllRead').addEventListener('click', function() {
            fetch('api/mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    document.getElementById('notificationCount').textContent = '0';
                }
            });
        });
    });
    
    function loadNotifications() {
        fetch('api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notificationsList = document.getElementById('notificationsList');

                if (!data.success) {
                    notificationsList.innerHTML = '<p class="text-center text-danger py-3">Error al cargar notificaciones</p>';
                    return;
                }

                const notifications = data.notifications;

                if (notifications.length === 0) {
                    notificationsList.innerHTML = '<p class="text-center text-muted py-3">No hay notificaciones</p>';
                    document.getElementById('notificationCount').textContent = '0';
                } else {
                    let html = '';

                    notifications.forEach(notification => {
                        html += `
                            <div class="alert ${notification.resolved == 1 ? 'alert-light' : 'alert-primary'} mb-2">
                                <div class="d-flex justify-content-between">
                                    <h6 class="alert-heading">${notification.title}</h6>
                                    <small class="text-muted">${notification.time_ago}</small>
                                </div>
                                <p class="mb-1">${notification.message}</p>
                                ${notification.resolved == 0 ? `
                                <button class="btn btn-sm btn-outline-primary mark-as-read" data-id="${notification.id}">
                                    Marcar como leída
                                </button>
                                ` : ''}
                            </div>
                        `;
                    });

                    notificationsList.innerHTML = html;
                    document.getElementById('notificationCount').textContent = data.unread_count;

                    // Agregar event listeners
                    document.querySelectorAll('.mark-as-read').forEach(button => {
                        button.addEventListener('click', function() {
                            const notificationId = this.getAttribute('data-id');
                            markAsRead(notificationId);
                        });
                    });
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('notificationsList').innerHTML =
                    '<p class="text-center text-danger py-3">Error al cargar notificaciones</p>';
            });
    }
    
    function markAsRead(notificationId) {
        fetch('api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        });
    }
    </script>