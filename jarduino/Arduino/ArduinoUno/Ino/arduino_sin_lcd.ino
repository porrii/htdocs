/*******************************************************
 * Riego Inteligente — Arduino UNO + ESP-01S (WiFiEsp)
 * MQTT JSON bidireccional + Encoder (sin LCD)
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
const char* TOPIC_SENSORES   = "sensores";
String TOPIC_ESTADO          = String("estado/") + DEVICE_ID;
const char* TOPIC_RIEGO      = "riego";
const char* TOPIC_STOP       = "stop";
const char* TOPIC_CONFIG_AUTO      = "config/auto";
const char* TOPIC_CONFIG_THRESHOLD = "config/threshold";

// Pines
const uint8_t DHT_PIN    = 7;
const uint8_t DHT_TYPE   = DHT11;
const uint8_t SOIL_PIN   = A0;
const uint8_t RELAY_PIN  = 8;
const bool    RELAY_ACTIVE_HIGH = true;

// Encoder
const uint8_t ENC_A    = 3;
const uint8_t ENC_B    = 4;
const uint8_t ENC_BTN  = 5;

// ESP-01S Serial
SoftwareSerial espSerial(10, 11); // RX, TX
WiFiEspClient espClient;

// MQTT
PubSubClient mqtt(espClient);

// DHT
DHT dht(DHT_PIN, DHT_TYPE);

// Humedad suelo
const int SOIL_WET_RAW = 350;
const int SOIL_DRY_RAW = 850;

// Timings
const uint32_t SENSORS_INTERVAL_MS = 5000;
const uint32_t STATUS_INTERVAL_MS  = 30000;

// Auto-riego
const uint16_t AUTO_PUMP_DEFAULT_S = 5;

/************ ESTADO ************/
struct Config {
  bool autoIrrigation;
  uint8_t soilThreshold;     // %
  uint16_t manualDurationS;  // s
  uint16_t crc;
} cfg;

bool pumpActive = false;
uint32_t pumpStopAt = 0;

float lastTemp = NAN, lastHum = NAN;
int   lastSoilPct = -1;

volatile int16_t encDelta = 0;
uint32_t lastBtnPressMs = 0;
bool btnPressed = false;

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

/************ ENCODER ************/
void onEncA() {
  bool a = digitalRead(ENC_A);
  bool b = digitalRead(ENC_B);
  if (a == b) encDelta++; else encDelta--;
}

void onBtn() {
  uint32_t now = millis();
  if (now - lastBtnPressMs > 200) {
    btnPressed = true;
    lastBtnPressMs = now;
  }
}

/************ MQTT ************/
void publishEstadoOnline() {
  StaticJsonDocument<128> doc;
  doc["id"] = DEVICE_ID;
  doc["online"] = true;
  char buf[128];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  mqtt.publish(TOPIC_ESTADO.c_str(), buf, n);
}

void publishSensores() {
  StaticJsonDocument<256> doc;
  doc["id"]   = DEVICE_ID;
  doc["t"]    = isnan(lastTemp) ? NAN  : lastTemp;
  doc["h"]    = isnan(lastHum)  ? NAN  : lastHum;
  doc["soil"] = lastSoilPct;
  doc["pump"] = pumpActive;
  char buf[256];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  mqtt.publish(TOPIC_SENSORES, buf, n);
}

void startPumpFor(uint16_t seconds) {
  if (seconds == 0) return;
  if (!pumpActive) {
    setRelay(true);
    pumpStopAt = millis() + (uint32_t)seconds * 1000UL;
  }
}

void stopPump() {
  setRelay(false);
  pumpStopAt = 0;
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  payload[length] = '\0';
  String msg = String((char*)payload);
  
  if (String(topic) == TOPIC_RIEGO) {
    int dur = msg.toInt();
    startPumpFor(dur);
    Serial.print("Riego manual por: "); Serial.println(dur);
  }
}

/************ WIFI ************/
int status = WL_IDLE_STATUS;

void InitWiFi() {
  espSerial.begin(9600);
  WiFi.init(&espSerial);

  if (WiFi.status() == WL_NO_SHIELD) {
    Serial.println("ESP-01S no detectado");
    while(true);
  }
  reconnectWiFi();
}

void reconnectWiFi() {
  while (status != WL_CONNECTED) {
    Serial.print("Conectando a SSID: ");
    Serial.println(WIFI_SSID);
    status = WiFi.begin(WIFI_SSID, WIFI_PASS);
    delay(1000);
  }
  Serial.println("¡Conectado a WiFi!");
  Serial.print("IP: "); Serial.println(WiFi.localIP());
}

void reconnectMQTT() {
  while (!mqtt.connected()) {
    Serial.print("Conectando a MQTT: "); Serial.println(MQTT_SERVER);
    String clientId = "ARD1-" + String(random(0xffff), HEX);
    if (mqtt.connect(clientId.c_str())) {
      mqtt.subscribe(TOPIC_RIEGO);
      Serial.println("MQTT conectado");
    } else {
      Serial.print("Error MQTT: "); Serial.println(mqtt.state());
      delay(5000);
    }
  }
}

/************ SETUP ************/
void setup() {
  Serial.begin(9600);
  pinMode(RELAY_PIN, OUTPUT);
  setRelay(false);

  pinMode(ENC_A, INPUT_PULLUP);
  pinMode(ENC_B, INPUT_PULLUP);
  pinMode(ENC_BTN, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(ENC_A), onEncA, CHANGE);
  attachInterrupt(digitalPinToInterrupt(ENC_BTN), onBtn, FALLING);

  dht.begin();

  InitWiFi();
  mqtt.setServer(MQTT_SERVER, MQTT_PORT);
  mqtt.setCallback(mqttCallback);

  wdt_enable(WDTO_2S);
}

/************ LOOP ************/
void loop() {
  wdt_reset();

  if (WiFi.status() != WL_CONNECTED) reconnectWiFi();
  if (!mqtt.connected()) reconnectMQTT();
  mqtt.loop();

  uint32_t now = millis();

  // Leer sensores cada 5s
  if (now - lastSensorsMs >= SENSORS_INTERVAL_MS) {
    lastSensorsMs = now;
    float t = dht.readTemperature();
    float h = dht.readHumidity();
    if (!isnan(t)) lastTemp = t;
    if (!isnan(h)) lastHum  = h;
    int soilRaw = analogRead(SOIL_PIN);
    lastSoilPct = soilToPercent(soilRaw);

    publishSensores();
  }

  // Control bomba
  if (pumpActive && pumpStopAt != 0 && (int32_t)(now - pumpStopAt) >= 0) {
    stopPump();
  }

  // Encoder manual
  int16_t delta = 0;
  noInterrupts(); delta = encDelta; encDelta = 0; interrupts();
  if (delta != 0) {
    long v = (long)cfg.manualDurationS + (delta > 0 ? 1 : -1);
    cfg.manualDurationS = (uint16_t)constrain(v, 1, 600);
    Serial.print("Duración manual ajustada: "); Serial.println(cfg.manualDurationS);
  }

  if (btnPressed) {
    btnPressed = false;
    startPumpFor(cfg.manualDurationS);
    Serial.print("Riego manual activado por "); Serial.println(cfg.manualDurationS);
  }

}
