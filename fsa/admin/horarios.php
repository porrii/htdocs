<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();

// Handle form submissions
$mensaje = '';
$tipo_mensaje = '';

if ($_POST && isset($_POST['actualizar_horarios'])) {
    $horarios = [];
    
    foreach ($_POST['horarios'] as $dia => $datos) {
        $horarios[$dia] = [
            'cerrado' => isset($datos['cerrado']),
            'horario_partido' => isset($datos['horario_partido']),
            'hora_apertura' => sanitizeInput($datos['hora_apertura']),
            'hora_cierre' => sanitizeInput($datos['hora_cierre']),
            'hora_apertura_tarde' => sanitizeInput($datos['hora_apertura_tarde']),
            'hora_cierre_tarde' => sanitizeInput($datos['hora_cierre_tarde'])
        ];
    }
    
    if (actualizarHorarios($horarios)) {
        $mensaje = 'Horarios actualizados correctamente.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al actualizar los horarios.';
        $tipo_mensaje = 'error';
    }
}

// Get current business hours
$horariosActuales = getHorariosCompletos();

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-styles.css">
    
    <style>
        .schedule-card {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .schedule-card.closed {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        
        .schedule-card.open {
            background-color: #f0f9ff;
            border-color: #0ea5e9;
        }
        
        .day-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .day-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .schedule-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .time-inputs.split {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }
        
        .time-group {
            display: flex;
            flex-direction: column;
        }
        
        .time-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .split-schedule {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .data-card.fade-in-up {
            height: auto !important;         /* Evita que se estire verticalmente */
            min-height: unset !important;    /* Elimina mínimos forzados */
            overflow: visible;               /* Asegura que no se recorte el contenido */
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
            <!-- Header -->
            <div class="dashboard-header fade-in-up">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="dashboard-title">
                        <i class="fas fa-clock"></i>
                        Gestión de Horarios
                    </h1>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-outline-modern" onclick="resetToDefault()">
                            <i class="fas fa-undo"></i>
                            Restaurar Predeterminado
                        </button>
                        <button type="button" class="btn-modern" onclick="saveSchedule()">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($mensaje): ?>
            <div class="alert-modern alert-<?php echo $tipo_mensaje; ?> fade-in-up">
                <div class="alert-icon">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                </div>
                <div><?php echo $mensaje; ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Schedule Form -->
            <form method="POST" id="scheduleForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="row">
                    <?php 
                    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    foreach ($dias as $dia): 
                        $horario = $horariosActuales[$dia] ?? [
                            'cerrado' => false,
                            'horario_partido' => false,
                            'hora_apertura' => '09:00',
                            'hora_cierre' => '18:00',
                            'hora_apertura_tarde' => '16:00',
                            'hora_cierre_tarde' => '20:00'
                        ];
                    ?>
                    <div class="col-lg-6">
                        <div class="schedule-card <?php echo $horario['cerrado'] ? 'closed' : 'open'; ?>" data-day="<?php echo $dia; ?>">
                            <div class="day-header">
                                <div class="day-name"><?php echo $dia; ?></div>
                                <div class="schedule-toggle">
                                    <label class="form-check-label" for="cerrado_<?php echo $dia; ?>">Cerrado</label>
                                    <input type="checkbox" class="form-check-input" 
                                           id="cerrado_<?php echo $dia; ?>" 
                                           name="horarios[<?php echo $dia; ?>][cerrado]"
                                           <?php echo $horario['cerrado'] ? 'checked' : ''; ?>
                                           onchange="toggleDay('<?php echo $dia; ?>')">
                                </div>
                            </div>
                            
                            <div class="schedule-content" style="<?php echo $horario['cerrado'] ? 'display: none;' : ''; ?>">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" 
                                           id="partido_<?php echo $dia; ?>" 
                                           name="horarios[<?php echo $dia; ?>][horario_partido]"
                                           <?php echo $horario['horario_partido'] ? 'checked' : ''; ?>
                                           onchange="toggleSplitSchedule('<?php echo $dia; ?>')">
                                    <label class="form-check-label" for="partido_<?php echo $dia; ?>">
                                        Horario partido (mañana y tarde)
                                    </label>
                                </div>
                                
                                <div class="time-inputs" id="timeInputs_<?php echo $dia; ?>">
                                    <div class="time-group">
                                        <label>Hora de apertura</label>
                                        <input type="time" class="form-control-modern" 
                                               name="horarios[<?php echo $dia; ?>][hora_apertura]"
                                               value="<?php echo $horario['hora_apertura']; ?>">
                                    </div>
                                    
                                    <div class="time-group">
                                        <label>Hora de cierre</label>
                                        <input type="time" class="form-control-modern" 
                                               name="horarios[<?php echo $dia; ?>][hora_cierre]"
                                               value="<?php echo $horario['hora_cierre']; ?>">
                                    </div>
                                </div>
                                
                                <div class="split-schedule" id="splitSchedule_<?php echo $dia; ?>" 
                                     style="<?php echo $horario['horario_partido'] ? '' : 'display: none;'; ?>">
                                    <div class="time-inputs">
                                        <div class="time-group">
                                            <label>Apertura tarde</label>
                                            <input type="time" class="form-control-modern" 
                                                   name="horarios[<?php echo $dia; ?>][hora_apertura_tarde]"
                                                   value="<?php echo $horario['hora_apertura_tarde']; ?>">
                                        </div>
                                        
                                        <div class="time-group">
                                            <label>Cierre tarde</label>
                                            <input type="time" class="form-control-modern" 
                                                   name="horarios[<?php echo $dia; ?>][hora_cierre_tarde]"
                                                   value="<?php echo $horario['hora_cierre_tarde']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" name="actualizar_horarios" class="btn-modern">
                        <i class="fas fa-save"></i>
                        Guardar Horarios
                    </button>
                </div>
            </form>
            
            <!-- Preview Section -->
            <div class="data-card mt-4 fade-in-up">
                <div class="data-header">
                    <h3 class="data-title">
                        <i class="fas fa-eye"></i>
                        Vista Previa - Cómo se verá en la web
                    </h3>
                </div>
                <div class="data-body">
                    <div class="row" id="schedulePreview">
                        <!-- Preview will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleDay(dia) {
        const checkbox = document.getElementById(`cerrado_${dia}`);
        const card = document.querySelector(`[data-day="${dia}"]`);
        const content = card.querySelector('.schedule-content');
        
        if (checkbox.checked) {
            card.classList.remove('open');
            card.classList.add('closed');
            content.style.display = 'none';
        } else {
            card.classList.remove('closed');
            card.classList.add('open');
            content.style.display = 'block';
        }
        
        updatePreview();
    }
    
    function toggleSplitSchedule(dia) {
        const checkbox = document.getElementById(`partido_${dia}`);
        const splitSchedule = document.getElementById(`splitSchedule_${dia}`);
        const timeInputs = document.getElementById(`timeInputs_${dia}`);
        
        if (checkbox.checked) {
            splitSchedule.style.display = 'block';
            timeInputs.classList.add('split');
            
            // Update labels for split schedule
            const labels = timeInputs.querySelectorAll('label');
            labels[0].textContent = 'Apertura mañana';
            labels[1].textContent = 'Cierre mañana';
        } else {
            splitSchedule.style.display = 'none';
            timeInputs.classList.remove('split');
            
            // Update labels for normal schedule
            const labels = timeInputs.querySelectorAll('label');
            labels[0].textContent = 'Hora de apertura';
            labels[1].textContent = 'Hora de cierre';
        }
        
        updatePreview();
    }
    
    function updatePreview() {
        const preview = document.getElementById('schedulePreview');
        const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        
        let html = '';
        
        dias.forEach(dia => {
            const cerrado = document.getElementById(`cerrado_${dia}`).checked;
            const partido = document.getElementById(`partido_${dia}`).checked;
            
            let horarioText = '';
            
            if (cerrado) {
                horarioText = '<span class="text-danger">Cerrado</span>';
            } else {
                const apertura = document.querySelector(`input[name="horarios[${dia}][hora_apertura]"]`).value;
                const cierre = document.querySelector(`input[name="horarios[${dia}][hora_cierre]"]`).value;
                
                if (partido) {
                    const aperturaTarde = document.querySelector(`input[name="horarios[${dia}][hora_apertura_tarde]"]`).value;
                    const cierreTarde = document.querySelector(`input[name="horarios[${dia}][hora_cierre_tarde]"]`).value;
                    horarioText = `${apertura} - ${cierre} | ${aperturaTarde} - ${cierreTarde}`;
                } else {
                    horarioText = `${apertura} - ${cierre}`;
                }
            }
            
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                        <strong>${dia}</strong>
                        <span>${horarioText}</span>
                    </div>
                </div>
            `;
        });
        
        preview.innerHTML = html;
    }
    
    function resetToDefault() {
        if (confirm('¿Estás seguro de que quieres restaurar los horarios predeterminados?')) {
            const defaultSchedule = {
                'Lunes': { open: '09:00', close: '18:00', closed: false, split: false },
                'Martes': { open: '09:00', close: '18:00', closed: false, split: false },
                'Miércoles': { open: '09:00', close: '18:00', closed: false, split: false },
                'Jueves': { open: '09:00', close: '18:00', closed: false, split: false },
                'Viernes': { open: '09:00', close: '18:00', closed: false, split: false },
                'Sábado': { open: '09:00', close: '14:00', closed: false, split: false },
                'Domingo': { open: '09:00', close: '18:00', closed: true, split: false }
            };
            
            Object.keys(defaultSchedule).forEach(dia => {
                const schedule = defaultSchedule[dia];
                
                document.getElementById(`cerrado_${dia}`).checked = schedule.closed;
                document.getElementById(`partido_${dia}`).checked = schedule.split;
                document.querySelector(`input[name="horarios[${dia}][hora_apertura]"]`).value = schedule.open;
                document.querySelector(`input[name="horarios[${dia}][hora_cierre]"]`).value = schedule.close;
                
                toggleDay(dia);
                toggleSplitSchedule(dia);
            });
        }
    }
    
    function saveSchedule() {
        document.getElementById('scheduleForm').submit();
    }
    
    // Initialize preview on page load
    document.addEventListener('DOMContentLoaded', function() {
        updatePreview();
        
        // Add event listeners to all time inputs
        document.querySelectorAll('input[type="time"]').forEach(input => {
            input.addEventListener('change', updatePreview);
        });
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-modern');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>
</body>
</html>
