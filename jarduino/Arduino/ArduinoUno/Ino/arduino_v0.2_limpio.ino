/*******************************************************
 * Riego Inteligente — Arduino UNO + ESP-01S (WiFiEsp)
 * MQTT JSON bidireccional (sin encoder)
 *******************************************************/
#include <Arduino.h>
#include <SoftwareSerial.h>
#include <EEPROM.h>
#include <avr/wdt.h>

#include <WiFiEsp.h>          // ESP-01S con firmware AT
#include <PubSubClient.h>     // MQTT
#include <ArduinoJson.h>      // JSON

#include <DHT.h>
#include <DHT_U.h>

/************ CONFIGURACIÓN ************/
const char* WIFI_SSID   = "TP-Link_7F20";
const char* WIFI_PASS   = "79949910";
const char* MQTT_SERVER = "192.168.0.100";
const uint16_t MQTT_PORT = 1883;

const char* DEVICE_ID   = "ARD1";

// Tópicos
const char* TOPIC_SENSORES         = "sensores";
String TOPIC_ESTADO                = String("estado/") + DEVICE_ID;
const char* TOPIC_RIEGO            = "riego";
const char* TOPIC_STOP             = "stop";
const char* TOPIC_CONFIG_AUTO      = "config/auto";
const char* TOPIC_CONFIG_THRESHOLD = "config/threshold";

// Pines
const uint8_t DHT_PIN    = 7;
const uint8_t DHT_TYPE   = DHT11;
const uint8_t SOIL_PIN   = A0;
const uint8_t RELAY_PIN  = 8;
const bool    RELAY_ACTIVE_HIGH = true;

// ESP-01S Serial
SoftwareSerial espSerial(10, 11); // RX, TX
WiFiEspClient espClient;

// MQTT
PubSubClient mqtt(espClient);

// DHT
DHT dht(DHT_PIN, DHT_TYPE);

// Humedad suelo (ajustar según sensor real)
const int SOIL_WET_RAW = 350;
const int SOIL_DRY_RAW = 850;

// Timings
const uint32_t SENSORS_INTERVAL_MS = 10000;
const uint32_t STATUS_INTERVAL_MS  = 30000;

// Direcciones EEPROM
#define ADDR_AUTO_IRRIGATION   0   // 1 byte
#define ADDR_SOIL_THRESHOLD    1   // 1 byte
#define ADDR_MANUAL_DURATION   2   // 2 bytes (uint16_t)

// Intervalos WiFi & MQTT
uint32_t lastWiFiCheckMs = 0;
const uint32_t WIFI_CHECK_INTERVAL = 2000; // revisar cada 2s
uint32_t lastMQTTCheckMs = 0;
const uint32_t MQTT_CHECK_INTERVAL = 2000;  // revisar cada 2s

// Variables para controlar cambios
float prevTemp = NAN;
float prevHum  = NAN;
int   prevSoilPct = -1;

// Umbrales mínimos para publicar
const float TEMP_CHANGE_THRESHOLD = 0.5;    // °C
const float HUM_CHANGE_THRESHOLD  = 2.0;    // %
const int   SOIL_CHANGE_THRESHOLD = 2;      // %

/************ ESTADO ************/
struct Config {
  bool autoIrrigation = false;
  uint8_t soilThreshold = 40;     // %
  uint16_t manualDurationS = 5;   // s
} cfg;

bool pumpActive = false;
uint32_t pumpStopAt = 0;

float lastTemp = NAN, lastHum = NAN;
int   lastSoilPct = -1;

// Tareas
uint32_t lastSensorsMs = 0;
uint32_t lastStatusMs  = 0;

/************ UTILIDADES ************/
int soilToPercent(int raw) {
  raw = constrain(raw, SOIL_WET_RAW, SOIL_DRY_RAW);
  int pct = map(raw, SOIL_DRY_RAW, SOIL_WET_RAW, 0, 100);
  return constrain(pct, 0, 100);
}

void setRelay(bool on) {
  pumpActive = on;
  if (RELAY_ACTIVE_HIGH) digitalWrite(RELAY_PIN, on ? HIGH : LOW);
  else                   digitalWrite(RELAY_PIN, on ? LOW  : HIGH);
}

void startPumpFor(uint16_t seconds, bool isAuto = false) {
    if (seconds == 0) return;
    if (!pumpActive) {
        setRelay(true);
        pumpStopAt = millis() + (uint32_t)seconds * 1000UL;
        Serial.print("Bomba activada por "); Serial.print(seconds); 
        Serial.println("s");

        // PUBLICAR AUTO-RIEGO SI CORRESPONDE
        if (isAuto) {
            StaticJsonDocument<128> doc;
            doc["device"] = DEVICE_ID;
            doc["duration"] = seconds;
            doc["mode"] = "auto";
            char buf[128];
            size_t n = serializeJson(doc, buf, sizeof(buf));
            mqtt.publish("riego", buf, n);
        }
    }
}

void stopPump() {
  if (pumpActive) {
    setRelay(false);
    pumpStopAt = 0;
    Serial.println("Bomba detenida");
  }
}

/************ EEPROM ************/
void saveConfigEEPROM() {
  EEPROM.update(ADDR_AUTO_IRRIGATION, cfg.autoIrrigation ? 1 : 0);
  EEPROM.update(ADDR_SOIL_THRESHOLD, cfg.soilThreshold);
  EEPROM.put(ADDR_MANUAL_DURATION, cfg.manualDurationS); // 2 bytes
  Serial.println("Configuración guardada en EEPROM");
}

void loadConfigEEPROM() {
  cfg.autoIrrigation = EEPROM.read(ADDR_AUTO_IRRIGATION) != 0;
  cfg.soilThreshold  = EEPROM.read(ADDR_SOIL_THRESHOLD);
  EEPROM.get(ADDR_MANUAL_DURATION, cfg.manualDurationS);
  Serial.println("Configuración cargada desde EEPROM:");
  Serial.print("  Auto-riego: "); Serial.println(cfg.autoIrrigation ? "ON" : "OFF");
  Serial.print("  Umbral humedad: "); Serial.println(cfg.soilThreshold);
  Serial.print("  Duración manual: "); Serial.println(cfg.manualDurationS);
}

/************ MQTT PUBLICAR ************/
void publishSensores() {
  StaticJsonDocument<256> doc;
  doc["id"]   = DEVICE_ID;
  doc["pump"] = pumpActive;

  // Solo publicar temperatura y humedad si son válidos
  if (!isnan(lastTemp)) doc["t"] = lastTemp;
  if (!isnan(lastHum))  doc["h"] = lastHum;

  doc["soil"] = lastSoilPct;

  char buf[256];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n > 0) mqtt.publish(TOPIC_SENSORES, buf, n);
}

void publishEstadoOnline() {
  StaticJsonDocument<128> doc;
  doc["id"] = DEVICE_ID;
  doc["online"] = true;
  char buf[128];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n > 0) mqtt.publish(TOPIC_ESTADO.c_str(), buf, n);
}

/************ MQTT CALLBACK ************/
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  // Copiar el payload a un buffer seguro
  char msgBuf[256];
  if (length >= sizeof(msgBuf)) length = sizeof(msgBuf) - 1;
  memcpy(msgBuf, payload, length);
  msgBuf[length] = '\0';

  String msg = String(msgBuf);

  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, msg);
  if (err) {
    Serial.print("Error parseando JSON: ");
    Serial.println(err.c_str());
    return;
  }

  // Filtrar por device_id si existe
  if (doc.containsKey("device") && String(doc["device"].as<const char*>()) != DEVICE_ID) return;

  // Manejo de tópicos
  if (String(topic) == TOPIC_RIEGO) {
    int dur = doc["duration"] | cfg.manualDurationS;
    dur = max(dur, 1); // mínimo 1 segundo
    startPumpFor(dur);

  } else if (String(topic) == TOPIC_STOP) {
    stopPump();

  } else if (String(topic) == TOPIC_CONFIG_AUTO) {
    if (doc.containsKey("enabled")) {
      cfg.autoIrrigation = doc["enabled"];
      Serial.print("Auto-riego: ");
      Serial.println(cfg.autoIrrigation ? "ON" : "OFF");
      saveConfigEEPROM(); // <--- guardar cambios
    }

  } else if (String(topic) == TOPIC_CONFIG_THRESHOLD) {
    if (doc.containsKey("threshold")) {
      int th = doc["threshold"];
      cfg.soilThreshold = constrain(th, 0, 100);
      Serial.print("Umbral humedad: ");
      Serial.println(cfg.soilThreshold);
      saveConfigEEPROM(); // <--- guardar cambios
    }
  }
}

/************ WIFI ************/
int status = WL_IDLE_STATUS;

void InitWiFi() {
  espSerial.begin(9600);
  WiFi.init(&espSerial);

  if (WiFi.status() == WL_NO_SHIELD) {
    Serial.println("ESP-01S no detectado");
    while (true);
  }
  checkWiFi();
}

void checkWiFi() {
  uint32_t now = millis();
  if (now - lastWiFiCheckMs < WIFI_CHECK_INTERVAL) return;
  lastWiFiCheckMs = now;

  if (WiFi.status() != WL_CONNECTED) {
    Serial.print("Reconectando WiFi...");
    WiFi.begin(WIFI_SSID, WIFI_PASS);
  }
}

void checkMQTT() {
  uint32_t now = millis();
  if (now - lastMQTTCheckMs < MQTT_CHECK_INTERVAL) return;
  lastMQTTCheckMs = now;

  if (!mqtt.connected() && WiFi.status() == WL_CONNECTED) {
    Serial.print("Reconectando MQTT...");
    String clientId = String(DEVICE_ID) + "-" + String(random(0xffff), HEX);
    if (mqtt.connect(clientId.c_str())) {
      mqtt.subscribe(TOPIC_RIEGO);
      mqtt.subscribe(TOPIC_STOP);
      mqtt.subscribe(TOPIC_CONFIG_AUTO);
      mqtt.subscribe(TOPIC_CONFIG_THRESHOLD);
      Serial.println("MQTT conectado");
      publishEstadoOnline();
    } else {
      Serial.print("Error MQTT: "); Serial.println(mqtt.state());
    }
  }
}

/************ SETUP ************/
void setup() {
  Serial.begin(9600);
  pinMode(RELAY_PIN, OUTPUT);
  setRelay(false);

  dht.begin();

  // Cargar configuración guardada
  loadConfigEEPROM();

  InitWiFi();
  mqtt.setServer(MQTT_SERVER, MQTT_PORT);
  mqtt.setCallback(mqttCallback);

  wdt_enable(WDTO_2S);
}

/************ LOOP ************/
void loop() {
  wdt_reset();

  checkWiFi();
  checkMQTT();
  
  mqtt.loop();

  uint32_t now = millis();

  // Leer sensores cada intervalo configurado = SENSORS_INTERVAL_MS
  if (now - lastSensorsMs >= SENSORS_INTERVAL_MS) {
      lastSensorsMs = now;

      float t = dht.readTemperature();
      float h = dht.readHumidity();
      int soilRaw = analogRead(SOIL_PIN);
      int soilPct = soilToPercent(soilRaw);

      bool publishNeeded = false;

      // Comprobar cambios significativos
      if (!isnan(t) && (isnan(prevTemp) || abs(t - prevTemp) >= TEMP_CHANGE_THRESHOLD)) {
          prevTemp = t;
          lastTemp = t;
          publishNeeded = true;
      }

      if (!isnan(h) && (isnan(prevHum) || abs(h - prevHum) >= HUM_CHANGE_THRESHOLD)) {
          prevHum = h;
          lastHum = h;
          publishNeeded = true;
      }

      if (prevSoilPct == -1 || abs(soilPct - prevSoilPct) >= SOIL_CHANGE_THRESHOLD) {
          prevSoilPct = soilPct;
          lastSoilPct = soilPct;
          publishNeeded = true;
      }

      // Publicar solo si hay cambios
      if (publishNeeded) {
          publishSensores();
      }

      // Auto-riego
      if (cfg.autoIrrigation && !pumpActive && lastSoilPct < cfg.soilThreshold) {
          Serial.println("Humedad baja, activando auto-riego");
          startPumpFor(cfg.manualDurationS, true);
      }
  }

  // Publicar estado cada 30s
  if (now - lastStatusMs >= STATUS_INTERVAL_MS) {
    lastStatusMs = now;
    publishEstadoOnline();
  }

  // Control bomba (apagado automático)
  if (pumpActive && pumpStopAt != 0 && (int32_t)(now - pumpStopAt) >= 0) {
    stopPump();
  }
}
