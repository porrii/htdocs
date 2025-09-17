/****************************************************
 * 📌 CONFIGURACIÓN GLOBAL
 ****************************************************/

// MQTT
const mqttOptions = {
    hostname: 'localhost', // misma IP que en Arduino / Broker
    port: 9001,
    protocol: 'ws', // WebSocket (usar 'mqtt' si no es WS)
};

// Tiempo límite para considerar un dispositivo offline
const DEVICE_TIMEOUT_MS = 20000; // ⏱️ 20 segundos

// Intervalo de verificación de dispositivos
const CHECK_INTERVAL_MS = 5000; // ⏱️ 5 segundos

// Historial máximo de puntos en el gráfico
const MAX_CHART_POINTS = 20;

// Diccionarios internos
const deviceLastSeen = {};   // Última vez visto cada dispositivo
const irrigationActive = {}; // Estado de riego por dispositivo


/****************************************************
 * 📌 CONEXIÓN MQTT
 ****************************************************/

const client = mqtt.connect(mqttOptions);

client.on('connect', function () {
    console.log('✅ Conectado al broker MQTT');

    // Suscripciones
    client.subscribe('sensores');
    client.subscribe('config/auto');
    client.subscribe('config/threshold');
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
        if (data.id) {
            deviceLastSeen[data.id] = Date.now();
            updateDeviceStatusBasedOnTimeout(data.id);
        }

        // Actualizar UI
        updateUI(topic, message.toString());

        // Guardar datos en la BD
        if (topic === 'sensores') saveSensorData(data);

        // Inicio de riego (manual o automático)
        if (topic === 'riego') {
            handleIrrigationStart(data);
        }

        // Detener riego
        if (topic === 'stop') {
            const deviceId = data.device;
            showAlert(`Riego detenido (Dispositivo ${deviceId})`, 'info');
        }

    } catch (e) {
        console.error('⚠️ Error parsing message:', e);
    }
});


/****************************************************
 * 📌 FUNCIONES DE RIEGO
 ****************************************************/

function handleIrrigationStart(data) {
    const deviceId = data.device;
    const duration = data.duration || 10;
    const mode = data.mode || 'manual'; // Arduino enviará "auto" si corresponde

    fetch('api/start_irrigation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ device: deviceId, duration: parseInt(duration), mode: mode })
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

function startIrrigation(deviceId) {
    if (!deviceId) return showAlert('Selecciona un dispositivo', 'warning');

    const durationInput = document.getElementById('irrigationTime');
    const duration = parseInt(durationInput.value);
    const mode = "manual";

    if (isNaN(duration) || duration <= 0) {
        return showAlert('Introduce una duración válida', 'warning');
    }
    if (!client || !client.connected) {
        return showAlert('MQTT no conectado. No se puede enviar el riego', 'danger');
    }

    const payload = { device: deviceId, duration: duration, mode: mode };
    console.log('➡️ Enviando riego:', payload);

    // Publicar por MQTT
    client.publish('riego', JSON.stringify(payload));

    // Guardar en BD
    fetch('api/start_irrigation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showAlert(`💧 Riego iniciado: ${duration}s (Dispositivo ${deviceId})`, 'success');
        } else {
            showAlert(`Error: ${res.message}`, 'danger');
        }
    })
    .catch(err => showAlert('Error de conexión: ' + err, 'danger'));
}

function stopIrrigation(deviceId) {
    client.publish('stop', JSON.stringify({ device: deviceId }));
    showAlert('⛔ Comando de detención enviado', 'info');
}

function toggleAutoIrrigation(deviceId, enabled) {
    client.publish('config/auto', JSON.stringify({ device: deviceId, enabled: enabled }));
    showAlert('Riego automático ' + (enabled ? 'activado' : 'desactivado'), 'success');
}

function updateThreshold(deviceId, value) {
    document.getElementById('thresholdValue').textContent = value;
    client.publish('config/threshold', JSON.stringify({ device: deviceId, threshold: parseInt(value) }));
}


/****************************************************
 * 📌 MANEJO DE DISPOSITIVOS
 ****************************************************/

function addDevice() {
    const deviceId = document.getElementById('deviceId').value;
    const deviceName = document.getElementById('deviceName').value;
    const description = document.getElementById('deviceDescription').value;
    
    fetch('api/add_device.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ deviceId, deviceName, description })
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

function updateDeviceConfig(deviceId) {
    const soilThreshold = parseInt(document.getElementById(`soil_threshold${deviceId}`).value);
    const autoIrrigation = document.getElementById(`auto_irrigation${deviceId}`).checked;

    console.log('DeviceId:', deviceId, 'soilThreshold:', soilThreshold, 'autoIrrigation:', autoIrrigation);

    // Publicar vía MQTT si existe client
    if (typeof client !== 'undefined') {
        client.publish('config/auto', JSON.stringify({ device: deviceId, enabled: autoIrrigation }));
        client.publish('config/threshold', JSON.stringify({ device: deviceId, threshold: soilThreshold }));
        showAlert(`Configuración enviada al dispositivo ${deviceId}`);
    }

    // Enviar el formulario al PHP
    document.getElementById(`editDeviceForm${deviceId}`).submit();
}

/****************************************************
 * 📌 FUNCIONES DE BASE DE DATOS
 ****************************************************/

function saveSensorData(data) {
    fetch('api/save_sensor_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            device: data.id,
            temperature: data.t,
            air_humidity: data.h,
            soil_moisture: data.soil,
            pump_status: data.pump
        })
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            console.warn('⚠️ No se pudo guardar en BD:', res.message);
        }
    })
    .catch(err => console.error('❌ Error guardando sensor en BD:', err));
}


/****************************************************
 * 📌 UI Y GRÁFICOS
 ****************************************************/

function updateUI(topic, message) {
    try {
        const data = JSON.parse(message);

        if (topic === 'sensores' && data.id) {
            updateDeviceListStatus(data.id, true);
            deviceLastSeen[data.id] = Date.now();

            if (data.id === getSelectedDeviceId()) {
                updateSensorData(data, getSelectedDeviceId());
            }
        }
    } catch (e) {
        console.error('⚠️ Error parsing MQTT message:', e);
    }
}

function updateSensorData(data, selectedDeviceId) {
    if (data.id !== selectedDeviceId) return;

    const temp = data.t ?? 'N/A';
    const humidity = data.h ?? 'N/A';
    const soil = data.soil ?? 'N/A';

    document.querySelector('[data-temperature]').textContent = temp + (temp !== 'N/A' ? '°C' : '');
    document.querySelector('[data-air-humidity]').textContent = humidity + (humidity !== 'N/A' ? '%' : '');
    document.querySelector('[data-soil-moisture]').textContent = soil + (soil !== 'N/A' ? '%' : '');

    const pumpElement = document.querySelector('[data-pump-status]');
    if (data.pump) {
        pumpElement.innerHTML = 'ACTIVA <i class="fas fa-circle pump-active ms-1"></i>';
        pumpElement.closest('.card').classList.replace('bg-secondary', 'bg-warning');
    } else {
        pumpElement.textContent = 'INACTIVA';
        pumpElement.closest('.card').classList.replace('bg-warning', 'bg-secondary');
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
