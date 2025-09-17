/****************************************************
 * 📌 CONFIGURACIÓN GLOBAL
 ****************************************************/

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
    alert('Funcionalidad de edición en desarrollo. Schedule ID: ' + scheduleId);
}

// Eliminar programación
function deleteSchedule(scheduleId) {
    if (confirm('¿Está seguro de que desea eliminar esta programación?')) {
        fetch('api/delete_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                schedule_id: scheduleId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Programación eliminada correctamente');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }
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
    const deviceId = document.getElementById('deviceId').value;
    const deviceName = document.getElementById('deviceName').value;
    
    fetch('api/add_device.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ deviceId, deviceName })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Dispositivo ' + deviceName + ' añadido correctamente', 'success');
            // Cerrar modal con Bootstrap 5
            const addDeviceModal = document.getElementById('addDeviceModal');
            const modal = bootstrap.Modal.getOrCreateInstance(addDeviceModal);
            modal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => showAlert('Error de conexión: ' + error, 'danger'));
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
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.querySelector('main').prepend(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
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
