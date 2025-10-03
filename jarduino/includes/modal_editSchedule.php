<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Editar Programación de Riego</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editScheduleForm">
                    <input type="hidden" name="schedule_id" id="editScheduleId">
                    <input type="hidden" name="device_id" id="editDeviceId">

                    <div class="mb-3">
                        <label for="editScheduleStartTime" class="form-label">Hora de Inicio</label>
                        <input type="time" class="form-control" id="editScheduleStartTime" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="editScheduleDuration" class="form-label">Duración (segundos)</label>
                        <input type="number" class="form-control" id="editScheduleDuration" name="duration" min="1" max="300" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Días de la Semana</label>
                        <div id="editDaysContainer">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="1" id="editDay1">
                                <label class="form-check-label" for="editDay1">Lunes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="2" id="editDay2">
                                <label class="form-check-label" for="editDay2">Martes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="3" id="editDay3">
                                <label class="form-check-label" for="editDay3">Miércoles</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="4" id="editDay4">
                                <label class="form-check-label" for="editDay4">Jueves</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="5" id="editDay5">
                                <label class="form-check-label" for="editDay5">Viernes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="6" id="editDay6">
                                <label class="form-check-label" for="editDay6">Sábado</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="7" id="editDay7">
                                <label class="form-check-label" for="editDay7">Domingo</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="editScheduleActive" name="active">
                        <label class="form-check-label" for="editScheduleActive">Programación Activa</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="editIrrigationSchedule()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
