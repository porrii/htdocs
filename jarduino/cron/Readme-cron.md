Configuración del Cron Job
Para que el sistema funcione, necesitas configurar un cron job en tu servidor:

Linux/Unix (usando crontab)
    # Editar crontab
    crontab -e

    # Añadir esta línea para ejecutar cada minuto
    * * * * * /usr/bin/php /ruta/completa/a/tu/proyecto/cron_jobs.php > /dev/null 2>&1

    # Para debug (recibe emails con output)
    * * * * * /usr/bin/php /ruta/completa/a/tu/proyecto/cron_jobs.php


Windows (usando Task Scheduler)
    Abre "Programador de tareas"
    Crea una tarea básica
    Configura para ejecutar cada minuto
    Acción: "Iniciar un programa"
    Programa: php.exe
    Argumentos: -f "C:\ruta\a\tu\proyecto\cron_jobs.php"

Verificación del Cron Job
    # Ver logs de cron
    tail -f /var/log/syslog | grep CRON

    # Verificar si el script se está ejecutando
    ps aux | grep cron_jobs.php

    # Ejecutar manualmente
    php cron_jobs.php

