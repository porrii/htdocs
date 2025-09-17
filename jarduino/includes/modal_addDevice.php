<!-- Modal Añadir Dispositivo -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeviceModalLabel">Añadir Nuevo Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDeviceForm">
                    <div class="mb-3">
                        <label for="deviceId" class="form-label">ID del Dispositivo *</label>
                        <input type="text" class="form-control" id="deviceId" name="device_id" required 
                               pattern="[A-Za-z0-9_-]{3,20}" title="Solo letras, números, guiones y guiones bajos (3-20 caracteres)">
                        <div class="form-text">ID único del dispositivo ESP32</div>
                    </div>
                    <div class="mb-3">
                        <label for="deviceName" class="form-label">Nombre del Dispositivo *</label>
                        <input type="text" class="form-control" id="deviceName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="deviceDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" id="deviceDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="deviceLocation" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" id="deviceLocation" name="location">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deviceLatitude" class="form-label">Latitud</label>
                                <input type="number" class="form-control" id="deviceLatitude" name="latitude" step="any">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deviceLongitude" class="form-label">Longitud</label>
                                <input type="number" class="form-control" id="deviceLongitude" name="longitude" step="any">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="getCurrentLocation()">
                            <i class="fas fa-location-arrow me-1"></i>Usar mi ubicación actual
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="addDevice()">Añadir Dispositivo</button>
            </div>
        </div>
    </div>
</div>

<script>
function addDevice() {
    const deviceId = document.getElementById('deviceId').value;
    const deviceName = document.getElementById('deviceName').value;
    const description = document.getElementById('deviceDescription').value;
    const location = document.getElementById('deviceLocation').value;
    const latitude = document.getElementById('deviceLatitude').value;
    const longitude = document.getElementById('deviceLongitude').value;
    
    if (!deviceId || !deviceName) {
        alert('Por favor, complete los campos obligatorios');
        return;
    }
    
    // Validar formato del ID del dispositivo
    const idRegex = /^[A-Za-z0-9_-]{3,20}$/;
    if (!idRegex.test(deviceId)) {
        alert('El ID del dispositivo debe tener entre 3 y 20 caracteres y solo puede contener letras, números, guiones y guiones bajos.');
        return;
    }
    
    fetch('../api/add_device.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            device_id: deviceId,
            name: deviceName,
            description: description,
            location: location,
            latitude: latitude,
            longitude: longitude
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            showAlert('Dispositivo añadido correctamente', 'success');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addDeviceModal'));
            modal.hide();
            
            // Recargar la página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error de conexión: ' + error, 'danger');
    });
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('deviceLatitude').value = position.coords.latitude;
                document.getElementById('deviceLongitude').value = position.coords.longitude;
                
                // Intentar obtener la dirección inversa
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.display_name) {
                            document.getElementById('deviceLocation').value = data.display_name;
                        }
                    })
                    .catch(error => {
                        console.error('Error getting location name:', error);
                    });
            },
            function(error) {
                console.error('Error getting location:', error);
                alert('No se pudo obtener la ubicación actual. Asegúrese de que los servicios de ubicación estén activados.');
            }
        );
    } else {
        alert('La geolocalización no es compatible con este navegador.');
    }
}

function showAlert(message, type) {
    // Crear elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar al principio del main
    const main = document.querySelector('main');
    if (main) {
        main.insertBefore(alertDiv, main.firstChild);
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}
</script>