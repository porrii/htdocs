// ****************************************************
// * 📌 CONFIGURACIÓN GLOBAL
// ****************************************************

// MQTT - Usar la misma configuración que el ESP32
const mqttOptions = {
    hostname: 'localhost', // IP del broker MQTT desde el ESP32
    port: 9001,
    protocol: 'mqtt', // Protocolo MQTT estándar
};

// Tiempo límite para considerar un dispositivo offline
const DEVICE_TIMEOUT_MS = 30000; // ⏱️ 30 segundos

// Intervalo de verificación de dispositivos
const CHECK_INTERVAL_MS = 5000; // ⏱️ 5 segundos

// Historial máximo de puntos en el gráfico
const MAX_CHART_POINTS = 20;

// Diccionarios internos
const deviceLastSeen = {};   // Última vez visto cada dispositivo
const deviceConfigs = {};    // Configuraciones de cada dispositivo

/****************************************************
 * 📌 CONEXIÓN MQTT
 ****************************************************/

const client = mqtt.connect(mqttOptions);

client.on('connect', function () {
    console.log('✅ Conectado al broker MQTT');

    // Suscripciones a los topics que usa el ESP32
    client.subscribe('sensores');
    client.subscribe('config');
    client.subscribe('riego');
    client.subscribe('stop');
});

client.on('reconnect', () => console.log('🔄 Reconectando al broker MQTT...'));
client.on('error', error => {
    console.error('❌ Error MQTT:', error);
    showAlert('Error de conexión MQTT', 'danger');
});

/****************************************************
 * 📌 MANEJO DE MENSAJES MQTT
 ****************************************************/

client.on('message', function (topic, message) {
    console.log('📩 Mensaje recibido:', topic, message.toString());

    try {
        const data = JSON.parse(message.toString());

        // Guardar timestamp de último mensaje
        if (data.device) {
            deviceLastSeen[data.device] = Date.now();
            updateDeviceStatusBasedOnTimeout(data.device);
        }

        // Procesar según el topic
        switch (topic) {
            case 'sensores':
                handleSensorData(data);
                break;
            case 'config':
                handleConfigData(data);
                break;
            case 'riego':
                handleIrrigationMessage(data);
                break;
            case 'stop':
                handleStopMessage(data);
                break;
        }

    } catch (e) {
        console.error('⚠️ Error parsing message:', e);
    }
});

function handleSensorData(data) {
    if (!data.device) return;
    
    // Guardar datos en la BD
    saveSensorData({
        device: data.device,
        temperature: data.t,
        air_humidity: data.h,
        soil_moisture: data.soil,
        pump_status: data.pump ? 1 : 0
    });

    // Actualizar UI
    updateUI(data);
}

function handleConfigData(data) {
    if (!data.device) return;
    
    // Guardar configuración localmente
    deviceConfigs[data.device] = {
        auto: data.auto,
        threshold: data.threshold,
        duration: data.duration
    };
    
    // Guardar en BD
    saveConfigData({
        device: data.device,
        auto_irrigation: data.auto ? 1 : 0,
        threshold: data.threshold,
        duration: data.duration
    });
    
    // Actualizar UI si este dispositivo está seleccionado
    if (data.device === getSelectedDeviceId()) {
        updateConfigUI(data);
    }
}

function handleIrrigationMessage(data) {
    if (!data.device) return;
    
    const deviceId = data.device;
    const duration = data.duration || 10;
    const mode = data.mode || 'auto';

    // Guardar en BD
    fetch('api/start_irrigation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ device_id: deviceId, duration: parseInt(duration), mode: mode })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showAlert(`💧 Riego ${mode} iniciado: ${duration}s (Dispositivo ${deviceId})`, 'success');
        } else {
            showAlert(`Error: ${res.message}`, 'danger');
        }
    });
}

function handleStopMessage(data) {
    if (!data.device) return;
    
    const deviceId = data.device;
    showAlert(`Riego detenido (Dispositivo ${deviceId})`, 'info');
}

/****************************************************
 * 📌 FUNCIONES DE RIEGO
 ****************************************************/

function startIrrigation(deviceId) {
    if (!deviceId) return showAlert('Selecciona un dispositivo', 'warning');

    const durationInput = document.getElementById('irrigationTime');
    const duration = parseInt(durationInput.value);

    if (isNaN(duration) || duration <= 0) {
        return showAlert('Introduce una duración válida', 'warning');
    }
    if (!client || !client.connected) {
        return showAlert('MQTT no conectado. No se puede enviar el riego', 'danger');
    }

    const payload = { device: deviceId, duration: duration, mode: "manual" };
    console.log('➡️ Enviando riego:', payload);

    // Publicar por MQTT
    try{
        client.publish('riego', JSON.stringify(payload));
    } catch (e) {
        showAlert(`Error: ${e}`, 'danger');
    }

}

function stopIrrigation(deviceId) {
    client.publish('stop', JSON.stringify({ device: deviceId }));
    showAlert('⛔ Comando de detención enviado', 'info');
}

function saveIrrigationConfig(deviceId) {
    // Leer los valores directamente del DOM al hacer clic
    const autoIrrigationEl = document.getElementById('autoIrrigation');
    const soilThresholdEl = document.getElementById('soilThreshold');
    const irrigationDurationEl = document.getElementById('irrigationDuration');

    if (!autoIrrigationEl || !soilThresholdEl || !irrigationDurationEl) {
        console.error('❌ No se pudieron encontrar los elementos de configuración');
        return;
    }

    const data = {
        device: deviceId,
        auto: autoIrrigationEl.checked,
        threshold: parseInt(soilThresholdEl.value),
        duration: parseInt(irrigationDurationEl.value)
    };

    // Usar la función existente
    saveConfigData(data);

    // Feedback visual opcional
    client.publish('config', JSON.stringify(data));
    showAlert('Configuración enviada correctamente.');
}

/****************************************************
 * 📌 FUNCIONES DE PROGRAMACION DE RIEGO
 ****************************************************/
// Añadir programación de riego
function addIrrigationSchedule() {
    const form = document.getElementById('addScheduleForm');
    const formData = new FormData(form);
    
    // Convertir checkboxes de días a string separado por comas
    const days = Array.from(formData.getAll('days[]')).join(',');
    
    const scheduleData = {
        device_id: formData.get('device_id'),
        start_time: formData.get('start_time'),
        duration: formData.get('duration'),
        days_of_week: days,
        active: document.getElementById('scheduleActive').checked ? 1 : 0
    };
    
    fetch('api/add_schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(scheduleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Programación Añadida',
                text: 'La programación se ha añadido correctamente.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Cerrar modal y recargar página
                const modal = bootstrap.Modal.getInstance(document.getElementById('addScheduleModal'));
                modal.hide();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo añadir la programación.'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al añadir la programación.'
        });
    });
}

// Editar programación (placeholder)
function editSchedule(scheduleId) {
    // Pedir los datos del schedule al servidor
    fetch('api/get_schedule.php?id=' + scheduleId)
        .then(response => response.json())
        .then(schedule => {
            if (!schedule.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: schedule.message || 'No se pudo cargar la programación.'
                });
                return;
            }

            const data = schedule.data; // objeto con los campos del schedule

            // Rellenar modal
            document.getElementById('editScheduleId').value = data.schedule_id;
            document.getElementById('editDeviceId').value = data.device_id;
            document.getElementById('editScheduleStartTime').value = data.start_time;
            document.getElementById('editScheduleDuration').value = data.duration;

            // Resetear checkboxes de días
            for (let i = 1; i <= 7; i++) {
                document.getElementById('editDay' + i).checked = false;
            }

            // Marcar días recibidos
            if (data.days_of_week) {
                const days = data.days_of_week.split(',');
                days.forEach(d => {
                    const checkbox = document.getElementById('editDay' + d);
                    if (checkbox) checkbox.checked = true;
                });
            }

            // Estado activo
            document.getElementById('editScheduleActive').checked = (data.active == 1);

            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error al cargar schedule:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar la programación.'
            });
        });
}

function editIrrigationSchedule() {
    const form = document.getElementById('editScheduleForm');
    const formData = new FormData(form);

    // Convertir días seleccionados
    const days = Array.from(formData.getAll('days[]')).join(',');

    const scheduleData = {
        schedule_id: formData.get('schedule_id'),
        device_id: formData.get('device_id'),
        start_time: formData.get('start_time'),
        duration: formData.get('duration'),
        days_of_week: days,
        active: document.getElementById('editScheduleActive').checked ? 1 : 0
    };

    fetch('api/update_schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(scheduleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Programación Actualizada',
                text: 'La programación se ha editado correctamente.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editScheduleModal'));
                modal.hide();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo actualizar la programación.'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al actualizar la programación.'
        });
    });
}

// Eliminar programación
function deleteSchedule(scheduleId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará la programación seleccionada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ schedule_id: scheduleId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Programación eliminada',
                        text: 'La programación se ha eliminado correctamente.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addScheduleModal'));
                        if (modal) modal.hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar la programación.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al intentar eliminar la programación.'
                });
            });
        }
    });
}

// Actualizar estado de programación
function updateScheduleStatus(scheduleId, active) {
    fetch('api/update_schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            schedule_id: scheduleId,
            active: active
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error al actualizar la programación');
            // Revertir el cambio
            const switchEl = document.querySelector(`.schedule-status[data-schedule-id="${scheduleId}"]`);
            switchEl.checked = !switchEl.checked;
        }
    })
    .catch(error => {
        alert('Error de conexión: ' + error);
        // Revertir el cambio
        const switchEl = document.querySelector(`.schedule-status[data-schedule-id="${scheduleId}"]`);
        switchEl.checked = !switchEl.checked;
    });
}

/****************************************************
 * 📌 FUNCIONES DE BASE DE DATOS
 ****************************************************/

function saveSensorData(data) {
    fetch('api/save_sensor_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            console.warn('⚠️ No se pudo guardar en BD:', res.message);
        }
    })
    .catch(err => console.error('❌ Error guardando sensor en BD:', err));
}

function saveConfigData(data) {
    fetch('api/save_config.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            console.warn('⚠️ No se pudo guardar configuración en BD:', res.message);
        }
    })
    .catch(err => console.error('❌ Error guardando configuración en BD:', err));
}

/****************************************************
 * 📌 UI Y GRÁFICOS
 ****************************************************/

function updateUI(data) {
    if (!data.device) return;
    
    updateDeviceListStatus(data.device, true);
    deviceLastSeen[data.device] = Date.now();

    if (data.device === getSelectedDeviceId()) {
        updateSensorData(data);
    }
}

function updateConfigUI(data) {
    if (!data.device || data.device !== getSelectedDeviceId()) return;
    
    // Actualizar controles de configuración
    const autoCheckbox = document.getElementById('autoIrrigation');
    const thresholdSlider = document.getElementById('soilThreshold');
    const thresholdValue = document.getElementById('thresholdValue');
    const durationInput = document.getElementById('irrigationDuration');
    
    if (autoCheckbox) autoCheckbox.checked = data.auto;
    if (thresholdSlider) thresholdSlider.value = data.threshold;
    if (thresholdValue) thresholdValue.textContent = data.threshold;
    if (durationInput) durationInput.value = data.duration;
}

function updateSensorData(data) {
    const temp = data.t !== undefined ? data.t : 'N/A';
    const humidity = data.h !== undefined ? data.h : 'N/A';
    const soil = data.soil !== undefined ? data.soil : 'N/A';

    document.querySelector('[data-temperature]').textContent = temp !== 'N/A' ? temp.toFixed(1) + '°C' : 'N/A';
    document.querySelector('[data-air-humidity]').textContent = humidity !== 'N/A' ? humidity.toFixed(1) + '%' : 'N/A';
    document.querySelector('[data-soil-moisture]').textContent = soil !== 'N/A' ? soil + '%' : 'N/A';

    const pumpElement = document.querySelector('[data-pump-status]');
    if (pumpElement) {
        if (data.pump) {
            pumpElement.innerHTML = 'ACTIVA <i class="fas fa-circle pump-active ms-1"></i>';
            pumpElement.closest('.card').classList.replace('bg-secondary', 'bg-warning');
        } else {
            pumpElement.textContent = 'INACTIVA';
            pumpElement.closest('.card').classList.replace('bg-warning', 'bg-secondary');
        }
    }

    updateSensorChart(
        temp !== 'N/A' ? temp : null,
        humidity !== 'N/A' ? humidity : null,
        soil !== 'N/A' ? soil : null
    );
}

function updateSensorChart(temp, hum, soil) {
    if (typeof sensorChart === 'undefined') {
        console.warn("⚠️ sensorChart no está definido aún.");
        return;
    }

    const now = new Date().toLocaleTimeString();

    sensorChart.data.labels.push(now);
    sensorChart.data.datasets[0].data.push(temp);
    sensorChart.data.datasets[1].data.push(hum);
    sensorChart.data.datasets[2].data.push(soil);

    if (sensorChart.data.labels.length > MAX_CHART_POINTS) {
        sensorChart.data.labels.shift();
        sensorChart.data.datasets.forEach(ds => ds.data.shift());
    }

    sensorChart.update();
}

/****************************************************
 * 📌 MANEJO DE DISPOSITIVOS
 ****************************************************/

function addDevice() {
    const deviceId = document.getElementById('deviceId').value.trim();
    const userId = document.getElementById('userId').value.trim();
    const deviceName = document.getElementById('deviceName').value.trim();
    // const description = document.getElementById('deviceDescription').value.trim();
    const location = document.getElementById('deviceLocation').value.trim();
    const latitude = document.getElementById('deviceLatitude').value ? parseFloat(document.getElementById('deviceLatitude').value) : null;
    const longitude = document.getElementById('deviceLongitude').value ? parseFloat(document.getElementById('deviceLongitude').value) : null;

    if (!deviceId || !deviceName) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos obligatorios',
            text: 'Por favor, complete los campos obligatorios.'
        });
        return;
    }

    fetch('api/add_device.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            device_id: deviceId,
            user_id: userId,
            name: deviceName,      
            location: location,
            latitude: latitude,
            longitude: longitude
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Dispositivo añadido',
                text: `El dispositivo "${deviceName}" se ha añadido correctamente.`,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addDeviceModal'));
                modal.hide();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo añadir el dispositivo.'
            });
            console.error('ERRORRRR:', data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: error
        });
    });
}

// Editar dispositivo
function editDevice(deviceId) {
    fetch('api/get_device.php?id=' + encodeURIComponent(deviceId))
        .then(response => response.json())
        .then(device => {
            if (!device.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: device.message || 'No se pudo cargar el dispositivo.'
                });
                return;
            }

            const data = device.data;

            // Rellenar modal
            document.getElementById('editDeviceId').value = data.device_id ?? '';
            document.getElementById('editDeviceName').value = data.name ?? '';
            document.getElementById('editDeviceLocation').value = data.location ?? '';
            document.getElementById('editDeviceLatitude').value = data.latitude !== null ? parseFloat(data.latitude) : '';
            document.getElementById('editDeviceLongitude').value = data.longitude !== null ? parseFloat(data.longitude) : '';
            document.getElementById('editSoilThreshold').value = data.threshold !== null ? parseFloat(data.threshold) : '';
            document.getElementById('editIrrigationDuration').value = data.duration !== null ? parseInt(data.duration) : '';
            document.getElementById('editAutoIrrigation').checked = data.auto_irrigation == 1;

            // Abrir modal y fijar foco en el primer input
            const modalEl = document.getElementById('editDeviceModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            modalEl.querySelector('#editDeviceName').focus(); // evita foco en btn-close y la advertencia aria-hidden
        })
        .catch(error => {
            console.error('Error al cargar dispositivo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar el dispositivo.'
            });
        });
}

function updateDevice() {
    const deviceId = document.getElementById('editDeviceId').value;
    const name = document.getElementById('editDeviceName').value.trim();
    const location = document.getElementById('editDeviceLocation').value.trim();
    const latitude = document.getElementById('editDeviceLatitude').value ? parseFloat(document.getElementById('editDeviceLatitude').value) : null;
    const longitude = document.getElementById('editDeviceLongitude').value ? parseFloat(document.getElementById('editDeviceLongitude').value) : null;
    const soil_threshold = document.getElementById('editSoilThreshold').value ? parseFloat(document.getElementById('editSoilThreshold').value) : null;
    const irrigation_duration = document.getElementById('editIrrigationDuration').value ? parseInt(document.getElementById('editIrrigationDuration').value) : null;
    const auto_irrigation = document.getElementById('editAutoIrrigation').checked ? 1 : 0;

    fetch('api/update_device.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            device_id: deviceId,
            name,
            location,
            latitude,
            longitude,
            auto_irrigation,
            soil_threshold,
            irrigation_duration
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Dispositivo actualizado',
                text: `El dispositivo "${name}" se ha actualizado correctamente.`,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editDeviceModal'));
                modal.hide();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo actualizar el dispositivo.'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: error
        });
    });
}

function deleteDevice(deviceId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará el dispositivo seleccionado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_device.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ device_id: deviceId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Dispositivo eliminado',
                        text: 'El dispositivo se ha eliminado correctamente.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addDeviceModal'));
                        if (modal) modal.hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar el dispositivo.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al intentar eliminar el dispositivo.'
                });
            });
        }
    });
}

// Verificar timeout de dispositivos
function checkAllDevicesTimeout() {
    const currentTime = Date.now();

    Object.keys(deviceLastSeen).forEach(deviceId => {
        const timeSinceLastSeen = currentTime - deviceLastSeen[deviceId];
        if (timeSinceLastSeen > DEVICE_TIMEOUT_MS) {
            setDeviceOffline(deviceId);
        } else {
            updateDeviceListStatus(deviceId, true);
        }
    });
}

function setDeviceOffline(deviceId) {
    updateDeviceListStatus(deviceId, false);

    const selectedDeviceId = getSelectedDeviceId();
    if (deviceId === selectedDeviceId) {
        const statusElement = document.querySelector('[data-device-status]');
        if (statusElement) {
            statusElement.innerHTML = '<span class="online-status offline"></span> Desconectado';
            statusElement.classList.remove('bg-success');
            statusElement.classList.add('bg-danger');
        }

        // Poner grises los sensores
        const sensorElements = document.querySelectorAll('[data-temperature], [data-air-humidity], [data-soil-moisture]');
        sensorElements.forEach(el => {
            if (el.textContent !== 'N/A') {
                el.style.opacity = '0.6';
                el.title = 'Datos no actualizados recientemente';
            }
        });
    }
}

function updateDeviceStatusBasedOnTimeout(deviceId) {
    updateDeviceListStatus(deviceId, true);

    if (deviceId === getSelectedDeviceId()) {
        const statusElement = document.querySelector('[data-device-status]');
        if (statusElement) {
            statusElement.innerHTML = '<span class="online-status online"></span> En línea';
            statusElement.classList.remove('bg-danger');
            statusElement.classList.add('bg-success');

            const sensorElements = document.querySelectorAll('[data-temperature], [data-air-humidity], [data-soil-moisture]');
            sensorElements.forEach(el => {
                el.style.opacity = '1';
                el.title = '';
            });
        }
    }
}

/****************************************************
 * 📌 UTILIDADES
 ****************************************************/

function updateDeviceListStatus(deviceId, isOnline) {
    const statusElement = document.getElementById(`status-${deviceId}`);
    const deviceCard = document.getElementById(`device-${deviceId}`);

    if (statusElement && deviceCard) {
        if (isOnline) {
            statusElement.innerHTML = '<span class="online-status online"></span> En línea';
            statusElement.classList.replace('text-danger', 'text-success');
            deviceCard.classList.replace('device-offline', 'device-online');
        } else {
            statusElement.innerHTML = '<span class="online-status offline"></span> Desconectado';
            statusElement.classList.replace('text-success', 'text-danger');
            deviceCard.classList.replace('device-online', 'device-offline');
        }
    }
}

function getSelectedDeviceId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('device');
}

function showAlert(message, type) {
    let icon = 'info';
    let title = 'Información';

    switch (type) {
        case 'success':
            icon = 'success';
            title = 'Éxito';
            break;
        case 'danger':
        case 'error':
            icon = 'error';
            title = 'Error';
            break;
        case 'warning':
            icon = 'warning';
            title = 'Atención';
            break;
    }

    Swal.fire({
        icon: icon,
        title: title,
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

function exportData(type) {
    const deviceEl = document.getElementById('device');
    const dateFromEl = document.getElementById('date_from');
    const dateToEl = document.getElementById('date_to');

    const device = deviceEl ? deviceEl.value : '';
    const date_from = dateFromEl ? dateFromEl.value : '';
    const date_to = dateToEl ? dateToEl.value : '';

    let url = `api/export_data.php?type=${type}`;
    if (device) url += `&device=${device}`;
    if (date_from) url += `&date_from=${date_from}`;
    if (date_to) url += `&date_to=${date_to}`;

    window.location.href = url;
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

/****************************************************
 * 📌 INICIALIZACIÓN
 ****************************************************/

// Verificar dispositivos cada cierto tiempo
setInterval(checkAllDevicesTimeout, CHECK_INTERVAL_MS);

// Verificación al cargar la página
window.addEventListener('load', function() {
    setTimeout(checkAllDevicesTimeout, 1000);

    // Detectar cambios de URL (navegación interna)
    let lastUrl = location.href;
    new MutationObserver(() => {
        const currentUrl = location.href;
        if (currentUrl !== lastUrl) {
            lastUrl = currentUrl;
            setTimeout(checkAllDevicesTimeout, 100);
        }
    }).observe(document, {subtree: true, childList: true});
});
