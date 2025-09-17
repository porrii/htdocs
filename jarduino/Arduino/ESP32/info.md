🛠️ Componentes necesarios
    ESP32 DevKit V1 (38 pines).
    DHT11 sensor de temperatura/humedad.
    Sensor de humedad del suelo (analógico).
    Pantalla OLED 0.96" I2C (SSD1306).
    Módulo relé 5V de 2 canales (solo usamos 1 canal, puedes ampliar).
    Mini bomba de agua 5V.
    3 botones pulsadores (para navegar menú: UP, DOWN, OK).
    Resistencias 10kΩ (si los pulsadores no son tipo pull-down integrados).
    Protoboard y cables dupont macho-hembra.
    Fuente de alimentación 5V 2A (ESP32 + bomba).

⚡ Conexiones exactas según el código
📌 ESP32 ↔ Pantalla OLED (I2C)
    OLED VCC → 3.3V del ESP32
    OLED GND → GND
    OLED SDA → GPIO21
    OLED SCL → GPIO22

📌 ESP32 ↔ Sensor DHT11
    DHT11 VCC → 3.3V (o 5V, depende del módulo; ambos valen para ESP32)
    DHT11 GND → GND
    DHT11 DATA → GPIO13
(Si tu módulo DHT11 no tiene resistencia pull-up, pon una de 10kΩ entre DATA y VCC)

📌 ESP32 ↔ Sensor de humedad del suelo (analógico)
    Sensor VCC → 3.3V
    Sensor GND → GND
    Sensor AOUT → GPIO34 (entrada ADC1)
(⚠ Importante: El ESP32 NO soporta más de 3.3V en sus pines. Si tu sensor de humedad da salida de 0–5V, debes usar un divisor resistivo con 2 resistencias para bajar a 0–3.3V)

📌 ESP32 ↔ Relé (para bomba)
    Relé VCC → 5V
    Relé GND → GND
    Relé IN1 → GPIO26
    Relé COM → 5V
    Relé NO → Bomba + (positivo de la bomba)
    Bomba – (negativo) → GND
(Cuando el relé se activa, conecta 5V al positivo de la bomba y la enciende)

📌 ESP32 ↔ Botones
Los tres botones están configurados con INPUT_PULLUP → por lo tanto, conectan a GND cuando se pulsan.
    Botón UP → GPIO32
    Botón DOWN → GPIO33
    Botón OK → GPIO25

Cada botón: un pin al GPIO y el otro a GND
(No necesitas resistencias externas, el ESP32 ya activa la pull-up interna).

🔋 Opciones de alimentación
    Si usas un módulo de alimentación de protoboard 5V/3.3V:
        Conecta la salida 5V → pin VIN (o 5V) del ESP32.
        El regulador de la placa baja a 3.3V internamente para el chip.
        No uses la salida 3.3V del módulo para alimentar el ESP32 directamente → no tiene suficiente corriente y te arriesgas a inestabilidad.

    Sensores y periféricos:
        OLED I2C y sensor de humedad → 3.3V (desde el pin 3.3V del ESP32).
        DHT11 → puede ir a 3.3V o 5V (mejor 3.3V si lo compartes con el ESP32).
        Relé + bomba → 5V (desde el módulo de alimentación).

✅ Recomendación práctica para tu proyecto
    Módulo de alimentación en protoboard a 5V (desde cargador USB o fuente externa).
    Salida 5V → pin VIN del ESP32.
    De ese mismo 5V alimentas el módulo relé y la bomba.
    Del pin 3.3V del ESP32 alimentas OLED, DHT11 y sensor de humedad.

