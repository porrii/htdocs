📦 Librerías necesarias (Arduino IDE)

    WiFiEspAT (de Arduino)
    PubSubClient (Nick O’Leary)
    ArduinoJson (v6+)
    DHT sensor library (Adafruit) + Adafruit Unified Sensor
    U8g2 (para LCD 12864)
    LiquidCrystal (solo si usas 20x4 paralelo, incluida por defecto)
    EEPROM

🛠️ Cableado de referencia

    ESP-01S: usar SoftwareSerial (UNO pins 10=RX, 11=TX) + conversor de nivel para RX del ESP y fuente 3.3 V estable.
    Relé bomba: RELAY_PIN (activo en HIGH o LOW según tu módulo; ajustable).
    DHT11: DHT_PIN.
    Humedad suelo (analógico): SOIL_PIN = A0. Calibra SOIL_WET_RAW y SOIL_DRY_RAW.
    Encoder: ENC_A, ENC_B, ENC_BTN (con resistencias pull-up internas).
    Pantalla:
        12864 ST7920 (Reprap Smart Controller): usa U8g2 por SPI software (LCD_CLK, LCD_MOSI, LCD_CS).
        o 20x4 2004 HD44780 paralelo**: define DISPLAY_2004 y usa pines RS, EN, D4..D7.
        Cambia pines en la cabecera según tu shield/controlador.

🔧 Configura antes de compilar

    WIFI_SSID, WIFI_PASS
    MQTT_SERVER, MQTT_PORT (1883), DEVICE_ID
    RELAY_ACTIVE_HIGH según tu módulo.
    DISPLAY_12864 o DISPLAY_2004 (elige una).

🔌 Notas de integración con tu web

    Mantén tu broker MQTT escuchando en 1883 (TCP) y en 9001 (WebSocket).
        La web sigue conectando por WebSocket a 9001.
        El Arduino se conecta por TCP a 1883 (lo típico con PubSubClient).
    Los tópicos y formatos JSON coinciden con tu simulador:
        Publica sensores → sensores
        {"id":"ARD1","t":23.4,"h":55.0,"soil":42,"pump":false}
        Publica estado → estado/ARD1 con {"id":"ARD1","online":true}
        Recibe:
        riego → {"device":"ARD1","duration":5}
        stop → {"device":"ARD1"}
        config/auto → {"device":"ARD1","enabled":true}
        config/threshold → {"device":"ARD1","threshold":40}
    Cuando modificas desde pantalla:
        Cambiar Auto → publica en config/auto.
        Cambiar Umbral → publica en config/threshold.
        La web debe escuchar esos tópicos y actualizar su UI.

✅ Robustez incluida

    Reconexión a WiFi y MQTT en bucle (con backoff simple).
    Sin bloqueos (pump por tiempo usando millis()).
    EEPROM + CRC para evitar configuraciones corruptas.
    Watchdog 2 s (reinicia si se cuelga).
    Debounce del botón del encoder.

🧪 Siguientes pasos sugeridos

    Ajusta pines y elige display (12864 o 2004) en las #define.
    Calibra SOIL_WET_RAW y SOIL_DRY_RAW.
    Cambia tus credenciales y la IP del broker.
    Comprueba que tu broker escucha en 1883.
    Si tu relé activa en LOW, pon RELAY_ACTIVE_HIGH = false.