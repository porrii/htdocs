<?php
// Este archivo se incluye en todas las páginas que requieren autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
        <i class="fas fa-leaf me-2"></i>SmartGarden
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap d-flex align-items-center">
            <span class="text-light me-3"><?php echo $_SESSION['user_name']; ?></span>
            <a class="nav-link" href="logout.php">Cerrar sesión</a>
        </li>
    </ul>
</nav>