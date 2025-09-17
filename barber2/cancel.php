<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Cita - FSA Studio</title>
    
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
        .step-active {
            background: #164e63;
            color: white;
        }
        .step-completed {
            background: #f59e0b;
            color: white;
        }
        .step-inactive {
            background: #e5e7eb;
            color: #6b7280;
        }
        .time-slot {
            transition: all 0.2s ease;
        }
        .time-slot:hover {
            transform: translateY(-2px);
        }
        .time-slot.selected {
            background: #164e63;
            color: white;
        }
        .time-slot.unavailable {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="font-body bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.html" class="text-2xl font-bold text-primary">FSA Studio</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.html" class="text-muted hover:text-primary transition-colors">Volver al Inicio</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
            <h2 class="text-4xl font-bold text-primary mb-4 text-center">Cancelar Cita</h2>
            
            <form id="cancelForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ID de la Cita</label>
                    <input type="text" id="bookingId" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                           placeholder="Ingresa el ID de tu cita">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                           placeholder="tu@email.com">
                </div>
                
                <button type="submit" 
                        class="w-full bg-red-600 text-white py-3 rounded-md hover:bg-red-700 transition-colors font-medium">
                    Cancelar Cita
                </button>
            </form>

            <div id="message" class="mt-6 hidden"></div>
            
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                <p class="text-sm text-yellow-800">
                    <strong>Nota:</strong> Solo puedes cancelar citas con al menos 2 horas de anticipación. 
                    El ID de la cita se encuentra en el email de confirmación que recibiste.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const bookingId = document.getElementById('bookingId').value;
            const email = document.getElementById('email').value;
            const messageDiv = document.getElementById('message');
            
            try {
                const response = await fetch('api/cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        email: email
                    })
                });
                
                const data = await response.json();
                
                messageDiv.classList.remove('hidden');
                if (data.success) {
                    messageDiv.className = 'mt-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md';
                    messageDiv.textContent = data.message;
                    document.getElementById('cancelForm').reset();
                } else {
                    messageDiv.className = 'mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md';
                    messageDiv.textContent = data.message;
                }
            } catch (error) {
                messageDiv.classList.remove('hidden');
                messageDiv.className = 'mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md';
                messageDiv.textContent = 'Error al procesar la solicitud';
            }
        });
    </script>
</body>
</html>
