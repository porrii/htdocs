<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar autenticación
requireAuth();

// Usuario:
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];
$username = $_SESSION['username'] ?? 'Usuario';
$userImage = $_SESSION['user_image'] ?? null; // null si no hay imagen
$initials = implode('', array_map(function($part){ return strtoupper($part[0]); }, explode(' ', $username)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'SmartGarden - Sistema de Riego Inteligente'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Avatar iniciales */
        .avatar-initials {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #6c757d;
            color: #fff;
            font-weight: bold;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            text-transform: uppercase;
        }
        .theme-transition {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-leaf me-2"></i>
                <strong>SmartGarden</strong>
            </a>

            <!-- Botón hamburguesa móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú escritorio -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="devices.php"><i class="fas fa-microchip me-1"></i> Dispositivos</a></li>
                    <li class="nav-item"><a class="nav-link" href="irrigation.php"><i class="fas fa-tint me-1"></i> Riego</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i> Historial</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reportes</a></li>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role']==='admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin.php"><i class="fas fa-cog me-1"></i> Administración</a></li>
                    <?php endif; ?>
                </ul>

                <!-- Usuario y notificaciones escritorio -->
                <ul class="navbar-nav ms-auto d-none d-lg-flex align-items-center">
                    <!-- Notificaciones -->
                    <li class="nav-item me-3 position-relative">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="fas fa-bell fa-lg theme-transition"></i>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle" id="notificationCount">0</span>
                        </a>
                    </li>

                    <!-- Dropdown usuario -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" 
                        href="javascript:void(0)" 
                        id="userDropdown" 
                        role="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false" 
                        data-bs-auto-close="outside">
                            <?php if($userImage): ?>
                                <img src="<?php echo $userImage; ?>" class="rounded-circle me-2" width="32" height="32">
                            <?php else: ?>
                                <div class="avatar-initials me-2"><?php echo $initials; ?></div>
                            <?php endif; ?>
                            <span><?php echo $username; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Configuración</a></li>
                            <li><a class="dropdown-item" href="#" id="themeToggle"><i class="fas fa-moon me-2"></i> Modo Oscuro</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="includes/auth.php" class="m-0">
                                    <input type="hidden" name="action" value="logout">
                                    <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas móvil -->
    <div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="menuOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="fas fa-microchip me-1"></i> Dispositivos</a></li>
                <li class="nav-item"><a class="nav-link" href="irrigation.php"><i class="fas fa-tint me-1"></i> Riego</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i> Historial</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reportes</a></li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role']==='admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin.php"><i class="fas fa-cog me-1"></i> Administración</a></li>
                <?php endif; ?>
                <hr class="bg-light">
                <li class="nav-item"><a class="nav-link" href="settings.php"><i class="fas fa-cog me-1"></i> Configuración</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="themeToggleMobile"><i class="fas fa-moon me-1"></i> Modo Oscuro</a></li>
                <li class="nav-item">
                    <form method="post" action="includes/auth.php">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="nav-link btn btn-link text-start">
                            <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <!-- Modal Notificaciones -->
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notificaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                <div class="modal-footer flex-wrap">
                    <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary w-100 w-sm-auto" id="markAllRead">Marcar todas como leídas</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const themeButtons = [document.getElementById('themeToggle'), document.getElementById('themeToggleMobile')].filter(Boolean);
            const applyTheme = (theme)=>{
                if(theme==='dark'){
                    document.documentElement.setAttribute('data-theme','dark');
                    themeButtons.forEach(btn=>btn.innerHTML='<i class="fas fa-sun me-2"></i> Modo Claro');
                } else{
                    document.documentElement.removeAttribute('data-theme');
                    themeButtons.forEach(btn=>btn.innerHTML='<i class="fas fa-moon me-2"></i> Modo Oscuro');
                }
            };
            const phpDarkMode = <?php echo isset($_SESSION['dark_mode']) ? (int)$_SESSION['dark_mode'] : 0; ?>;
            let currentTheme = sessionStorage.getItem('theme') || (phpDarkMode===1?'dark':'light');
            sessionStorage.setItem('theme', currentTheme);
            applyTheme(currentTheme);
            themeButtons.forEach(btn=>{
                btn.addEventListener('click', e=>{
                    e.preventDefault();
                    const theme = document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
                    sessionStorage.setItem('theme', theme);
                    applyTheme(theme);
                });
            });

            // Notificaciones (igual que antes)
            const loadNotifications = ()=>{
                fetch('api/get_notifications.php')
                .then(r=>r.json())
                .then(data=>{
                    const notificationsList = document.getElementById('notificationsList');
                    if(!data.success){ notificationsList.innerHTML='<p class="text-center text-danger py-3">Error al cargar notificaciones</p>'; return; }
                    const notifications = data.notifications||[];
                    if(notifications.length===0){
                        notificationsList.innerHTML='<p class="text-center text-muted py-3">No hay notificaciones</p>';
                        ['notificationCount','notificationCountMobile'].forEach(id=>{const el=document.getElementById(id); if(el) el.textContent='0';});
                        return;
                    }
                    let html='';
                    notifications.forEach(n=>{
                        html+=`<div class="alert ${n.resolved==1?'alert-light':'alert-primary'} mb-2">
                            <div class="d-flex justify-content-between"><h6 class="alert-heading mb-1">${n.title}</h6><small class="text-muted">${n.time_ago}</small></div>
                            <p class="mb-1">${n.message}</p>
                            ${n.resolved==0?`<button class="btn btn-sm btn-outline-primary mark-as-read" data-id="${n.id}">Marcar como leída</button>`:''}
                        </div>`;
                    });
                    notificationsList.innerHTML=html;
                    ['notificationCount','notificationCountMobile'].forEach(id=>{const el=document.getElementById(id); if(el) el.textContent=data.unread_count;});
                    document.querySelectorAll('.mark-as-read').forEach(btn=>btn.addEventListener('click',()=>markAsRead(btn.getAttribute('data-id'))));
                }).catch(err=>{console.error(err); notificationsList.innerHTML='<p class="text-center text-danger py-3">Error al cargar notificaciones</p>';});
            };
            const markAsRead=(id)=>{fetch('api/mark_notification_read.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}).then(r=>r.json()).then(d=>{if(d.success) loadNotifications();});};
            loadNotifications();
            document.getElementById('markAllRead')?.addEventListener('click',()=>{fetch('api/mark_notifications_read.php',{method:'POST',headers:{'Content-Type':'application/json'}}).then(r=>r.json()).then(d=>{if(d.success){loadNotifications(); ['notificationCount','notificationCountMobile'].forEach(id=>{const el=document.getElementById(id); if(el) el.textContent='0';});}});});
        });
    </script>
</body>
</html>
