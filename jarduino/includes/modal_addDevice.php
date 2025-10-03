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
                    <input type="hidden" id="userId" name="user_id" value="<?php echo $user_id; ?>">
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
                    <!-- <div class="mb-3">
                        <label for="deviceDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" id="deviceDescription" name="description" rows="2"></textarea>
                    </div> -->
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