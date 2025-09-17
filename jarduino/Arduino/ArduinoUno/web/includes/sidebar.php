<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="devices.php">
                    <i class="fas fa-microchip me-2"></i>
                    Mis Dispositivos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="irrigation.php">
                    <i class="fas fa-tint me-2"></i>
                    Control de Riego
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="history.php">
                    <i class="fas fa-history me-2"></i>
                    Historial
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Configuración
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Dispositivos</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <?php
            $devices = getUserDevices($_SESSION['user_id']);
            foreach ($devices as $device): 
            ?>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?device=<?php echo $device['device_id']; ?>">
                        <i class="fas fa-circle-notch me-2"></i>
                        <?php echo htmlspecialchars($device['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>