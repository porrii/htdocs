<!-- Botón toggle (solo visible en móviles) -->
<button class="sidebar-toggle-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle sidebar">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
  <div class="sidebar-brand">
    <h3>
      <i class="fas fa-cut me-2"></i>
      Admin Panel
    </h3>
  </div>
  
  <ul class="sidebar-nav">
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'citas.php' ? 'active' : ''; ?>" href="citas.php">
        <i class="fas fa-calendar-alt"></i>
        Citas
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'servicios.php' ? 'active' : ''; ?>" href="servicios.php">
        <i class="fas fa-calendar-alt"></i>
        Servicios
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'productos.php' ? 'active' : ''; ?>" href="productos.php">
        <i class="fas fa-box"></i>
        Productos
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'videos.php' ? 'active' : ''; ?>" href="videos.php">
        <i class="fas fa-video"></i>
        Videos
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'horarios.php' ? 'active' : ''; ?>" href="horarios.php">
        <i class="fas fa-clock"></i>
        Horarios
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
        <i class="fas fa-users"></i>
        Usuarios
      </a>
    </li>
    <li class="sidebar-nav-item mt-4">
      <a class="sidebar-nav-link" href="../index.php" target="_blank">
        <i class="fas fa-external-link-alt"></i>
        Ver Sitio Web
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a class="sidebar-nav-link" href="../logout.php">
        <i class="fas fa-sign-out-alt"></i>
        Cerrar Sesión
      </a>
    </li>
  </ul>
</nav>
