<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Get all products
$productos = getAllProducts();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuestro Equipamiento - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Conoce el equipamiento profesional que utilizamos en nuestra peluquería. Tecnología de vanguardia para los mejores resultados.">
    
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

    <!-- Products Content -->
    <div class="products-wrapper">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header fade-in-up">
                <div class="page-header-content">
                    <h1 class="page-title">Nuestro Equipamiento</h1>
                    <p class="page-subtitle">
                        Utilizamos equipos profesionales de última generación para garantizar 
                        los mejores resultados en todos nuestros servicios
                    </p>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($productos)): ?>
            <div class="row g-4">
                <?php foreach ($productos as $producto): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="product-card-detailed scroll-reveal">
                        <div class="product-image-container">
                            <?php
                            if (!empty($producto['imagen'])) {
                                $thumb = getThumbnail($producto['imagen']);
                                if ($thumb) {
                                    echo '<img src="' . htmlspecialchars($thumb) . '" 
                                        alt="' . htmlspecialchars($producto['nombre']) . '" 
                                        class="product-image-detailed"
                                        loading="lazy">';
                                } else {
                                    // Miniatura no generada, mostrar icono
                                    echo '<div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                            <i class="fas fa-tools fa-3x text-muted"></i>
                                        </div>';
                                }
                            } else {
                                // No hay imagen definida, mostrar icono
                                echo '<div class="product-placeholder">
                                    <i class="fas fa-tools"></i>
                                </div>';
                            }
                            ?>
                            
                            <div class="product-overlay">
                                <div class="product-overlay-content">
                                    <i class="fas fa-tools"></i>
                                    <span>Equipamiento Profesional</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="product-content-detailed">
                            <h3 class="product-title-detailed"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p class="product-description-detailed"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            
                            <div class="product-features">
                                <div class="feature-badge">
                                    <i class="fas fa-star"></i>
                                    <span>Calidad Premium</span>
                                </div>
                                <div class="feature-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Tecnología Avanzada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-content">
                    <i class="fas fa-tools empty-state-icon"></i>
                    <h3 class="empty-state-title">Próximamente</h3>
                    <p class="empty-state-description">
                        Estamos actualizando nuestro catálogo de equipamiento. 
                        Pronto podrás conocer todas las herramientas profesionales que utilizamos.
                    </p>
                    <a href="citas.php" class="btn-modern">
                        <i class="fas fa-calendar-plus"></i>
                        Reservar Cita
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info Section -->
            <br>
            <div class="info-section">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="info-content scroll-reveal">
                            <h2 class="info-title">Tecnología de Vanguardia</h2>
                            <p class="info-description">
                                En nuestra peluquería, invertimos constantemente en equipamiento de última generación 
                                para ofrecerte los mejores resultados. Cada herramienta ha sido cuidadosamente 
                                seleccionada por nuestros profesionales para garantizar:
                            </p>
                            
                            <div class="info-features">
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Resultados profesionales y duraderos</span>
                                </div>
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Tratamientos seguros y efectivos</span>
                                </div>
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Experiencia cómoda y relajante</span>
                                </div>
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Técnicas innovadoras y actualizadas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="info-image scroll-reveal">
                            <img src="./uploads/equipamento.jpg?height=200&width=300" alt="Equipamiento profesional" class="img-fluid rounded">
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <br>
            <div class="cta-section-inline">
                <div class="cta-content-inline">
                    <h3 class="cta-title-inline">¿Quieres experimentar la diferencia?</h3>
                    <p class="cta-description-inline">
                        Reserva tu cita y descubre cómo nuestro equipamiento profesional 
                        puede transformar tu look
                    </p>
                    <a href="citas.php" class="btn-cta-white">
                        <i class="fas fa-calendar-plus"></i>
                        Reservar Cita Ahora
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once "includes/footer.php";?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar-modern');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
