<!-- Modal Editar Dispositivo -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDeviceModalLabel">Editar Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="editDeviceForm">
                    <input type="hidden" id="editDeviceId" name="device_id">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editDeviceName" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ubicación</label>
                        <input type="text" class="form-control" id="editDeviceLocation" name="location">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitud</label>
                            <input type="number" class="form-control" id="editDeviceLatitude" name="latitude" step="any">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitud</label>
                            <input type="number" class="form-control" id="editDeviceLongitude" name="longitude" step="any">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Umbral de humedad (%)</label>
                        <input type="number" class="form-control" id="editSoilThreshold" name="soil_threshold" min="5" max="95" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Duración riego automático (segundos)</label>
                        <input type="number" class="form-control" id="editIrrigationDuration" name="irrigation_duration" min="1" max="300" required>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="editAutoIrrigation" name="auto_irrigation">
                        <label class="form-check-label">Riego automático</label>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="updateDevice()">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>
