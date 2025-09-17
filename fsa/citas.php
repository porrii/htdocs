<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Generate CSRF token
session_start();
$csrf_token = generateCSRFToken();

// Get business hours for validation
$horarios = getHorariosCompletos();

// Get services for the form
$servicios = getAllServicios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Cita - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Reserva tu cita online de forma rápida y sencilla. Elige fecha, hora y servicio.">
    
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
    <div class="cita-wrapper">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header fade-in-up">
                <div class="page-header-content">
                    <h1 class="page-title">Reservar Cita</h1>
                    <p class="page-subtitle">
                        Selecciona tu fecha, hora y servicio preferido. Te confirmaremos tu cita por email.
                    </p>
                </div>
            </div>

            <!-- Appointment Form -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="form-container fade-in-up fade-in-up-delay-1">
                        <form id="appointmentForm" method="POST" action="ajax/procesar-cita.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Personal Information -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <div class="form-section-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    Información Personal
                                </h3>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre" class="form-label">Nombre Completo *</label>
                                            <input type="text" id="nombre" name="nombre" class="form-control-modern" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" id="email" name="email" class="form-control-modern" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="telefono" class="form-label">Teléfono *</label>
                                            <input type="tel" id="telefono" name="telefono" class="form-control-modern" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="servicio" class="form-label">Servicio *</label>
                                            <select id="servicio" name="servicio" class="form-control-modern form-select-modern" required>
                                                <option value="">Selecciona un servicio</option>
                                                <?php foreach ($servicios as $servicio): ?>
                                                <option value="<?php echo htmlspecialchars($servicio['nombre']); ?>" 
                                                        data-duracion="<?php echo $servicio['duracion']; ?>"
                                                        data-precio="<?php echo $servicio['precio']; ?>">
                                                    <?php echo htmlspecialchars($servicio['nombre']); ?>
                                                    <?php if ($servicio['precio']): ?>
                                                        - €<?php echo number_format($servicio['precio'], 2); ?>
                                                    <?php endif; ?>
                                                    (<?php echo $servicio['duracion']; ?> min)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Date Selection -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <div class="form-section-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    Seleccionar Fecha
                                </h3>
                                
                                <div class="calendar-container">
                                    <div class="calendar-header">
                                        <button type="button" class="calendar-nav-btn" id="prevMonth">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div class="calendar-month" id="currentMonth"></div>
                                        <button type="button" class="calendar-nav-btn" id="nextMonth">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="calendar-grid" id="calendarGrid">
                                        <!-- Calendar will be generated by JavaScript -->
                                    </div>
                                    
                                    <input type="hidden" id="fecha" name="fecha" required>
                                </div>
                            </div>

                            <!-- Time Selection -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <div class="form-section-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    Seleccionar Hora
                                </h3>
                                
                                <div class="time-slots-container">
                                    <div id="timeSlotsMessage" class="text-center text-muted mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Selecciona una fecha para ver las horas disponibles
                                    </div>
                                    
                                    <div class="time-slots-grid" id="timeSlots">
                                        <!-- Time slots will be loaded by JavaScript -->
                                    </div>
                                    
                                    <input type="hidden" id="hora" name="hora" required>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" class="btn-modern" id="submitBtn" disabled>
                                    <i class="fas fa-calendar-check"></i>
                                    Confirmar Reserva
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once "includes/footer.php";?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calendar and appointment booking functionality with improved validation and UX
        class AppointmentBooking {
            constructor() {
                this.currentDate = new Date();
                this.selectedDate = null;
                this.selectedTime = null;
                this.businessHours = <?php echo json_encode($horarios); ?>;
                this.formFields = {
                    nombre: document.getElementById('nombre'),
                    email: document.getElementById('email'),
                    telefono: document.getElementById('telefono'),
                    servicio: document.getElementById('servicio'),
                    fecha: document.getElementById('fecha'),
                    hora: document.getElementById('hora')
                };
                this.submitBtn = document.getElementById('submitBtn');
                this.form = document.getElementById('appointmentForm');
                
                this.init();
            }
            
            init() {
                this.renderCalendar();
                this.bindEvents();
                this.validateForm();
                
                // Inicializar tooltips para mejor UX
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        boundary: document.body
                    });
                });
                
                // Añadir atributos de accesibilidad
                this.enhanceAccessibility();
            }
            
            enhanceAccessibility() {
                // Añadir roles ARIA y atributos para mejorar accesibilidad
                document.getElementById('calendarGrid').setAttribute('role', 'grid');
                document.getElementById('calendarGrid').setAttribute('aria-label', 'Calendario para seleccionar fecha');
                
                document.getElementById('timeSlots').setAttribute('role', 'radiogroup');
                document.getElementById('timeSlots').setAttribute('aria-label', 'Horarios disponibles');
                
                // Añadir instrucciones para lectores de pantalla
                const srInstructions = document.createElement('p');
                srInstructions.className = 'sr-only';
                srInstructions.textContent = 'Seleccione una fecha en el calendario y luego un horario disponible para su cita.';
                this.form.prepend(srInstructions);
            }
            
            bindEvents() {
                document.getElementById('prevMonth').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    this.renderCalendar();
                });
                
                document.getElementById('nextMonth').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    this.renderCalendar();
                });
                
                // Validación en tiempo real con feedback visual
                Object.values(this.formFields).forEach(input => {
                    if (!input) return;
                    
                    input.addEventListener('blur', () => {
                        this.validateField(input);
                    });
                    
                    input.addEventListener('input', () => {
                        if (input.classList.contains('is-invalid')) {
                            this.validateField(input);
                        }
                        this.validateForm();
                    });
                });
                
                // Validación al enviar
                this.form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Validar todos los campos antes de enviar
                    let isValid = true;
                    Object.values(this.formFields).forEach(input => {
                        if (!input) return;
                        if (!this.validateField(input)) {
                            isValid = false;
                        }
                    });
                    
                    if (isValid) {
                        this.submitForm();
                    } else {
                        // Enfocar el primer campo con error
                        const firstInvalid = this.form.querySelector('.is-invalid');
                        if (firstInvalid) {
                            firstInvalid.focus();
                            
                            // Mostrar mensaje de error general
                            this.showAlert('error', 'Por favor, corrija los errores', 'Hay campos con información incorrecta o incompleta.');
                        }
                    }
                });
                
                // Añadir navegación por teclado para el calendario
                document.addEventListener('keydown', (e) => {
                    if (!this.selectedDate) return;
                    
                    const currentDate = new Date(this.selectedDate);
                    
                    switch (e.key) {
                        case 'ArrowLeft':
                            currentDate.setDate(currentDate.getDate() - 1);
                            break;
                        case 'ArrowRight':
                            currentDate.setDate(currentDate.getDate() + 1);
                            break;
                        case 'ArrowUp':
                            currentDate.setDate(currentDate.getDate() - 7);
                            break;
                        case 'ArrowDown':
                            currentDate.setDate(currentDate.getDate() + 7);
                            break;
                        default:
                            return;
                    }
                    
                    // Verificar si la nueva fecha es válida
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (currentDate >= today) {
                        this.selectDate(currentDate);
                        e.preventDefault();
                    }
                });
            }
            
            validateField(input) {
                if (!input) return true;
                
                const value = input.value.trim();
                const name = input.name;
                let isValid = true;
                let errorMessage = '';
                
                // Eliminar clases de validación anteriores
                input.classList.remove('is-valid', 'is-invalid');
                
                // Validar según el tipo de campo
                if (value === '') {
                    if (input.hasAttribute('required')) {
                        isValid = false;
                        errorMessage = 'Este campo es obligatorio';
                    }
                } else {
                    switch (name) {
                        case 'email':
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
                            if (!emailRegex.test(value)) {
                                isValid = false;
                                errorMessage = 'Introduce un email válido';
                            }
                            break;
                        case 'telefono':
                            // Permitir formatos internacionales y espacios
                            const phoneRegex = /^[+]?[\d\s\-()]{9,}$/;
                            if (!phoneRegex.test(value)) {
                                isValid = false;
                                errorMessage = 'Introduce un teléfono válido';
                            }
                            break;
                    }
                }
                
                // Mostrar feedback visual
                if (isValid) {
                    input.classList.add('is-valid');
                    
                    // Eliminar mensaje de error si existe
                    const errorElement = input.parentElement.querySelector('.invalid-feedback');
                    if (errorElement) {
                        errorElement.remove();
                    }
                } else {
                    input.classList.add('is-invalid');
                    
                    // Mostrar mensaje de error
                    let errorElement = input.parentElement.querySelector('.invalid-feedback');
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'invalid-feedback';
                        input.parentElement.appendChild(errorElement);
                    }
                    errorElement.textContent = errorMessage;
                }
                
                return isValid;
            }
            
            renderCalendar() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                
                // Update month display
                const monthNames = [
                    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ];
                document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
                
                // Clear calendar
                const calendarGrid = document.getElementById('calendarGrid');
                calendarGrid.innerHTML = '';
                
                // Add day headers
                const dayHeaders = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                dayHeaders.forEach(day => {
                    const dayHeader = document.createElement('div');
                    dayHeader.className = 'calendar-day-header';
                    dayHeader.textContent = day;
                    dayHeader.setAttribute('role', 'columnheader');
                    calendarGrid.appendChild(dayHeader);
                });
                
                // Get first day of month and number of days
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                // Add empty cells for days before month starts
                for (let i = 0; i < firstDay; i++) {
                    const emptyDay = document.createElement('div');
                    emptyDay.className = 'calendar-day disabled';
                    emptyDay.setAttribute('aria-hidden', 'true');
                    calendarGrid.appendChild(emptyDay);
                }
                
                // Add days of month
                for (let day = 1; day <= daysInMonth; day++) {
                    const dayElement = document.createElement('div');
                    dayElement.className = 'calendar-day';
                    dayElement.textContent = day;
                    dayElement.setAttribute('role', 'gridcell');
                    dayElement.setAttribute('tabindex', '0');
                    dayElement.setAttribute('aria-label', `${day} de ${monthNames[month]} de ${year}`);
                    
                    const dayDate = new Date(year, month, day);
                    const dayName = dayDate.toLocaleDateString('es-ES', { weekday: 'long' });
                    const dayNameCapitalized = dayName.charAt(0).toUpperCase() + dayName.slice(1);
                    
                    // Check if day is in the past - CORREGIDO
                    const today = new Date();
                    today.setHours(0, 0, 0, 0); // Resetear horas para comparar solo fechas
                    dayDate.setHours(0, 0, 0, 0);

                    if (dayDate < today) {
                        dayElement.classList.add('disabled');
                        dayElement.setAttribute('aria-disabled', 'true');
                    }
                    // Check if business is closed
                    else if (this.businessHours[dayNameCapitalized] && this.businessHours[dayNameCapitalized].cerrado) {
                        dayElement.classList.add('disabled');
                        dayElement.setAttribute('aria-disabled', 'true');
                        dayElement.setAttribute('data-bs-toggle', 'tooltip');
                        dayElement.setAttribute('data-bs-title', 'Cerrado este día');
                    }
                    // Check if it's today
                    else if (dayDate.toDateString() === new Date().toDateString()) {
                        dayElement.classList.add('today');
                        dayElement.setAttribute('aria-current', 'date');
                    }
                    
                    // Check if it's the selected date
                    if (this.selectedDate && dayDate.toDateString() === this.selectedDate.toDateString()) {
                        dayElement.classList.add('selected');
                        dayElement.setAttribute('aria-selected', 'true');
                    }
                    
                    // Add click event if not disabled
                    if (!dayElement.classList.contains('disabled')) {
                        dayElement.addEventListener('click', () => {
                            this.selectDate(dayDate);
                        });
                        
                        // Añadir evento de teclado para accesibilidad
                        dayElement.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                this.selectDate(dayDate);
                            }
                        });
                    }
                    
                    calendarGrid.appendChild(dayElement);
                }
            }
            
            selectDate(date) {
                // Remove previous selection
                document.querySelectorAll('.calendar-day.selected').forEach(day => {
                    day.classList.remove('selected');
                    day.setAttribute('aria-selected', 'false');
                });
                
                // Add selection to clicked day
                this.selectedDate = date;
                
                // Formatear fecha correctamente para el input
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const fechaFormateada = `${year}-${month}-${day}`;
                
                document.getElementById('fecha').value = fechaFormateada;
                
                // Update calendar if the selected date is in a different month
                if (date.getMonth() !== this.currentDate.getMonth() || 
                    date.getFullYear() !== this.currentDate.getFullYear()) {
                    this.currentDate = new Date(date);
                    this.renderCalendar();
                } else {
                    // Just update the selected day
                    const days = document.querySelectorAll('.calendar-day:not(.disabled)');
                    days.forEach(day => {
                        if (parseInt(day.textContent) === date.getDate()) {
                            day.classList.add('selected');
                            day.setAttribute('aria-selected', 'true');
                        }
                    });
                }
                
                // Load time slots for selected date - CORREGIDO
                this.loadTimeSlots(fechaFormateada);
                this.validateForm();
            }
            
            async loadTimeSlots(dateString) {
                const timeSlotsContainer = document.getElementById('timeSlots');
                const timeSlotsMessage = document.getElementById('timeSlotsMessage');
                
                // Show loading state
                timeSlotsContainer.classList.add('loading');
                timeSlotsMessage.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando horarios disponibles...';
                timeSlotsMessage.style.display = 'block';
                
                try {
                    const response = await fetch('ajax/get-time-slots.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            fecha: dateString
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.renderTimeSlots(data.slots);
                        if (data.slots.length > 0) {
                            timeSlotsMessage.style.display = 'none';
                        } else {
                            timeSlotsMessage.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>No hay horarios disponibles para esta fecha';
                        }
                    } else {
                        timeSlotsMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + data.message;
                        timeSlotsContainer.innerHTML = '';
                    }
                } catch (error) {
                    console.error('Error loading time slots:', error);
                    timeSlotsMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los horarios. Por favor, inténtalo de nuevo.';
                    timeSlotsContainer.innerHTML = '';
                } finally {
                    timeSlotsContainer.classList.remove('loading');
                }
            }
            
            renderTimeSlots(slots) {
                const timeSlotsContainer = document.getElementById('timeSlots');
                timeSlotsContainer.innerHTML = '';
                
                if (slots.length === 0) {
                    document.getElementById('timeSlotsMessage').innerHTML = '<i class="fas fa-info-circle me-2"></i>No hay horarios disponibles para esta fecha';
                    document.getElementById('timeSlotsMessage').style.display = 'block';
                    return;
                }
                
                // Agrupar por mañana/tarde para mejor organización
                const morning = [];
                const afternoon = [];
                
                slots.forEach(slot => {
                    const hour = parseInt(slot.hora.split(':')[0]);
                    if (hour < 12) {
                        morning.push(slot);
                    } else {
                        afternoon.push(slot);
                    }
                });
                
                // Crear contenedores para mañana y tarde
                if (morning.length > 0) {
                    const morningHeader = document.createElement('h6');
                    morningHeader.className = 'time-slot-header';
                    morningHeader.innerHTML = '<i class="fas fa-sun me-2"></i>Mañana';
                    timeSlotsContainer.appendChild(morningHeader);
                    
                    const morningContainer = document.createElement('div');
                    morningContainer.className = 'time-slots-grid';
                    timeSlotsContainer.appendChild(morningContainer);
                    
                    this.renderTimeSlotGroup(morning, morningContainer);
                }
                
                if (afternoon.length > 0) {
                    const afternoonHeader = document.createElement('h6');
                    afternoonHeader.className = 'time-slot-header mt-3';
                    afternoonHeader.innerHTML = '<i class="fas fa-moon me-2"></i>Tarde';
                    timeSlotsContainer.appendChild(afternoonHeader);
                    
                    const afternoonContainer = document.createElement('div');
                    afternoonContainer.className = 'time-slots-grid';
                    timeSlotsContainer.appendChild(afternoonContainer);
                    
                    this.renderTimeSlotGroup(afternoon, afternoonContainer);
                }
            }
            
            renderTimeSlotGroup(slots, container) {
                slots.forEach((slot, index) => {
                    const timeSlot = document.createElement('div');
                    timeSlot.className = 'time-slot';
                    timeSlot.textContent = slot.hora;
                    timeSlot.setAttribute('role', 'radio');
                    timeSlot.setAttribute('aria-checked', 'false');
                    timeSlot.setAttribute('tabindex', '0');
                    
                    if (!slot.disponible) {
                        timeSlot.classList.add('unavailable');
                        timeSlot.setAttribute('aria-disabled', 'true');
                        timeSlot.innerHTML += '<br><small>No disponible</small>';
                    } else {
                        timeSlot.addEventListener('click', () => {
                            this.selectTime(slot.hora);
                        });
                        
                        // Añadir navegación por teclado
                        timeSlot.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                this.selectTime(slot.hora);
                            }
                        });
                    }
                    
                    container.appendChild(timeSlot);
                });
            }
            
            selectTime(time) {
                // Remove previous selection
                document.querySelectorAll('.time-slot.selected').forEach(slot => {
                    slot.classList.remove('selected');
                    slot.setAttribute('aria-checked', 'false');
                });
                
                // Add selection to clicked time
                const slots = document.querySelectorAll('.time-slot');
                slots.forEach(slot => {
                    if (slot.textContent.includes(time)) {
                        slot.classList.add('selected');
                        slot.setAttribute('aria-checked', 'true');
                    }
                });
                
                this.selectedTime = time;
                document.getElementById('hora').value = time;
                this.validateForm();
                
                // Scroll to submit button for better UX
                this.submitBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            validateForm() {
                let isValid = true;
                
                // Check required fields
                Object.values(this.formFields).forEach(field => {
                    if (!field) return;
                    
                    if (field.hasAttribute('required') && !field.value.trim()) {
                        isValid = false;
                    }
                });
                
                // Validate email format if provided
                const email = this.formFields.email?.value.trim();
                if (email && !this.isValidEmail(email)) {
                    isValid = false;
                }
                
                // Validate phone format if provided
                const telefono = this.formFields.telefono?.value.trim();
                if (telefono && !this.isValidPhone(telefono)) {
                    isValid = false;
                }
                
                this.submitBtn.disabled = !isValid;
                
                // Añadir feedback visual al botón
                if (isValid) {
                    this.submitBtn.classList.add('pulse-animation');
                } else {
                    this.submitBtn.classList.remove('pulse-animation');
                }
                
                return isValid;
            }
            
            isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
                return emailRegex.test(email);
            }
            
            isValidPhone(phone) {
                const phoneRegex = /^[+]?[\d\s\-()]{9,}$/;
                return phoneRegex.test(phone);
            }
            
            async submitForm() {
                if (!this.validateForm()) return;
                
                const form = document.getElementById('appointmentForm');
                const formData = new FormData(form);
                
                // Show loading state
                this.submitBtn.disabled = true;
                this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                
                try {
                    const response = await fetch('ajax/procesar-cita.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Mostrar confirmación
                        this.showAlert('success', '¡Cita reservada correctamente!', data.message);
                        
                        // Limpiar formulario
                        form.reset();
                        this.selectedDate = null;
                        this.selectedTime = null;
                        document.getElementById('timeSlots').innerHTML = '';
                        document.getElementById('timeSlotsMessage').style.display = 'block';
                        document.getElementById('timeSlotsMessage').innerHTML = '<i class="fas fa-info-circle me-2"></i>Selecciona una fecha para ver las horas disponibles';
                        
                        // Eliminar selecciones del calendario
                        document.querySelectorAll('.calendar-day.selected').forEach(day => {
                            day.classList.remove('selected');
                            day.setAttribute('aria-selected', 'false');
                        });
                        
                        // Mostrar resumen de la cita
                        this.showAppointmentSummary(data.cita_id, formData);
                    } else {
                        this.showAlert('error', 'Error al reservar la cita', data.message);
                    }
                } catch (error) {
                    console.error('Error submitting form:', error);
                    this.showAlert('error', 'Error de conexión', 'No se pudo procesar tu solicitud. Por favor, inténtalo de nuevo.');
                } finally {
                    this.submitBtn.disabled = false;
                    this.submitBtn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Confirmar Reserva';
                    this.validateForm();
                }
            }
            
            showAppointmentSummary(citaId, formData) {
                // Crear modal de confirmación
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.id = 'appointmentSummaryModal';
                modal.setAttribute('tabindex', '-1');
                modal.setAttribute('aria-labelledby', 'appointmentSummaryModalLabel');
                modal.setAttribute('aria-hidden', 'true');
                
                const nombre = formData.get('nombre');
                const email = formData.get('email');
                const telefono = formData.get('telefono');
                const servicio = formData.get('servicio');
                const fecha = new Date(formData.get('fecha')).toLocaleDateString('es-ES', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                const hora = formData.get('hora');
                
                modal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="appointmentSummaryModalLabel">
                                    <i class="fas fa-check-circle me-2"></i>¡Cita Confirmada!
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <div class="display-1 text-success mb-3">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <h4>¡Gracias por tu reserva!</h4>
                                    <p class="text-muted">Hemos enviado un correo de confirmación a tu email.</p>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Detalles de tu cita</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Número de cita:</strong> ${citaId}</p>
                                        <p><strong>Nombre:</strong> ${nombre}</p>
                                        <p><strong>Servicio:</strong> ${servicio}</p>
                                        <p><strong>Fecha:</strong> ${fecha}</p>
                                        <p><strong>Hora:</strong> ${hora}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                                <a href="index.php" class="btn-modern">
                                    <i class="fas fa-home me-2"></i>Volver al Inicio
                                </a>
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
            
            showAlert(type, title, message) {
                const alertContainer = document.createElement('div');
                alertContainer.className = `alert-modern alert-${type}`;
                alertContainer.setAttribute('role', 'alert');
                alertContainer.innerHTML = `
                    <div class="alert-icon">
                        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'exclamation-triangle'}"></i>
                    </div>
                    <div>
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                `;
                
                // Insert at top of form
                const form = document.getElementById('appointmentForm');
                form.insertBefore(alertContainer, form.firstChild);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (alertContainer.parentNode) {
                        alertContainer.style.opacity = '0';
                        setTimeout(() => {
                            if (alertContainer.parentNode) {
                                alertContainer.parentNode.removeChild(alertContainer);
                            }
                        }, 300);
                    }
                }, 5000);
                
                // Scroll to top
                alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        // Initialize appointment booking when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new AppointmentBooking();
            
            // Añadir clase para animación del botón
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    @keyframes pulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.05); }
                        100% { transform: scale(1); }
                    }
                    .pulse-animation {
                        animation: pulse 2s infinite;
                    }
                    
                    .time-slot-header {
                        margin-bottom: 0.75rem;
                        color: var(--text-primary);
                        font-weight: 600;
                    }
                    
                    .invalid-feedback {
                        display: block;
                        width: 100%;
                        margin-top: 0.25rem;
                        font-size: 0.875em;
                        color: #ef4444;
                    }
                </style>
            `);
        });
    </script>
</body>
</html>
