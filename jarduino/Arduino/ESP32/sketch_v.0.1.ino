#include <WiFi.h>
#include <PubSubClient.h>
#include <U8g2lib.h>
#include <Preferences.h>
#include "DHT.h"

// ================== CONFIG PINS ==================
#define RELAY_PIN 26
#define RELAY_ACTIVE_STATE HIGH

#define DHT_PIN 13
#define DHT_TYPE DHT11
#define SOIL_PIN 34 // ADC

// 5 Botones: UP, DOWN, LEFT, RIGHT, OK
#define BTN_UP      14
#define BTN_DOWN    12
#define BTN_LEFT    32
#define BTN_RIGHT   33
#define BTN_OK      25

// OLED 0.96" I2C
#define I2C_SDA 21
#define I2C_SCL 22
U8G2_SSD1306_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, /* reset=*/ U8X8_PIN_NONE, I2C_SCL, I2C_SDA);

DHT dht(DHT_PIN, DHT_TYPE);
Preferences prefs;

// ================== STATE ==================
struct Config {
  char ssid[32];
  char pass[32];
  char mqttServer[32];
  uint16_t mqttPort;
  char deviceId[16];
  bool autoIrrigation;
  int soilThreshold;
  int irrigationDuration; // segundos
} cfg;

WiFiClient espClient;
PubSubClient mqttClient(espClient);

bool pumpActive = false;
unsigned long pumpStartMs = 0;
unsigned long pumpDurationMs = 0;

unsigned long lastPublish = 0;
const unsigned long publishInterval = 5000;

bool wifiConnected = false;
bool mqttConnected = false;

// Menu
enum MenuPage {
  PAGE_MAIN_DASHBOARD,
  PAGE_SENSORS,
  PAGE_IRRIGATION,
  PAGE_SETTINGS,
  PAGE_NETWORK,
  PAGE_SYSTEM,
  PAGE_COUNT
};

enum SettingsPage {
  SETTINGS_MAIN,
  SETTINGS_AUTO,
  SETTINGS_THRESHOLD,
  SETTINGS_DURATION,
  SETTINGS_DEVICE_ID,
  SETTINGS_COUNT
};

enum NetworkPage {
  NETWORK_MAIN,
  NETWORK_WIFI_SSID,
  NETWORK_WIFI_PASS,
  NETWORK_MQTT_SERVER,
  NETWORK_MQTT_PORT,
  NETWORK_RECONNECT,
  NETWORK_COUNT
};

enum SystemPage {
  SYSTEM_MAIN,
  SYSTEM_SAVE,
  SYSTEM_CLEAR_CONFIG,
  SYSTEM_RESTART,
  SYSTEM_COUNT
};

MenuPage currentPage = PAGE_MAIN_DASHBOARD;
SettingsPage settingsPage = SETTINGS_MAIN;
NetworkPage networkPage = NETWORK_MAIN;
SystemPage systemPage = SYSTEM_MAIN;

int menuIndex = 0;
const char* menuItems[] = {
  "Dashboard",
  "Sensores",
  "Riego",
  "Configuracion",
  "Red",
  "Sistema"
};

const char* settingsItems[] = {
  "Auto-Riego",
  "Umbral Suelo",
  "Duracion",
  "ID Dispositivo",
  "Atras"
};

const char* networkItems[] = {
  "WiFi SSID",
  "WiFi PASS",
  "MQTT Server",
  "MQTT Puerto",
  "Reconectar",
  "Atras"
};

const char* systemItems[] = {
  "Guardar",
  "Borrar Config",
  "Reiniciar",
  "Atras"
};

bool editingText = false;
int editPos = 0;
char *editField = nullptr;
int editFieldLen = 0;

// ================== ICONOS ==================
// Iconos de 16x16 pixels
const unsigned char icon_wifi[] = {
  0b00000000,0b00000000,
  0b00000000,0b00000000,
  0b00000111,0b11100000,
  0b00011000,0b00011000,
  0b00100000,0b00000100,
  0b01000000,0b00000010,
  0b00000111,0b11100000,
  0b00001000,0b00010000,
  0b00010000,0b00001000,
  0b00000011,0b11000000,
  0b00000100,0b00100000,
  0b00001000,0b00010000,
  0b00000001,0b10000000,
  0b00000010,0b01000000,
  0b00000000,0b00000000,
  0b00000000,0b00000000
};

const unsigned char icon_mqtt[] = {
  0b00000000,0b00000000,
  0b00000000,0b00000000,
  0b00001111,0b11110000,
  0b00010000,0b00001000,
  0b00100000,0b00000100,
  0b01000000,0b00000010,
  0b10000000,0b00000001,
  0b10000000,0b00000001,
  0b10000000,0b00000001,
  0b10000000,0b00000001,
  0b01000000,0b00000010,
  0b00100000,0b00000100,
  0b00010000,0b00001000,
  0b00001111,0b11110000,
  0b00000000,0b00000000,
  0b00000000,0b00000000
};

const unsigned char icon_pump[] = {
  0b00000000,0b00000000,
  0b00000000,0b00000000,
  0b00000011,0b10000000,
  0b00000100,0b01000000,
  0b00000100,0b01000000,
  0b00000111,0b11000000,
  0b00000100,0b01000000,
  0b00000100,0b01000000,
  0b00011111,0b11110000,
  0b00100000,0b00001000,
  0b01000000,0b00000100,
  0b01000000,0b00000100,
  0b00100000,0b00001000,
  0b00011111,0b11110000,
  0b00000000,0b00000000,
  0b00000000,0b00000000
};

const unsigned char icon_settings[] = {
  0b00000000,0b00000000,
  0b00000001,0b10000000,
  0b00000001,0b10000000,
  0b00011001,0b10011000,
  0b00100111,0b11100100,
  0b01000111,0b11100010,
  0b01001111,0b11110010,
  0b10001111,0b11110001,
  0b10001111,0b11110001,
  0b01001111,0b11110010,
  0b01000111,0b11100010,
  0b00100111,0b11100100,
  0b00011001,0b10011000,
  0b00000001,0b10000000,
  0b00000001,0b10000000,
  0b00000000,0b00000000
};

// ================== HELPERS ==================
void drawIcon(int x, int y, const unsigned char* icon) {
  for (int row = 0; row < 16; row++) {
    for (int col = 0; col < 16; col++) {
      int byteIndex = row * 2 + col / 8;
      int bitIndex = 7 - (col % 8);
      if (icon[byteIndex] & (1 << bitIndex)) {
        u8g2.drawPixel(x + col, y + row);
      }
    }
  }
}

void saveConfig() {
  prefs.putBytes("config", &cfg, sizeof(cfg));
  Serial.println("Configuración guardada en EEPROM");
}

void loadConfig() {
  if (prefs.getBytes("config", &cfg, sizeof(cfg)) != sizeof(cfg)) {
    Serial.println("Configuración por defecto cargada");
    strcpy(cfg.ssid, "TP-Link_7F20_5G");
    strcpy(cfg.pass, "79949910");
    strcpy(cfg.mqttServer, "192.168.0.100");
    cfg.mqttPort = 1883;
    strcpy(cfg.deviceId, "ARD1");
    cfg.autoIrrigation = true;
    cfg.soilThreshold = 50;
    cfg.irrigationDuration = 10;
    saveConfig();
  } else {
    Serial.println("Configuración cargada desde EEPROM");
  }
}

void setRelay(bool on) {
  digitalWrite(RELAY_PIN, on ? RELAY_ACTIVE_STATE : !RELAY_ACTIVE_STATE);
  pumpActive = on;
  Serial.print("Bomba ");
  Serial.println(on ? "ACTIVADA" : "DESACTIVADA");
}

int readSoilPct() {
  int raw = analogRead(SOIL_PIN);
  return constrain(map(raw, 4095, 0, 0, 100), 0, 100);
}

void reconnectWiFi() {
  Serial.println("Intentando reconectar WiFi...");
  WiFi.disconnect();
  delay(1000);
  WiFi.begin(cfg.ssid, cfg.pass);
  
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
    delay(500);
    Serial.print(".");
  }
  
  wifiConnected = (WiFi.status() == WL_CONNECTED);
  if (wifiConnected) {
    Serial.println("\nWiFi reconectado!");
  } else {
    Serial.println("\nError reconectando a WiFi");
  }
}

void publishConfig() {
  String msg = "{";
  msg += "\"device\":\"" + String(cfg.deviceId) + "\"";
  msg += ",\"auto\":" + String(cfg.autoIrrigation ? "true" : "false");
  msg += ",\"threshold\":" + String(cfg.soilThreshold);
  msg += ",\"duration\":" + String(cfg.irrigationDuration);
  msg += ",\"ssid\":\"" + String(cfg.ssid) + "\"";
  msg += ",\"mqtt\":\"" + String(cfg.mqttServer) + "\"";
  msg += ",\"port\":" + String(cfg.mqttPort);
  msg += "}";
  
  if (mqttClient.publish("config", msg.c_str(), true)) {
    Serial.println("Configuración publicada en MQTT");
  } else {
    Serial.println("Error publicando configuración en MQTT");
  }
}

void publishSensors() {
  float t = dht.readTemperature();
  float h = dht.readHumidity();
  int soil = readSoilPct();

  String payload = "{";
  payload += "\"device\":\"" + String(cfg.deviceId) + "\"";
  payload += ",\"pump\":" + String(pumpActive ? "true" : "false");
  if (!isnan(t)) payload += ",\"t\":" + String(t, 1);
  if (!isnan(h)) payload += ",\"h\":" + String(h, 1);
  payload += ",\"soil\":" + String(soil);
  payload += ",\"online\":" + String("true");
  payload += "}";

  if (mqttClient.publish("sensores", payload.c_str())) {
    Serial.println("Datos de sensores publicados en MQTT");
  } else {
    Serial.println("Error publicando datos de sensores en MQTT");
  }
}

// ================== MQTT ==================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  payload[length] = 0;
  String msg = (char*)payload;
  String top = topic;

  Serial.print("MQTT mensaje recibido: ");
  Serial.print(top);
  Serial.print(" -> ");
  Serial.println(msg);

  if (top == "riego") {
    if (msg.indexOf(cfg.deviceId) >= 0) {
      int dPos = msg.indexOf("\"duration\":");
      if (dPos > 0) {
        int duration = msg.substring(dPos + 11).toInt();
        if (duration > 0) {
          Serial.print("Activando bomba por ");
          Serial.print(duration);
          Serial.println(" segundos (comando MQTT)");
          setRelay(true);
          pumpStartMs = millis();
          pumpDurationMs = duration * 1000UL;
        }
      }
    }
  }
  else if (top == "config") {
    if (msg.indexOf(cfg.deviceId) >= 0) {
      if (msg.indexOf("\"auto\":true") > 0) cfg.autoIrrigation = true;
      if (msg.indexOf("\"auto\":false") > 0) cfg.autoIrrigation = false;

      int tPos = msg.indexOf("\"threshold\":");
      if (tPos > 0) cfg.soilThreshold = msg.substring(tPos + 12).toInt();

      int dPos = msg.indexOf("\"duration\":");
      if (dPos > 0) cfg.irrigationDuration = msg.substring(dPos + 11).toInt();

      saveConfig();
      Serial.println("Configuración actualizada desde MQTT");
    }
  }
  else if (top == "stop") {
    if (msg.indexOf(cfg.deviceId) >= 0) {
      Serial.println("Deteniendo bomba (comando MQTT)");
      setRelay(false);
    }
  }
}

void mqttReconnect() {
  Serial.print("Intentando conectar MQTT...");
  while (!mqttClient.connected()) {
    if (mqttClient.connect(cfg.deviceId)) {
      Serial.println("Conectado al broker MQTT!");
      mqttClient.subscribe("riego");
      mqttClient.subscribe("config");
      mqttClient.subscribe("stop");
      Serial.println("Suscripciones MQTT establecidas");
      mqttConnected = true;
      publishConfig();
    } else {
      Serial.print("Error MQTT, rc=");
      Serial.print(mqttClient.state());
      Serial.println(" reintentando en 2 segundos...");
      mqttConnected = false;
      delay(2000);
    }
  }
}

// ================== UI ==================
bool readButton(int pin) {
  return digitalRead(pin) == LOW;
}

char cycleChar(char c, int dir) {
  if (c < 32 || c > 126) c = 'A'; 
  c += dir;
  if (c < 32) c = 126;
  if (c > 126) c = 32;
  return c;
}

void startEdit(char* field, int maxlen) {
  editingText = true;
  editField = field;
  editFieldLen = maxlen;
  editPos = 0;
  if (editField[0] == 0) editField[0] = 'A';
  Serial.println("Modo edición activado");
}

void handleEdit() {
  if (!editingText) return;
  
  if (readButton(BTN_UP)) { 
    editField[editPos] = cycleChar(editField[editPos], +1); 
    delay(150); 
  }
  if (readButton(BTN_DOWN)) { 
    editField[editPos] = cycleChar(editField[editPos], -1); 
    delay(150); 
  }
  if (readButton(BTN_LEFT)) { 
    editPos = max(0, editPos - 1); 
    delay(200); 
  }
  if (readButton(BTN_RIGHT)) { 
    editPos = min(editFieldLen - 2, editPos + 1); 
    delay(200); 
  }
  if (readButton(BTN_OK)) {
    if (editPos < editFieldLen - 2) {
      editPos++;
      if (editField[editPos] == 0) editField[editPos] = 'A';
    } else {
      editField[editFieldLen - 1] = 0;
      editingText = false;
      Serial.println("Modo edición finalizado");
      saveConfig();
      if (wifiConnected) publishConfig();
    }
    delay(200);
  }
}

void drawStatusBar() {
  // Icono WiFi
  drawIcon(0, 0, icon_wifi);
  u8g2.setDrawColor(0);
  u8g2.drawBox(2, 2, 12, 12);
  u8g2.setDrawColor(1);
  drawIcon(0, 0, icon_wifi);
  if (!wifiConnected) u8g2.drawLine(0, 0, 15, 15);
  
  // Icono MQTT
  drawIcon(18, 0, icon_mqtt);
  if (!mqttConnected) u8g2.drawLine(18, 0, 33, 15);
  
  // Icono Bomba si está activa
  if (pumpActive) {
    drawIcon(36, 0, icon_pump);
  }
  
  // Hora de actividad
  u8g2.setFont(u8g2_font_5x7_tr);
  u8g2.setCursor(110, 7);
  u8g2.print(millis() / 60000);
  u8g2.print("m");
}

void renderMainDashboard() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  // Temperatura y Humedad
  u8g2.setCursor(0, 20);
  u8g2.print("T:");
  u8g2.print(isnan(dht.readTemperature()) ? "--" : String(dht.readTemperature(), 1));
  u8g2.print("C");
  
  u8g2.setCursor(64, 20);
  u8g2.print("H:");
  u8g2.print(isnan(dht.readHumidity()) ? "--" : String(dht.readHumidity(), 1));
  u8g2.print("%");

  // Humedad del suelo con barra
  int soil = readSoilPct();
  u8g2.setCursor(0, 35);
  u8g2.print("Suelo:");
  u8g2.print(soil);
  u8g2.print("%");
  
  // Barra de progreso
  int barWidth = map(soil, 0, 100, 0, 120);
  u8g2.drawFrame(0, 40, 120, 8);
  u8g2.drawBox(0, 40, barWidth, 8);

  // Estado de la bomba
  u8g2.setCursor(0, 55);
  u8g2.print("Bomba:");
  u8g2.print(pumpActive ? "ON " : "OFF");
  if (pumpActive) {
    u8g2.print(" ");
    u8g2.print((pumpDurationMs - (millis() - pumpStartMs)) / 1000);
    u8g2.print("s");
  }

  // Modo auto
  u8g2.setCursor(64, 55);
  u8g2.print("Auto:");
  u8g2.print(cfg.autoIrrigation ? "ON" : "OFF");
}

void renderSensorsPage() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  u8g2.setCursor(0, 15);
  u8g2.print("Temperatura: ");
  u8g2.print(isnan(dht.readTemperature()) ? "--" : String(dht.readTemperature(), 1));
  u8g2.print("C");

  u8g2.setCursor(0, 30);
  u8g2.print("Humedad: ");
  u8g2.print(isnan(dht.readHumidity()) ? "--" : String(dht.readHumidity(), 1));
  u8g2.print("%");

  u8g2.setCursor(0, 45);
  u8g2.print("Humedad Suelo: ");
  u8g2.print(readSoilPct());
  u8g2.print("%");

  u8g2.setCursor(0, 60);
  u8g2.print("Umbral: ");
  u8g2.print(cfg.soilThreshold);
  u8g2.print("%");
}

void renderIrrigationPage() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  u8g2.setCursor(0, 20);
  u8g2.print("Estado: ");
  u8g2.print(pumpActive ? "ACTIVA" : "INACTIVA");

  if (pumpActive) {
    u8g2.setCursor(0, 35);
    u8g2.print("Tiempo restante: ");
    u8g2.print((pumpDurationMs - (millis() - pumpStartMs)) / 1000);
    u8g2.print("s");
  }

  u8g2.setCursor(0, 50);
  u8g2.print("OK: ");
  u8g2.print(pumpActive ? "Detener" : "Iniciar");
}

void renderMenu() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  u8g2.setCursor(0, 15);
  for (int i = 0; i < 3; i++) {
    int itemIndex = (menuIndex / 3) * 3 + i;
    if (itemIndex < PAGE_COUNT) {
      if (itemIndex == menuIndex) {
        u8g2.print("> ");
      } else {
        u8g2.print("  ");
      }
      u8g2.println(menuItems[itemIndex]);
    }
  }
}

void renderSettings() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  u8g2.setCursor(0, 15);
  for (int i = 0; i < 3; i++) {
    int itemIndex = (settingsPage / 3) * 3 + i;
    if (itemIndex < SETTINGS_COUNT) {
      if (itemIndex == settingsPage) {
        u8g2.print("> ");
      } else {
        u8g2.print("  ");
      }
      u8g2.print(settingsItems[itemIndex]);
      
      if (itemIndex == SETTINGS_AUTO) {
        u8g2.print(": ");
        u8g2.print(cfg.autoIrrigation ? "ON" : "OFF");
      } else if (itemIndex == SETTINGS_THRESHOLD) {
        u8g2.print(": ");
        u8g2.print(cfg.soilThreshold);
        u8g2.print("%");
      } else if (itemIndex == SETTINGS_DURATION) {
        u8g2.print(": ");
        u8g2.print(cfg.irrigationDuration);
        u8g2.print("s");
      }
      u8g2.println();
    }
  }
}

void renderNetwork() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  u8g2.setCursor(0, 15);
  for (int i = 0; i < 3; i++) {
    int itemIndex = (networkPage / 3) * 3 + i;
    if (itemIndex < NETWORK_COUNT) {
      if (itemIndex == networkPage) {
        u8g2.print("> ");
      } else {
        u8g2.print("  ");
      }
      u8g2.print(networkItems[itemIndex]);
      
      if (itemIndex == NETWORK_RECONNECT) {
        u8g2.print(": ");
        u8g2.print(wifiConnected ? "Conectado" : "Desconectado");
      }
      u8g2.println();
    }
  }
}

void renderSystem() {
  u8g2.setFont(u8g2_font_6x10_tr);
  
  u8g2.setCursor(0, 15);
  for (int i = 0; i < 3; i++) {
    int itemIndex = (systemPage / 3) * 3 + i;
    if (itemIndex < SYSTEM_COUNT) {
      if (itemIndex == systemPage) {
        u8g2.print("> ");
      } else {
        u8g2.print("  ");
      }
      u8g2.println(systemItems[itemIndex]);
    }
  }
}

void renderEdit() {
  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.setCursor(0, 20);
  u8g2.print("Editando: ");
  
  u8g2.setCursor(0, 35);
  for (int i = 0; i < editFieldLen - 1; i++) {
    if (i == editPos) u8g2.print("[");
    u8g2.print(editField[i] ? editField[i] : ' ');
    if (i == editPos) u8g2.print("]");
  }
  
  u8g2.setCursor(0, 50);
  u8g2.print("←→:Navegar  ↑↓:Cambiar");
}

void renderPage() {
  u8g2.clearBuffer();
  drawStatusBar();
  
  if (editingText) {
    renderEdit();
    u8g2.sendBuffer();
    return;
  }

  u8g2.setFont(u8g2_font_6x10_tr);
  
  switch (currentPage) {
    case PAGE_MAIN_DASHBOARD:
      renderMainDashboard();
      break;
    case PAGE_SENSORS:
      renderSensorsPage();
      break;
    case PAGE_IRRIGATION:
      renderIrrigationPage();
      break;
    case PAGE_SETTINGS:
      renderSettings();
      break;
    case PAGE_NETWORK:
      renderNetwork();
      break;
    case PAGE_SYSTEM:
      renderSystem();
      break;
  }

  u8g2.sendBuffer();
}

// ================== SETUP ==================
void setup() {
  Serial.begin(115200);
  Serial.println("\n=== SMART GARDEN SYSTEM INICIANDO ===");

  pinMode(RELAY_PIN, OUTPUT);
  setRelay(false);

  pinMode(BTN_UP, INPUT_PULLUP);
  pinMode(BTN_DOWN, INPUT_PULLUP);
  pinMode(BTN_LEFT, INPUT_PULLUP);
  pinMode(BTN_RIGHT, INPUT_PULLUP);
  pinMode(BTN_OK, INPUT_PULLUP);
  Serial.println("Pines configurados");

  dht.begin();
  u8g2.begin();
  Serial.println("Sensores y display inicializados");

  prefs.begin("smartgarden", false);
  
  // Opción para borrar configuración
  Serial.println("Presiona 'R' en 3 segundos para borrar configuración...");
  delay(3000);
  if (Serial.available() > 0) {
    char command = Serial.read();
    if (command == 'R' || command == 'r') {
      prefs.clear();
      Serial.println("Configuración BORRADA de EEPROM");
      ESP.restart();
    }
  }
  
  loadConfig();

  Serial.print("Conectando a WiFi: ");
  Serial.println(cfg.ssid);
  WiFi.begin(cfg.ssid, cfg.pass);
  
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 8000) {
    delay(500);
    Serial.print(".");
  }
  
  wifiConnected = (WiFi.status() == WL_CONNECTED);
  if (wifiConnected) {
    Serial.println("\nWiFi conectado!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nError conectando a WiFi");
  }

  mqttClient.setServer(cfg.mqttServer, cfg.mqttPort);
  mqttClient.setCallback(mqttCallback);
  Serial.print("Servidor MQTT configurado: ");
  Serial.print(cfg.mqttServer);
  Serial.print(":");
  Serial.println(cfg.mqttPort);

  Serial.println("=== SETUP COMPLETADO ===");
}

// ================== LOOP ==================
void loop() {
  static unsigned long lastButtonCheck = 0;
  static unsigned long lastSensorUpdate = 0;
  const unsigned long buttonCheckInterval = 100;
  const unsigned long sensorUpdateInterval = 1000;
  
  wifiConnected = (WiFi.status() == WL_CONNECTED);
  mqttConnected = mqttClient.connected();
  
  if (wifiConnected && !mqttConnected) mqttReconnect();
  if (wifiConnected) mqttClient.loop();

  if (pumpActive && millis() - pumpStartMs >= pumpDurationMs) {
    Serial.println("Tiempo de riego completado, apagando bomba");
    setRelay(false);
  }

  if (millis() - lastPublish > publishInterval) {
    lastPublish = millis();
    if (wifiConnected && mqttConnected) {
      publishSensors();
    }
  }

  if (editingText) { 
    handleEdit(); 
    renderPage(); 
    return; 
  }

  // Actualizar página principal cada segundo
  if (currentPage == PAGE_MAIN_DASHBOARD && millis() - lastSensorUpdate > sensorUpdateInterval) {
    lastSensorUpdate = millis();
    renderPage();
  }

  // Navegación con botones
  if (millis() - lastButtonCheck > buttonCheckInterval) {
    lastButtonCheck = millis();
    
    if (readButton(BTN_UP)) {
      switch (currentPage) {
        case PAGE_MAIN_DASHBOARD: break;
        case PAGE_SENSORS: break;
        case PAGE_IRRIGATION: break;
        case PAGE_SETTINGS: settingsPage = (SettingsPage)((settingsPage + SETTINGS_COUNT - 1) % SETTINGS_COUNT); break;
        case PAGE_NETWORK: networkPage = (NetworkPage)((networkPage + NETWORK_COUNT - 1) % NETWORK_COUNT); break;
        case PAGE_SYSTEM: systemPage = (SystemPage)((systemPage + SYSTEM_COUNT - 1) % SYSTEM_COUNT); break;
      }
      renderPage();
      delay(200);
    }
    
    if (readButton(BTN_DOWN)) {
      switch (currentPage) {
        case PAGE_MAIN_DASHBOARD: break;
        case PAGE_SENSORS: break;
        case PAGE_IRRIGATION: break;
        case PAGE_SETTINGS: settingsPage = (SettingsPage)((settingsPage + 1) % SETTINGS_COUNT); break;
        case PAGE_NETWORK: networkPage = (NetworkPage)((networkPage + 1) % NETWORK_COUNT); break;
        case PAGE_SYSTEM: systemPage = (SystemPage)((systemPage + 1) % SYSTEM_COUNT); break;
      }
      renderPage();
      delay(200);
    }
    
    if (readButton(BTN_LEFT)) {
      currentPage = (MenuPage)((currentPage + PAGE_COUNT - 1) % PAGE_COUNT);
      renderPage();
      delay(200);
    }
    
    if (readButton(BTN_RIGHT)) {
      currentPage = (MenuPage)((currentPage + 1) % PAGE_COUNT);
      renderPage();
      delay(200);
    }
    
    if (readButton(BTN_OK)) {
      switch (currentPage) {
        case PAGE_MAIN_DASHBOARD:
          // No action
          break;
        case PAGE_SENSORS:
          // No action
          break;
        case PAGE_IRRIGATION:
          if (pumpActive) {
            setRelay(false);
          } else {
            setRelay(true);
            pumpStartMs = millis();
            pumpDurationMs = cfg.irrigationDuration * 1000UL;
          }
          break;
        case PAGE_SETTINGS:
          switch (settingsPage) {
            case SETTINGS_AUTO:
              cfg.autoIrrigation = !cfg.autoIrrigation;
              saveConfig();
              break;
            case SETTINGS_THRESHOLD:
              cfg.soilThreshold = (cfg.soilThreshold + 5) % 100;
              if (cfg.soilThreshold < 10) cfg.soilThreshold = 10;
              saveConfig();
              break;
            case SETTINGS_DURATION:
              cfg.irrigationDuration = (cfg.irrigationDuration + 5) % 120;
              if (cfg.irrigationDuration < 5) cfg.irrigationDuration = 5;
              saveConfig();
              break;
            case SETTINGS_DEVICE_ID:
              startEdit(cfg.deviceId, sizeof(cfg.deviceId));
              break;
            case SETTINGS_COUNT:
              currentPage = PAGE_MAIN_DASHBOARD;
              break;
          }
          break;
        case PAGE_NETWORK:
          switch (networkPage) {
            case NETWORK_WIFI_SSID:
              startEdit(cfg.ssid, sizeof(cfg.ssid));
              break;
            case NETWORK_WIFI_PASS:
              startEdit(cfg.pass, sizeof(cfg.pass));
              break;
            case NETWORK_MQTT_SERVER:
              startEdit(cfg.mqttServer, sizeof(cfg.mqttServer));
              break;
            case NETWORK_MQTT_PORT:
              cfg.mqttPort = (cfg.mqttPort + 100) % 10000;
              if (cfg.mqttPort < 1883) cfg.mqttPort = 1883;
              saveConfig();
              break;
            case NETWORK_RECONNECT:
              reconnectWiFi();
              break;
            case NETWORK_COUNT:
              currentPage = PAGE_MAIN_DASHBOARD;
              break;
          }
          break;
        case PAGE_SYSTEM:
          switch (systemPage) {
            case SYSTEM_SAVE:
              saveConfig();
              if (wifiConnected) publishConfig();
              break;
            case SYSTEM_CLEAR_CONFIG:
              prefs.clear();
              ESP.restart();
              break;
            case SYSTEM_RESTART:
              ESP.restart();
              break;
            case SYSTEM_COUNT:
              currentPage = PAGE_MAIN_DASHBOARD;
              break;
          }
          break;
      }
      renderPage();
      delay(200);
    }
  }

  delay(50);
}