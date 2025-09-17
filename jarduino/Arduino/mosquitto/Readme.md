🟢 1. Instalación de Mosquitto
    🔹 En Linux (Ubuntu/Debian)
        sudo apt update
        sudo apt install mosquitto mosquitto-clients -y

        mosquitto → el broker
        mosquitto-clients → utilidades (mosquitto_pub, mosquitto_sub)

        El servicio arranca automáticamente:
        sudo systemctl enable mosquitto
        sudo systemctl start mosquitto
        sudo systemctl status mosquitto

    🔹 En Windows

        Descarga el instalador oficial:
        👉 https://mosquitto.org/download/
        Instálalo en C:\Program Files\mosquitto

        Copia el archivo de ejemplo de configuración:
        C:\Program Files\mosquitto\mosquitto.conf

        Para ejecutarlo como servicio:
        net start mosquitto

        o bien iniciar manualmente:
        "C:\Program Files\mosquitto\mosquitto.exe" -c "C:\Program Files\mosquitto\mosquitto.conf"

🟢 2. Configuración básica del broker

    El archivo de configuración (mosquitto.conf) puede variar según la ruta:
    Linux → /etc/mosquitto/mosquitto.conf
    Windows → C:\Program Files\mosquitto\mosquitto.conf
    Recomiendo crear un archivo de configuración propio (ej. custom.conf) y enlazarlo:
    Linux → /etc/mosquitto/conf.d/custom.conf
    Windows → C:\mosquitto\custom.conf

🟢 mosquitto.conf:
    En Windows, cambia las rutas:

    password_file C:/mosquitto/passwd
    log_dest file C:/mosquitto/mosquitto.log

🟢 3. Crear usuarios y contraseñas
    🔹 En Linux
        sudo mosquitto_passwd -c /etc/mosquitto/passwd usuario1

        Te pedirá la contraseña.
        Agrega más usuarios sin -c (para no sobrescribir):
        sudo mosquitto_passwd /etc/mosquitto/passwd usuario2

    🔹 En Windows (cmd o PowerShell)
        "C:\Program Files\mosquitto\mosquitto_passwd.exe" -c C:\mosquitto\passwd usuario1

🟢 4. Probar el broker
    🔹 En Linux
        En una terminal:
        mosquitto_sub -h localhost -p 1883 -t "test" -u usuario1 -P contraseña

        En otra:
        mosquitto_pub -h localhost -p 1883 -t "test" -m "Hola MQTT" -u usuario1 -P contraseña

        Deberías ver Hola MQTT en la primera ventana.

    🔹 En Windows
        Igual, pero usando rutas completas:
        "C:\Program Files\mosquitto\mosquitto_sub.exe" -h localhost -p 1883 -t test -u usuario1 -P contraseña
        "C:\Program Files\mosquitto\mosquitto_pub.exe" -h localhost -p 1883 -t test -m "Hola MQTT" -u usuario1 -P contraseña

🟢 5. Puertos y firewall

    Linux:
    sudo ufw allow 1883
    sudo ufw allow 9001

    Windows:
    Permitir mosquitto.exe en el Firewall y abrir los puertos 1883 y 9001 en reglas de entrada.

🟢 6. (Opcional) Habilitar TLS/SSL

    Si quieres acceso seguro desde fuera de la LAN, necesitas certificados (ej. con Let’s Encrypt en Linux):
    listener 8883
    protocol mqtt
    cafile /etc/mosquitto/certs/ca.crt
    certfile /etc/mosquitto/certs/server.crt
    keyfile /etc/mosquitto/certs/server.key
    require_certificate false

    En Windows, lo mismo pero con rutas tipo C:/mosquitto/certs/....

🟢 7. Servicios y logs

    Linux:
    sudo systemctl restart mosquitto
    sudo journalctl -u mosquitto -f

    Windows:
    Logs en C:\mosquitto\mosquitto.log

    Reinicio con:
    net stop mosquitto
    net start mosquitto

👉 Con esta configuración tendrás:

    MQTT TCP en 1883 (Arduino UNO/ESP01)
    WebSocket en 9001 (tu web en navegador)
    Usuarios/contraseñas seguras
    Logs y persistencia activada

    