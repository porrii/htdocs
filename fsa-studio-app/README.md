# FSA Studio - Aplicación Móvil para Barbería

Aplicación móvil completa desarrollada con Capacitor para la gestión integral de citas, servicios, videos y herramientas de FSA Studio.

## 🚀 Tecnologías

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Mobile**: Capacitor 5.0
- **Diseño**: Sistema de diseño personalizado con tema oscuro elegante

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Node.js 16+ (para Capacitor)
- Apache/Nginx con mod_rewrite habilitado
- Android Studio (para compilar APK)
- Xcode (para compilar iOS, solo macOS)

## 🛠️ Instalación

### Backend

1. Importar la base de datos:
\`\`\`bash
mysql -u root -p < backend/database/schema.sql
\`\`\`

2. Configurar la conexión en `backend/config/database.php`:
\`\`\`php
private $host = "localhost";
private $db_name = "fsa_studio";
private $username = "tu_usuario";
private $password = "tu_contraseña";
\`\`\`

3. Configurar el servidor web para apuntar a la carpeta `backend/`

4. Asegurarse de que el servidor tenga habilitado:
   - mod_rewrite (Apache)
   - CORS headers
   - PHP JSON extension

### Frontend

1. Instalar dependencias:
\`\`\`bash
npm install
\`\`\`

2. Configurar la URL del backend en `www/js/config.js`:
\`\`\`javascript
API_URL: 'https://tu-servidor.com/backend'
\`\`\`

3. Para desarrollo local:
\`\`\`bash
npm run dev
\`\`\`
Abre http://localhost:8080

## 📱 Compilar para Móvil

### Android (APK)

1. Sincronizar archivos:
\`\`\`bash
npm run android
\`\`\`

2. Esto abrirá Android Studio donde podrás:
   - Compilar en modo debug para pruebas
   - Generar APK firmado para producción

Para instrucciones detalladas, consulta [BUILD_INSTRUCTIONS.md](BUILD_INSTRUCTIONS.md)

### iOS (IPA)

1. Sincronizar archivos:
\`\`\`bash
npm run ios
\`\`\`

2. Esto abrirá Xcode donde podrás compilar para dispositivos iOS

## 🔐 Credenciales por Defecto

- **Admin**:
  - Email: admin@fsastudio.com
  - Contraseña: admin123

- **Cliente de prueba**:
  - Email: cliente@test.com
  - Contraseña: test123

## 🌐 Desarrollo con ngrok

Para probar con ngrok:

1. Iniciar ngrok:
\`\`\`bash
ngrok http 80
\`\`\`

2. Actualizar la URL en `www/js/config.js` con la URL de ngrok

## 📝 Estructura del Proyecto

\`\`\`
fsa-studio-app/
├── backend/
│   ├── api/
│   │   ├── auth/          # Autenticación y registro
│   │   ├── appointments/  # Gestión de citas
│   │   ├── barbers/       # Gestión de barberos
│   │   ├── services/      # Servicios disponibles
│   │   ├── videos/        # Videos de trabajos
│   │   ├── tools/         # Herramientas profesionales
│   │   └── admin/         # Panel de administración
│   ├── config/            # Configuración de BD
│   ├── database/          # Esquemas SQL
│   ├── middleware/        # Autenticación JWT
│   └── utils/             # Utilidades
├── www/
│   ├── css/
│   │   └── styles.css     # Estilos personalizados
│   ├── js/
│   │   ├── config.js      # Configuración
│   │   ├── api.js         # Cliente API
│   │   ├── auth.js        # Autenticación
│   │   ├── appointments.js # Gestión de citas
│   │   ├── admin.js       # Funciones admin
│   │   ├── videos.js      # Gestión de videos
│   │   ├── tools.js       # Gestión de herramientas
│   │   ├── validators.js  # Validaciones
│   │   ├── notifications.js # Notificaciones web
│   │   ├── connection.js  # Detección de conexión
│   │   ├── capacitor-plugins.js # Plugins móviles
│   │   ├── router.js      # Enrutamiento SPA
│   │   └── app.js         # Inicialización
│   └── index.html
├── android/               # Proyecto Android
├── ios/                   # Proyecto iOS
├── capacitor.config.json  # Configuración Capacitor
├── package.json
├── BUILD_INSTRUCTIONS.md  # Guía de compilación
└── README.md
\`\`\`

## ✨ Características Implementadas

### Autenticación y Usuarios
- ✅ Sistema de autenticación con JWT
- ✅ Registro de nuevos usuarios
- ✅ Sesión persistente con localStorage
- ✅ Roles de usuario (admin/cliente)
- ✅ Validación de email, contraseña y teléfono

### Gestión de Citas
- ✅ Crear nuevas citas
- ✅ Ver historial de citas
- ✅ Reprogramar citas existentes
- ✅ Cancelar citas
- ✅ Selección de servicio y barbero
- ✅ Verificación de disponibilidad
- ✅ Estados de cita (pendiente, confirmada, completada, cancelada)

### Panel de Administración
- ✅ Dashboard con estadísticas
- ✅ Gestión de barberos (CRUD completo)
- ✅ Ver todas las citas de clientes
- ✅ Gestión de información de la barbería
- ✅ Gestión de videos (CRUD completo)
- ✅ Gestión de herramientas (CRUD completo)
- ✅ Configuración de horarios y redes sociales

### Videos y Herramientas
- ✅ Galería de videos de YouTube
- ✅ Catálogo de herramientas profesionales
- ✅ Sistema de activación/desactivación
- ✅ Gestión desde panel admin

### Notificaciones y Validaciones
- ✅ Notificaciones web push
- ✅ Notificaciones locales en móvil (Capacitor)
- ✅ Recordatorios de citas (24h y 1h antes)
- ✅ Validaciones de formularios
- ✅ Validación de disponibilidad de barberos
- ✅ Detección de conflictos de horario
- ✅ Monitoreo de conexión a internet

### Diseño y UX
- ✅ Diseño responsive y moderno
- ✅ Tema oscuro profesional con dorado
- ✅ Navegación inferior tipo app móvil
- ✅ Splash screen animado
- ✅ Toasts para feedback
- ✅ Modales para formularios
- ✅ Estados de carga (spinners)
- ✅ Manejo de errores

### Capacitor y Móvil
- ✅ Configuración completa de Capacitor
- ✅ Soporte para Android e iOS
- ✅ Notificaciones locales
- ✅ Monitoreo de red
- ✅ Splash screen nativo
- ✅ Listo para compilar APK/IPA

## 🎯 Funcionalidades por Rol

### Cliente
- Ver y gestionar sus propias citas
- Agendar nuevas citas con barbero preferido
- Ver videos de trabajos realizados
- Ver catálogo de herramientas
- Recibir notificaciones de recordatorio
- Actualizar su perfil

### Administrador
- Todo lo del cliente, más:
- Dashboard con estadísticas de negocio
- Gestionar barberos del equipo
- Ver todas las citas de todos los clientes
- Gestionar información de la barbería
- Administrar videos y herramientas
- Configurar horarios y contacto

## 🔒 Seguridad

- Autenticación JWT con tokens seguros
- Validación de datos en frontend y backend
- Protección contra SQL injection (prepared statements)
- CORS configurado correctamente
- Middleware de autenticación en rutas protegidas
- Validación de roles de usuario
- Sanitización de inputs

## 🚀 Despliegue

### Backend
1. Subir archivos a servidor con PHP y MySQL
2. Configurar base de datos
3. Asegurar que el servidor use HTTPS
4. Configurar CORS para permitir la app móvil

### App Móvil
1. Actualizar `API_URL` en config.js con URL de producción
2. Compilar APK/IPA siguiendo [BUILD_INSTRUCTIONS.md](BUILD_INSTRUCTIONS.md)
3. Publicar en Google Play Store / Apple App Store

## 📊 Base de Datos

Tablas principales:
- `usuarios` - Clientes y administradores
- `barberos` - Equipo de barberos
- `servicios` - Servicios ofrecidos
- `citas` - Reservas de clientes
- `videos` - Galería de trabajos
- `herramientas` - Catálogo de equipamiento
- `barberia_info` - Información del negocio

## 🐛 Solución de Problemas

### Error de conexión al backend
- Verificar que la URL en `config.js` sea correcta
- Asegurar que el servidor backend esté corriendo
- Verificar configuración CORS en el servidor

### Error al compilar APK
- Verificar que Android Studio esté instalado
- Ejecutar `npx cap sync android`
- Limpiar proyecto: `cd android && ./gradlew clean`

### Notificaciones no funcionan
- Verificar permisos en el dispositivo
- En Android, verificar que Google Services esté configurado
- En iOS, verificar certificados de push

## 📚 Documentación Adicional

- [BUILD_INSTRUCTIONS.md](BUILD_INSTRUCTIONS.md) - Guía detallada de compilación
- [Capacitor Docs](https://capacitorjs.com/docs) - Documentación oficial de Capacitor

## 🤝 Contribuir

Este es un proyecto privado para FSA Studio. Para cambios o mejoras, contactar al equipo de desarrollo.

## 📄 Licencia

MIT - FSA Studio 2025

## 📞 Soporte

Para soporte técnico o consultas:
- Email: soporte@fsastudio.com
- WhatsApp: [Configurar en panel admin]
- Instagram: @fsastudio
