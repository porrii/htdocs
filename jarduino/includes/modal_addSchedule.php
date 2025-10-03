<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addScheduleModalLabel">Añadir Programación de Riego</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addScheduleForm">
                    <input type="hidden" name="device_id" value="<?php echo $selected_device['device_id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="scheduleStartTime" class="form-label">Hora de Inicio</label>
                        <input type="time" class="form-control" id="scheduleStartTime" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="scheduleDuration" class="form-label">Duración (segundos)</label>
                        <input type="number" class="form-control" id="scheduleDuration" name="duration" min="1" max="300" value="10" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Días de la Semana</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="1" id="day1">
                            <label class="form-check-label" for="day1">Lunes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="2" id="day2">
                            <label class="form-check-label" for="day2">Martes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="3" id="day3">
                            <label class="form-check-label" for="day3">Miércoles</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="4" id="day4">
                            <label class="form-check-label" for="day4">Jueves</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="5" id="day5">
                            <label class="form-check-label" for="day5">Viernes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="6" id="day6">
                            <label class="form-check-label" for="day6">Sábado</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="days[]" value="7" id="day7">
                            <label class="form-check-label" for="day7">Domingo</label>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="scheduleActive" name="active" checked>
                        <label class="form-check-label" for="scheduleActive">Programación Activa</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="addIrrigationSchedule()">Guardar Programación</button>
            </div>
        </div>
    </div>
</div>