import paho.mqtt.client as mqtt
import json
import time
import random
import threading

# Configuración MQTT (debe coincidir con tu ESP32 real)
MQTT_BROKER = "localhost"
MQTT_PORT = 1883
DEVICE_ID = "ARD2"  # Cambia esto si necesitas simular múltiples dispositivos

# Estado del dispositivo
state = {
    "pump_active": False,
    "pump_start_time": 0,
    "pump_duration": 0,
    "auto_irrigation": True,
    "soil_threshold": 50,
    "irrigation_duration": 10,
    "temperature": 25.0,
    "humidity": 45.0,
    "soil_moisture": 60,
    "online": True
}

# Callback cuando se conecta al broker
def on_connect(client, userdata, flags, rc):
    print(f"✅ Conectado al broker MQTT con código: {rc}")
    # Suscribirse a los topics
    client.subscribe("config")
    client.subscribe("riego")
    client.subscribe("stop")
    print("📡 Suscrito a topics: config, riego, stop")

# Callback cuando recibe un mensaje
def on_message(client, userdata, msg):
    try:
        payload = msg.payload.decode()
        print(f"📩 Mensaje recibido [{msg.topic}]: {payload}")
        
        data = json.loads(payload)
        
        # Verificar si el mensaje es para este dispositivo
        if "device" in data and data["device"] != DEVICE_ID:
            print(f"⚠️ Mensaje ignorado (para otro dispositivo: {data['device']})")
            return
            
        if msg.topic == "config":
            handle_config(data)
        elif msg.topic == "riego":
            handle_irrigation(data)
        elif msg.topic == "stop":
            handle_stop(data)
            
    except Exception as e:
        print(f"❌ Error procesando mensaje: {e}")

# Manejar mensajes de configuración
def handle_config(data):
    changes = []
    
    if "auto" in data:
        state["auto_irrigation"] = data["auto"]
        changes.append(f"Riego automático: {'Activado' if data['auto'] else 'Desactivado'}")
    
    if "threshold" in data:
        state["soil_threshold"] = data["threshold"]
        changes.append(f"Umbral: {data['threshold']}%")
    
    if "duration" in data:
        state["irrigation_duration"] = data["duration"]
        changes.append(f"Duración: {data['duration']}s")
    
    if changes:
        print(f"⚙️ Configuración actualizada: {', '.join(changes)}")
    else:
        print("ℹ️ Mensaje de configuración sin cambios")

# Manejar mensajes de riego
def handle_irrigation(data):
    if "duration" in data:
        duration = data["duration"]
        mode = data.get("mode", "manual")
        
        state["pump_active"] = True
        state["pump_start_time"] = time.time()
        state["pump_duration"] = duration
        
        print(f"💧 Riego iniciado ({mode}): {duration} segundos")
        
        # Programar apagado automático
        threading.Timer(duration, stop_pump).start()
    else:
        print("⚠️ Mensaje de riego sin duración")

# Manejar mensajes de stop
def handle_stop(data):
    if state["pump_active"]:
        state["pump_active"] = False
        print("⛔ Riego detenido manualmente")
    else:
        print("ℹ️ Bomba ya estaba detenida")

# Detener la bomba
def stop_pump():
    if state["pump_active"]:
        state["pump_active"] = False
        print("⏹️ Riego finalizado (tiempo completado)")

# Simular lecturas de sensores
def simulate_sensors():
    # Variaciones realistas en las lecturas
    state["temperature"] += random.uniform(-0.5, 0.5)
    state["temperature"] = max(15, min(35, state["temperature"]))  # Limitar entre 15-35°C
    
    state["humidity"] += random.uniform(-2, 2)
    state["humidity"] = max(30, min(80, state["humidity"]))  # Limitar entre 30-80%
    
    # La humedad del suelo disminuye más rápido cuando la bomba está activa
    if state["pump_active"]:
        state["soil_moisture"] -= random.uniform(0.5, 1.5)
    else:
        state["soil_moisture"] -= random.uniform(0.1, 0.3)
    
    state["soil_moisture"] = max(5, min(95, state["soil_moisture"]))  # Limitar entre 5-95%
    
    # Riego automático si está activado
    if (state["auto_irrigation"] and 
        not state["pump_active"] and 
        state["soil_moisture"] < state["soil_threshold"]):
        
        state["pump_active"] = True
        state["pump_start_time"] = time.time()
        state["pump_duration"] = state["irrigation_duration"]
        
        print(f"🤖 Riego automático iniciado: {state['irrigation_duration']}s (Humedad: {state['soil_moisture']:.1f}% < {state['soil_threshold']}%)")
        
        # Publicar mensaje de riego automático
        irrigation_msg = {
            "device": DEVICE_ID,
            "duration": state["irrigation_duration"],
            "mode": "auto"
        }
        client.publish("riego", json.dumps(irrigation_msg))
        
        # Programar apagado automático
        threading.Timer(state["irrigation_duration"], stop_pump).start()

# Publicar datos de sensores
def publish_sensor_data():
    simulate_sensors()
    
    sensor_data = {
        "device": DEVICE_ID,
        "pump": state["pump_active"],
        "t": round(state["temperature"], 1),
        "h": round(state["humidity"], 1),
        "soil": round(state["soil_moisture"]),
        "online": state["online"]
    }
    
    client.publish("sensores", json.dumps(sensor_data))
    #print(f"📊 Datos publicados: {json.dumps(sensor_data)}")

# Publicar configuración
def publish_config():
    config_data = {
        "device": DEVICE_ID,
        "auto": state["auto_irrigation"],
        "threshold": state["soil_threshold"],
        "duration": state["irrigation_duration"]
    }
    
    client.publish("config", json.dumps(config_data))
    print(f"⚙️ Configuración publicada: {json.dumps(config_data)}")

# Función para interfaz de usuario
def show_help():
    print("\n" + "="*50)
    print("🤖 SIMULADOR ESP32 - SMARTGARDEN")
    print("="*50)
    print("Comandos disponibles:")
    print("  help          - Mostrar esta ayuda")
    print("  status        - Mostrar estado actual")
    print("  config        - Publicar configuración actual")
    print("  manual [seg]  - Iniciar riego manual (ej: manual 15)")
    print("  stop          - Detener riego manual")
    print("  auto [on/off] - Activar/desactivar riego automático")
    print("  threshold [%] - Cambiar umbral (ej: threshold 40)")
    print("  duration [s]  - Cambiar duración (ej: duration 20)")
    print("  exit          - Salir del simulador")
    print("="*50)

def show_status():
    print("\n" + "="*50)
    print("📊 ESTADO ACTUAL DEL DISPOSITIVO")
    print("="*50)
    print(f"Dispositivo:      {DEVICE_ID}")
    print(f"Bomba:            {'🔵 ACTIVA' if state['pump_active'] else '⚪ INACTIVA'}")
    if state["pump_active"]:
        elapsed = time.time() - state["pump_start_time"]
        remaining = max(0, state["pump_duration"] - elapsed)
        print(f"Tiempo restante:  {remaining:.1f}s de {state['pump_duration']}s")
    print(f"Riego automático: {'✅ ACTIVADO' if state['auto_irrigation'] else '❌ DESACTIVADO'}")
    print(f"Umbral:           {state['soil_threshold']}%")
    print(f"Duración:         {state['irrigation_duration']}s")
    print(f"Temperatura:      {state['temperature']:.1f}°C")
    print(f"Humedad aire:     {state['humidity']:.1f}%")
    print(f"Humedad suelo:    {state['soil_moisture']:.1f}%")
    print("="*50)

# Configurar cliente MQTT
client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

# Conectar al broker
try:
    client.connect(MQTT_BROKER, MQTT_PORT, 60)
    print(f"🔗 Conectando a {MQTT_BROKER}:{MQTT_PORT}...")
except Exception as e:
    print(f"❌ Error conectando al broker: {e}")
    exit(1)

# Iniciar loop MQTT en segundo plano
client.loop_start()

# Publicar configuración inicial al conectar
time.sleep(1)  # Esperar a que se establezca la conexión
publish_config()

# Programar publicación periódica de datos de sensores
def sensor_loop():
    while True:
        try:
            publish_sensor_data()
            time.sleep(5)  # Publicar cada 5 segundos (como el ESP32 real)
        except Exception as e:
            print(f"❌ Error en loop de sensores: {e}")

# Iniciar loop de sensores en segundo plano
sensor_thread = threading.Thread(target=sensor_loop, daemon=True)
sensor_thread.start()

# Interfaz de usuario principal
print("\nSimulador ESP32 iniciado. Escribe 'help' para ver comandos disponibles.")

try:
    while True:
        command = input("\n> ").strip().lower()
        
        if command == "help":
            show_help()
        
        elif command == "status":
            show_status()
        
        elif command == "config":
            publish_config()
        
        elif command.startswith("manual"):
            parts = command.split()
            if len(parts) == 2 and parts[1].isdigit():
                duration = int(parts[1])
                irrigation_msg = {
                    "device": DEVICE_ID,
                    "duration": duration,
                    "mode": "manual"
                }
                client.publish("riego", json.dumps(irrigation_msg))
                print(f"💧 Riego manual iniciado: {duration} segundos")
            else:
                print("❌ Uso: manual [segundos]")
        
        elif command == "stop":
            stop_msg = {"device": DEVICE_ID}
            client.publish("stop", json.dumps(stop_msg))
            print("⛔ Comando de detención enviado")
        
        elif command.startswith("auto"):
            parts = command.split()
            if len(parts) == 2:
                if parts[1] in ["on", "true", "1"]:
                    state["auto_irrigation"] = True
                    print("✅ Riego automático activado")
                elif parts[1] in ["off", "false", "0"]:
                    state["auto_irrigation"] = False
                    print("❌ Riego automático desactivado")
                publish_config()
            else:
                print("❌ Uso: auto [on/off]")
        
        elif command.startswith("threshold"):
            parts = command.split()
            if len(parts) == 2 and parts[1].isdigit():
                threshold = int(parts[1])
                if 5 <= threshold <= 95:
                    state["soil_threshold"] = threshold
                    print(f"📊 Umbral cambiado a: {threshold}%")
                    publish_config()
                else:
                    print("❌ El umbral debe estar entre 5 y 95")
            else:
                print("❌ Uso: threshold [porcentaje]")
        
        elif command.startswith("duration"):
            parts = command.split()
            if len(parts) == 2 and parts[1].isdigit():
                duration = int(parts[1])
                if 1 <= duration <= 300:
                    state["irrigation_duration"] = duration
                    print(f"⏱️ Duración cambiada a: {duration} segundos")
                    publish_config()
                else:
                    print("❌ La duración debe estar entre 1 y 300 segundos")
            else:
                print("❌ Uso: duration [segundos]")
        
        elif command == "exit":
            print("👋 Saliendo del simulador...")
            break
        
        else:
            print("❌ Comando no reconocido. Escribe 'help' para ver opciones.")

except KeyboardInterrupt:
    print("\n👋 Simulador interrumpido por el usuario")

finally:
    client.loop_stop()
    client.disconnect()