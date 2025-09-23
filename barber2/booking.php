<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Get services for the dropdown
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM services WHERE active = 1 ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected service if provided
$selectedService = isset($_GET['service']) ? (int)$_GET['service'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservar Cita - <?= SITE_NAME ?></title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'sans': ['Space Grotesk', 'system-ui', 'sans-serif'],
            'body': ['DM Sans', 'system-ui', 'sans-serif'],
          },
          colors: {
            primary: '#164e63',
            secondary: '#f59e0b',
            accent: '#f59e0b',
            muted: '#475569',
          }
        }
      }
    }
  </script>

  <style>
    .step-active { background: #164e63; color: white; }
    .step-completed { background: #f59e0b; color: white; }
    .step-inactive { background: #e5e7eb; color: #6b7280; }
    .time-slot { transition: all 0.2s ease; }
    .time-slot:hover { transform: translateY(-2px); }
    .time-slot.selected { background: #164e63; color: white; }
    .time-slot.unavailable { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
  </style>
</head>
<body class="font-body bg-gray-50">

  <!-- Navigation -->
  <nav class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <div class="flex items-center">
          <a href="index.html" class="text-2xl font-bold text-primary"><?= SITE_NAME ?></a>
        </div>
        <div class="flex items-center space-x-4">
          <a href="index.html" class="text-muted hover:text-primary transition-colors text-sm sm:text-base">
            Volver al Inicio
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-4xl mx-auto px-4 py-6 sm:py-8">
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
      <h1 class="text-3xl sm:text-4xl font-bold text-primary mb-3 sm:mb-4">Reservar Cita</h1>
      <p class="text-lg sm:text-xl text-muted">Selecciona tu servicio, fecha y hora preferida</p>
    </div>

    <!-- Progress Steps Responsivo -->
    <div class="overflow-x-auto py-4 px-2 sm:px-0">
    <div class="flex min-w-max space-x-4 sm:space-x-6 items-center">
        
        <!-- Step 1 -->
        <div class="flex flex-col items-center flex-shrink-0">
        <div id="step1-indicator" class="step-active w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold">1</div>
        <span class="mt-1 text-xs sm:text-sm text-center">Servicio</span>
        </div>

        <!-- Connector -->
        <div class="flex-1 h-0.5 bg-gray-300 min-w-[40px]"></div>

        <!-- Step 2 -->
        <div class="flex flex-col items-center flex-shrink-0">
        <div id="step2-indicator" class="step-inactive w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold">2</div>
        <span class="mt-1 text-xs sm:text-sm text-center">Fecha</span>
        </div>

        <!-- Connector -->
        <div class="flex-1 h-0.5 bg-gray-300 min-w-[40px]"></div>

        <!-- Step 3 -->
        <div class="flex flex-col items-center flex-shrink-0">
        <div id="step3-indicator" class="step-inactive w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold">3</div>
        <span class="mt-1 text-xs sm:text-sm text-center">Hora</span>
        </div>

        <!-- Connector -->
        <div class="flex-1 h-0.5 bg-gray-300 min-w-[40px]"></div>

        <!-- Step 4 -->
        <div class="flex flex-col items-center flex-shrink-0">
        <div id="step4-indicator" class="step-inactive w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold">4</div>
        <span class="mt-1 text-xs sm:text-sm text-center">Datos</span>
        </div>

    </div>
    </div>

    <!-- Booking Form -->
    <div class="bg-white rounded-2xl shadow-lg p-4 sm:p-8">
      
      <!-- Step 1 -->
      <div id="step1" class="booking-step">
        <h2 class="text-xl sm:text-2xl font-bold text-primary mb-4 sm:mb-6">Selecciona tu Servicio</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
          <?php foreach($services as $service): ?>
          <div class="service-option border-2 border-gray-200 rounded-xl p-4 sm:p-6 cursor-pointer hover:border-primary transition-colors <?php echo $selectedService == $service['id'] ? 'border-primary bg-primary/5' : ''; ?>" 
            data-service-id="<?php echo $service['id']; ?>"
            data-service-name="<?php echo htmlspecialchars($service['name']); ?>"
            data-service-price="<?php echo $service['price']; ?>"
            data-service-duration="<?php echo $service['duration']; ?>">
            <div class="flex flex-col sm:flex-row justify-between sm:items-start mb-3 sm:mb-4">
              <h3 class="text-lg sm:text-xl font-semibold text-primary"><?php echo htmlspecialchars($service['name']); ?></h3>
              <div class="text-right mt-2 sm:mt-0">
                <div class="text-xl sm:text-2xl font-bold text-secondary">€<?php echo $service['price']; ?></div>
                <div class="text-xs sm:text-sm text-muted"><?php echo $service['duration']; ?> min</div>
              </div>
            </div>
            <p class="text-sm sm:text-base text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="flex justify-end mt-6 sm:mt-8">
          <button id="next-to-step2" class="bg-primary text-white px-6 sm:px-8 py-3 rounded-lg hover:bg-primary/90 transition-colors font-medium text-sm sm:text-base disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Siguiente: Seleccionar Fecha
          </button>
        </div>
      </div>

      <!-- Step 2 -->
      <div id="step2" class="booking-step hidden">
        <h2 class="text-xl sm:text-2xl font-bold text-primary mb-4 sm:mb-6">Selecciona la Fecha</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
          <div>
            <div id="calendar" class="bg-gray-50 rounded-xl p-4 sm:p-6">
              <!-- Calendar -->
            </div>
          </div>
          <div>
            <div class="bg-primary/5 rounded-xl p-4 sm:p-6">
              <h3 class="text-base sm:text-lg font-semibold text-primary mb-3 sm:mb-4">Servicio Seleccionado</h3>
              <div id="selected-service-info"></div>
            </div>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row justify-between mt-6 sm:mt-8 space-y-3 sm:space-y-0 sm:space-x-4">
          <button id="back-to-step1" class="border border-primary text-primary px-6 sm:px-8 py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium">
            Anterior
          </button>
          <button id="next-to-step3" class="bg-primary text-white px-6 sm:px-8 py-3 rounded-lg hover:bg-primary/90 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
            Siguiente: Seleccionar Hora
          </button>
        </div>
      </div>

      <!-- Step 3 -->
      <div id="step3" class="booking-step hidden">
        <h2 class="text-xl sm:text-2xl font-bold text-primary mb-4 sm:mb-6">Selecciona la Hora</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
          <div>
            <div id="time-slots" class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3"></div>
          </div>
          <div>
            <div class="bg-primary/5 rounded-xl p-4 sm:p-6">
              <h3 class="text-base sm:text-lg font-semibold text-primary mb-3 sm:mb-4">Resumen de tu Cita</h3>
              <div id="appointment-summary"></div>
            </div>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row justify-between mt-6 sm:mt-8 space-y-3 sm:space-y-0 sm:space-x-4">
          <button id="back-to-step2" class="border border-primary text-primary px-6 sm:px-8 py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium">
            Anterior
          </button>
          <button id="next-to-step4" class="bg-primary text-white px-6 sm:px-8 py-3 rounded-lg hover:bg-primary/90 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
            Siguiente: Datos Personales
          </button>
        </div>
      </div>

      <!-- Step 4 -->
      <div id="step4" class="booking-step hidden">
        <h2 class="text-xl sm:text-2xl font-bold text-primary mb-4 sm:mb-6">Datos Personales</h2>
        <form id="booking-form" class="space-y-4 sm:space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-sm font-medium text-muted mb-1 sm:mb-2">Nombre Completo *</label>
              <input type="text" id="client-name" name="client_name" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
              <label class="block text-sm font-medium text-muted mb-1 sm:mb-2">Email *</label>
              <input type="email" id="client-email" name="client_email" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-muted mb-1 sm:mb-2">Teléfono</label>
            <input type="tel" id="client-phone" name="client_phone" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
          </div>
          <div>
            <label class="block text-sm font-medium text-muted mb-1 sm:mb-2">Notas Adicionales</label>
            <textarea id="notes" name="notes" rows="4" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Alguna preferencia especial o comentario..."></textarea>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-primary mb-3 sm:mb-4">Resumen Final</h3>
            <div id="final-summary"></div>
          </div>
        </form>
        <div class="flex flex-col sm:flex-row justify-between mt-6 sm:mt-8 space-y-3 sm:space-y-0 sm:space-x-4">
          <button id="back-to-step3" class="border border-primary text-primary px-6 sm:px-8 py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium">
            Anterior
          </button>
          <button id="confirm-booking" class="bg-secondary text-white px-6 sm:px-8 py-3 rounded-lg hover:bg-secondary/90 transition-colors font-medium text-base sm:text-lg">
            Confirmar Reserva
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="success-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden px-4">
    <div class="bg-white rounded-2xl p-6 sm:p-8 max-w-md w-full">
      <div class="text-center">
        <div class="bg-green-100 rounded-full w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center mx-auto mb-4">
          <svg class="w-6 h-6 sm:w-8 sm:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h3 class="text-xl sm:text-2xl font-bold text-primary mb-3 sm:mb-4">¡Cita Confirmada!</h3>
        <p class="text-sm sm:text-base text-muted mb-4 sm:mb-6">Tu cita ha sido reservada exitosamente. Recibirás un email de confirmación en breve.</p>
        <div class="space-y-2 sm:space-y-3">
          <a href="index.html" class="block w-full bg-primary text-white py-2 sm:py-3 rounded-lg hover:bg-primary/90 transition-colors font-medium">
            Volver al Inicio
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="js/booking.js"></script>
</body>
</html>
