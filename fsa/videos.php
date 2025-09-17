<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Get filter parameter
$categoria_filter = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;

// Get all videos (filtered or not)
$videos = getVideosByCategory($categoria_filter);

// Get all categories for filters
$categorias = getCategoriesVideos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Descubre nuestras técnicas y transformaciones en acción. Videos de nuestros mejores trabajos y tutoriales.">
    
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
    <div class="video-wrapper">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header fade-in-up">
                <div class="page-header-content">
                    <h1 class="page-title">Nuestro Trabajo en Acción</h1>
                    <p class="page-subtitle">
                        Descubre nuestras técnicas profesionales y las increíbles transformaciones 
                        que realizamos día a día
                    </p>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons fade-in-up">
                <a href="videos.php" class="filter-btn <?php echo !$categoria_filter ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i>
                    Todos
                </a>
                <?php foreach ($categorias as $categoria): ?>
                <a href="videos.php?categoria=<?php echo $categoria['id']; ?>" 
                   class="filter-btn <?php echo $categoria_filter == $categoria['id'] ? 'active' : ''; ?>">
                    <i class="fas fa-<?php 
                        echo match($categoria['nombre']) {
                            'Cortes' => 'cut',
                            'Coloración' => 'palette',
                            'Peinados' => 'magic',
                            'Tratamientos' => 'spa',
                            'Tutoriales' => 'graduation-cap',
                            default => 'video'
                        };
                    ?>"></i>
                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Videos Grid -->
            <?php if (!empty($videos)): ?>
            <div class="videos-grid">
                <?php foreach ($videos as $video): ?>
                <div class="video-card-gallery scroll-reveal">
                    <div class="video-thumbnail-gallery">
                        <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/maxresdefault.jpg" 
                             alt="<?php echo htmlspecialchars($video['titulo']); ?>" 
                             class="video-thumbnail-img">
                        
                        <?php if ($video['categoria_nombre']): ?>
                        <div class="video-category-badge">
                            <?php echo htmlspecialchars($video['categoria_nombre']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="video-play-overlay" onclick="openVideo('<?php echo htmlspecialchars($video['youtube_id']); ?>')">
                            <div class="video-play-btn-gallery">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        <!-- <div class="video-duration">5:30</div> -->
                    </div>
                    
                    <div class="video-content-gallery">
                        <h3 class="video-title-gallery"><?php echo htmlspecialchars($video['titulo']); ?></h3>
                        <p class="video-description-gallery"><?php echo htmlspecialchars($video['descripcion']); ?></p>
                        
                        <div class="video-meta">
                            <span class="video-views">
                                <i class="fas fa-eye"></i>
                                1.2K visualizaciones
                            </span>
                            <span class="video-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($video['fecha_creacion'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-content">
                    <i class="fas fa-video empty-state-icon"></i>
                    <h3 class="empty-state-title">
                        <?php echo $categoria_filter ? 'No hay videos en esta categoría' : 'Próximamente'; ?>
                    </h3>
                    <p class="empty-state-description">
                        <?php if ($categoria_filter): ?>
                            No hemos publicado videos en esta categoría aún. 
                            Revisa otras categorías o vuelve pronto para ver nuevo contenido.
                        <?php else: ?>
                            Estamos preparando contenido increíble para ti. 
                            Pronto podrás ver nuestras técnicas y transformaciones en acción.
                        <?php endif; ?>
                    </p>
                    <?php if ($categoria_filter): ?>
                    <a href="videos.php" class="btn-modern">
                        <i class="fas fa-arrow-left"></i>
                        Ver Todos los Videos
                    </a>
                    <?php else: ?>
                    <a href="citas.php" class="btn-modern">
                        <i class="fas fa-calendar-plus"></i>
                        Reservar Cita
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info Section -->
            <br>
            <div class="info-section">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="info-content scroll-reveal">
                            <h2 class="info-title">Aprende de los Profesionales</h2>
                            <p class="info-description">
                                Nuestros videos no solo muestran el resultado final, sino todo el proceso creativo 
                                detrás de cada transformación. Descubre:
                            </p>
                            
                            <div class="info-features">
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Técnicas profesionales paso a paso</span>
                                </div>
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Consejos de cuidado capilar</span>
                                </div>
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Tendencias y estilos actuales</span>
                                </div>
                                <div class="info-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Transformaciones completas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="info-image scroll-reveal">
                            <img src="./uploads/videos.jfif?height=400&width=500" alt="Grabación de video profesional" class="img-fluid rounded">
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <br>
            <div class="cta-section-inline">
                <div class="cta-content-inline">
                    <h3 class="cta-title-inline">¿Te gustó lo que viste?</h3>
                    <p class="cta-description-inline">
                        Reserva tu cita y vive en primera persona la experiencia de una 
                        transformación profesional
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

        // Video modal
        function openVideo(videoId) {
            const modal = document.createElement('div');
            modal.className = 'modal fade video-modal';
            modal.innerHTML = `
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        <div class="modal-body">
                            <iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0" allowfullscreen></iframe>
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
    </script>
</body>
</html>
