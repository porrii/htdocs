<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Get featured content
$servicios_destacados = getFeaturedServices(6);
$productos_destacados = getFeaturedProducts(3);
$videos_destacados = getFeaturedVideos(2);
$horarios = getHorarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Peluquería Profesional</title>
    <meta name="description" content="Peluquería profesional con los mejores servicios de corte, peinado y tratamientos capilares. Reserva tu cita online.">
    
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

    <!-- ENVOLTORIO PRINCIPAL -->
    <div class="main-wrapper">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-6">
                        <div class="hero-content fade-in-up">
                            <h1 class="hero-title">
                                Tu Belleza, Nuestra Pasión
                            </h1>
                            <p class="hero-subtitle">
                                Descubre la experiencia única de nuestros servicios profesionales de peluquería. 
                                Transformamos tu look con las últimas tendencias y técnicas.
                            </p>
                            <div class="hero-buttons">
                                <a href="citas.php" class="btn-modern fade-in-up-delay-1">
                                    <i class="fas fa-calendar-check"></i>
                                    Reservar Cita
                                </a>
                                <a href="#servicios" class="btn-outline-modern fade-in-up-delay-2">
                                    <i class="fas fa-arrow-down"></i>
                                    Ver Servicios
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-center fade-in-up fade-in-up-delay-3">
                            <div class="hero-image-wrapper">
                                <img src="./uploads/fsa_studio.jfif" 
                                    alt="Peluquería profesional" 
                                    class="img-fluid hero-image">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <?php if (!empty($servicios_destacados)): ?>
        <section id="servicios" class="section-modern">
            <div class="container">
                <div class="section-header"> <!-- scroll-reveal"> -->
                    <h2 class="section-title">Nuestros Servicios</h2>
                    <p class="section-subtitle">
                        Ofrecemos una amplia gama de servicios profesionales para realzar tu belleza natural
                    </p>
                </div>

                <div class="row g-4">
                    <?php foreach ($servicios_destacados as $servicio): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card"> <!-- scroll-reveal"> -->
                            <div class="feature-icon">
                                <i class="fas <?php echo $servicio['icono'] ?? 'fa-star'; ?>"></i>
                            </div>
                            <h3 class="feature-title"><?php echo htmlspecialchars($servicio['nombre']); ?></h3>
                            <p class="feature-description"><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- <div class="text-center mt-5">
                    <a href="servicios.php" class="btn-cta-white">
                        <i class="fas fa-arrow-right"></i>
                        Ver Todos los Servicios
                    </a>
                </div> -->
            </div>
        </section>
        <?php endif; ?>

        <!-- Products Section -->
        <?php if (!empty($productos_destacados)): ?>
        <section class="section-modern bg-light">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Nuestro Equipamiento</h2>
                    <p class="section-subtitle">
                        Utilizamos equipos profesionales de última generación para garantizar los mejores resultados
                    </p>
                </div>

                <div class="row g-4">
                    <?php foreach ($productos_destacados as $producto): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="product-card">
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
                                echo '<div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                        <i class="fas fa-tools fa-3x text-muted"></i>
                                    </div>';
                            }
                            ?>
                            <div class="product-content">
                                <h3 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <div class="text-center mt-5">
                    <a href="productos.php" class="btn-cta-white">
                        <i class="fas fa-arrow-right"></i>
                        Ver Todo el Equipamiento
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Videos Section -->
        <?php if (!empty($videos_destacados)): ?>
        <section class="section-modern">
            <div class="container">
                <div class="section-header"> <!-- scroll-reveal"> -->
                    <h2 class="section-title">Nuestro Trabajo</h2>
                    <p class="section-subtitle">
                        Descubre nuestras técnicas y transformaciones en acción
                    </p>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($videos_destacados as $video): ?>
                    <div class="col-lg-6">
                        <div class="video-card"> <!-- scroll-reveal"> -->
                            <div class="video-thumbnail-gallery">
                                <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/maxresdefault.jpg" 
                                    alt="<?php echo htmlspecialchars($video['titulo']); ?>" 
                                    class="video-thumbnail-img">
                                <div class="video-play-overlay" onclick="openVideo('<?php echo htmlspecialchars($video['youtube_id']); ?>')">
                                    <div class="video-play-btn-gallery">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="video-content">
                                <h3 class="video-title"><?php echo htmlspecialchars($video['titulo']); ?></h3>
                                <p class="video-description"><?php echo htmlspecialchars($video['descripcion']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-5">
                    <a href="videos.php" class="btn-cta-white">
                        <i class="fas fa-play-circle"></i>
                        Ver Todos los Videos
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Hours Section -->
        <section class="section-modern bg-light">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="hours-card"> <!-- scroll-reveal"> -->
                            <h2 class="hours-title">
                                <i class="fas fa-clock me-3"></i>
                                Horarios de Atención
                            </h2>
                            
                            <?php foreach ($horarios as $dia => $horario): ?>
                            <div class="hours-item">
                                <span class="hours-day"><?php echo $dia; ?></span>
                                <span class="hours-time">
                                    <?php if ($horario['apertura'] === 'Cerrado'): ?>
                                        <span class="text-muted">Cerrado</span>
                                    <?php else: ?>
                                        <?php echo $horario['apertura']; ?>
                                        <?php if ($horario['cierre'] !== $horario['apertura']): ?>
                                            - <?php echo $horario['cierre']; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">¿Listo para tu Transformación?</h2>
                    <p class="cta-subtitle">
                        Reserva tu cita ahora y descubre por qué somos la peluquería de confianza de miles de clientes
                    </p>
                    <a href="citas.php" class="btn-cta-white">
                        <i class="fas fa-calendar-plus"></i>
                        Reservar Cita Ahora
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include_once "includes/footer.php";?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Scroll animations
        // const observerOptions = {
        //     threshold: 0.1,
        //     rootMargin: '0px 0px -50px 0px'
        // };

        // const observer = new IntersectionObserver((entries) => {
        //     entries.forEach(entry => {
        //         if (entry.isIntersecting) {
        //             entry.target.classList.add('revealed');
        //         }
        //     });
        // }, observerOptions);

        // document.querySelectorAll('.scroll-reveal').forEach(el => {
        //     observer.observe(el);
        // });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar-modern');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Video modal
        function openVideo(videoId) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
