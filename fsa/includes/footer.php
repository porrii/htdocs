<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$servicios_footer = getServiciosActivos();
?>
<footer class="footer-modern bg-dark text-light py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Marca y descripción -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand fs-3 fw-bold mb-2"><?php echo SITE_NAME; ?></div>
                <p class="footer-description mb-3">
                    Tu peluquería de confianza con más de 10 años de experiencia.<br>
                    Especialistas en cortes modernos, coloración y tratamientos capilares.
                </p>
                <div class="social-links d-flex gap-3">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/fsa.barber_/" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Servicios -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Servicios</h5>
                <ul class="footer-list">
                    <?php foreach ($servicios_footer as $servicio): ?>
                        <li><?php echo htmlspecialchars($servicio['nombre']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Enlaces -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Enlaces</h5>
                <ul class="footer-list">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="index.php#servicios">Servicios</a></li>
                    <li><a href="productos.php">Productos</a></li>
                    <li><a href="videos.php">Videos</a></li>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-title">Contacto</h5>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt me-2"></i>Calle el casino , numero 3 , bajo 1F, 39620, Santa María de Cayón</li>
                    <li><i class="fas fa-phone me-2"></i>+34 123 456 789</li>
                    <li><i class="fas fa-envelope me-2"></i>info@peluqueria.com</li>
                </ul>
            </div>
        </div>

        <!-- Línea inferior -->
        <div class="footer-bottom text-center mt-4 pt-3 border-top border-secondary">
            <p class="mb-0">&copy; 2025 <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>
