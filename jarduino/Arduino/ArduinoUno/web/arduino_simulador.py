#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import time
import random
import json
import asyncio
import paho.mqtt.client as mqtt

# ---------------- CONFIG ----------------
DEVICE_ID = "ARD1"

MQTT_SERVER = "localhost"
MQTT_PORT = 1883

TOPIC_SENSORES = "sensores"
TOPIC_ESTADO = f"estado/{DEVICE_ID}"
TOPIC_RIEGO = "riego"
TOPIC_STOP = "stop"
TOPIC_CONFIG_AUTO = "config/auto"
TOPIC_CONFIG_THRESHOLD = "config/threshold"

SENSORS_INTERVAL = 10       # segundos
STATUS_INTERVAL = 30        # segundos

TEMP_CHANGE_THRESHOLD = 0.5
HUM_CHANGE_THRESHOLD = 2.0
SOIL_CHANGE_THRESHOLD = 2

MANUAL_DURATION_S = 5       # duración por defecto

# ---------------- ESTADO ----------------
class Config:
    autoIrrigation = False
    soilThreshold = 40
    manualDurationS = MANUAL_DURATION_S

cfg = Config()
pumpActive = False
pumpStopAt = 0

lastTemp = None
lastHum = None
lastSoilPct = None

prevTemp = None
prevHum = None
prevSoilPct = None

# ---------------- MQTT ----------------
client = mqtt.Client()

# ---------------- FUNCIONES MQTT ----------------
def on_connect(client, userdata, flags, rc):
    print(f"[MQTT] Conectado con código {rc}")
    client.subscribe(TOPIC_RIEGO)
    client.subscribe(TOPIC_STOP)
    client.subscribe(TOPIC_CONFIG_AUTO)
    client.subscribe(TOPIC_CONFIG_THRESHOLD)
    publish_estado_online()

def on_message(client, userdata, msg):
    global cfg
    payload = msg.payload.decode()
    print(f"[MQTT] Mensaje recibido en {msg.topic}: {payload}")
    try:
        doc = json.loads(payload)
    except:
        print(f"[ERROR] JSON inválido: {payload}")
        return

    # Filtrar por device
    if "device" in doc and doc["device"] != DEVICE_ID:
        return

    topic = msg.topic
    if topic == TOPIC_RIEGO:
        dur = doc.get("duration", cfg.manualDurationS)
        start_pump(dur, auto=False)
    elif topic == TOPIC_STOP:
        stop_pump()
    elif topic == TOPIC_CONFIG_AUTO:
        if "enabled" in doc:
            cfg.autoIrrigation = bool(doc["enabled"])
            print(f"[CONFIG] Auto-riego: {'ON' if cfg.autoIrrigation else 'OFF'}")
    elif topic == TOPIC_CONFIG_THRESHOLD:
        if "threshold" in doc:
            cfg.soilThreshold = max(0, min(100, int(doc["threshold"])))
            print(f"[CONFIG] Umbral humedad: {cfg.soilThreshold}")

def init_mqtt():
    client.on_connect = on_connect
    client.on_message = on_message
    client.connect(MQTT_SERVER, MQTT_PORT, 60)
    client.loop_start()

# ---------------- SENSORES ----------------
def read_sensors():
    global lastTemp, lastHum, lastSoilPct
    temp = round((lastTemp if lastTemp is not None else random.uniform(20,25)) + random.uniform(-0.5,0.5), 1)
    hum = round((lastHum if lastHum is not None else random.uniform(40,60)) + random.uniform(-1,1), 1)
    soil = max(0, min(100, (lastSoilPct if lastSoilPct is not None else random.randint(30,70)) + random.randint(-2,2)))
    return temp, hum, soil

def publish_sensores():
    doc = {"id": DEVICE_ID, "pump": pumpActive}
    if lastTemp is not None: doc["t"] = lastTemp
    if lastHum is not None: doc["h"] = lastHum
    doc["soil"] = lastSoilPct
    client.publish(TOPIC_SENSORES, json.dumps(doc))
    print(f"[MQTT] Sensores: {doc}")

def publish_estado_online():
    doc = {"id": DEVICE_ID, "online": True}
    client.publish(TOPIC_ESTADO, json.dumps(doc))
    print(f"[MQTT] Estado online publicado")

# ---------------- RIEGO ----------------
def start_pump(duration, auto=False):
    global pumpActive, pumpStopAt
    if duration <= 0: return
    if not pumpActive:
        pumpActive = True
        pumpStopAt = time.time() + duration
        print(f"[RIEGO] Bomba activada por {duration}s {'(AUTO)' if auto else '(MANUAL)'}")
        # Publicar solo si es auto-riego
        if auto:
            doc = {"device": DEVICE_ID, "duration": duration, "mode": "auto"}
            client.publish(TOPIC_RIEGO, json.dumps(doc))

def stop_pump():
    global pumpActive, pumpStopAt
    if pumpActive:
        pumpActive = False
        pumpStopAt = 0
        print("[RIEGO] Bomba detenida")

# ---------------- LOOP ASÍNCRONO ----------------
async def loop_simulator():
    global lastTemp, lastHum, lastSoilPct, prevTemp, prevHum, prevSoilPct
    last_sensors_time = time.time()
    last_status_time = time.time()

    while True:
        now = time.time()

        # Leer sensores
        if now - last_sensors_time >= SENSORS_INTERVAL:
            last_sensors_time = now
            temp, hum, soil = read_sensors()
            publish_needed = False

            if prevTemp is None or abs(temp - prevTemp) >= TEMP_CHANGE_THRESHOLD:
                prevTemp = temp
                lastTemp = temp
                publish_needed = True

            if prevHum is None or abs(hum - prevHum) >= HUM_CHANGE_THRESHOLD:
                prevHum = hum
                lastHum = hum
                publish_needed = True

            if prevSoilPct is None or abs(soil - prevSoilPct) >= SOIL_CHANGE_THRESHOLD:
                prevSoilPct = soil
                lastSoilPct = soil
                publish_needed = True

            if publish_needed:
                publish_sensores()

            # Auto-riego
            if cfg.autoIrrigation and not pumpActive and lastSoilPct < cfg.soilThreshold:
                print("[AUTO-RIEGO] Humedad baja, activando bomba")
                start_pump(cfg.manualDurationS, auto=True)

        # Estado online
        if now - last_status_time >= STATUS_INTERVAL:
            last_status_time = now
            publish_estado_online()

        # Apagar bomba automáticamente
        if pumpActive and pumpStopAt != 0 and now >= pumpStopAt:
            stop_pump()

        await asyncio.sleep(0.1)

# ---------------- MAIN ----------------
if __name__ == "__main__":
    print("[SIMULADOR] Iniciando Arduino Simulador")
    init_mqtt()
    asyncio.run(loop_simulator())
