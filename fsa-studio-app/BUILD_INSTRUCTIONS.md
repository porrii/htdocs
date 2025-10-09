# FSA Studio - Instrucciones de Compilación

## Requisitos Previos

### Para Android
- Node.js 16 o superior
- Android Studio (última versión)
- JDK 11 o superior
- Android SDK (API 22 o superior)

### Para iOS (solo en macOS)
- Xcode 14 o superior
- CocoaPods
- Cuenta de desarrollador de Apple

## Instalación

1. **Instalar dependencias**
\`\`\`bash
npm install
\`\`\`

2. **Sincronizar proyecto con Capacitor**
\`\`\`bash
npx cap sync
\`\`\`

## Compilar para Android

### Desarrollo
\`\`\`bash
# Sincronizar cambios y abrir Android Studio
npm run android
\`\`\`

### Producción (APK)

1. **Abrir Android Studio**
\`\`\`bash
npm run android
\`\`\`

2. **En Android Studio:**
   - Ve a `Build > Generate Signed Bundle / APK`
   - Selecciona `APK`
   - Crea o selecciona tu keystore
   - Completa la información de firma
   - Selecciona `release` como build variant
   - Click en `Finish`

3. **El APK se generará en:**
\`\`\`
android/app/release/app-release.apk
\`\`\`

### Generar Keystore (primera vez)

\`\`\`bash
keytool -genkey -v -keystore fsa-studio.keystore -alias fsa-studio -keyalg RSA -keysize 2048 -validity 10000
\`\`\`

Guarda el keystore en un lugar seguro y recuerda la contraseña.

## Compilar para iOS

### Desarrollo
\`\`\`bash
# Sincronizar cambios y abrir Xcode
npm run ios
\`\`\`

### Producción (IPA)

1. **Abrir Xcode**
\`\`\`bash
npm run ios
\`\`\`

2. **En Xcode:**
   - Selecciona tu equipo de desarrollo
   - Configura el Bundle Identifier único
   - Ve a `Product > Archive`
   - Una vez archivado, click en `Distribute App`
   - Sigue el asistente para generar el IPA

## Configuración del Backend

Antes de compilar, asegúrate de configurar la URL del backend en `www/js/config.js`:

\`\`\`javascript
const CONFIG = {
  API_URL: 'https://tu-servidor.com/backend/api',
  // ... resto de configuración
}
\`\`\`

## Permisos

### Android
Los permisos ya están configurados en `android/app/src/main/AndroidManifest.xml`:
- Internet
- Notificaciones
- Estado de red

### iOS
Los permisos están configurados en `ios/App/App/Info.plist`:
- Notificaciones locales
- Acceso a red

## Iconos y Splash Screen

Los recursos visuales están en:
- Android: `android/app/src/main/res/`
- iOS: `ios/App/App/Assets.xcassets/`

Para generar iconos automáticamente:
\`\`\`bash
npx capacitor-assets generate
\`\`\`

## Testing

### En navegador (desarrollo)
\`\`\`bash
npm run dev
\`\`\`
Abre http://localhost:8080

### En dispositivo físico

**Android:**
1. Habilita "Depuración USB" en tu dispositivo
2. Conecta el dispositivo
3. En Android Studio, selecciona tu dispositivo y click en Run

**iOS:**
1. Conecta tu iPhone/iPad
2. En Xcode, selecciona tu dispositivo
3. Click en el botón Play

## Solución de Problemas

### Error: "SDK location not found"
Crea el archivo `android/local.properties`:
\`\`\`
sdk.dir=/ruta/a/tu/Android/sdk
\`\`\`

### Error de Gradle
\`\`\`bash
cd android
./gradlew clean
cd ..
npx cap sync android
\`\`\`

### Error de CocoaPods (iOS)
\`\`\`bash
cd ios/App
pod install
cd ../..
\`\`\`

## Actualizar la App

Después de hacer cambios en el código:

\`\`\`bash
# Sincronizar cambios
npx cap sync

# O sincronizar solo Android/iOS
npx cap sync android
npx cap sync ios
\`\`\`

## Publicación

### Google Play Store
1. Genera el APK firmado (release)
2. Ve a Google Play Console
3. Crea una nueva aplicación
4. Sube el APK
5. Completa la información de la tienda
6. Envía para revisión

### Apple App Store
1. Genera el IPA desde Xcode
2. Ve a App Store Connect
3. Crea una nueva app
4. Sube el IPA usando Xcode o Transporter
5. Completa la información de la tienda
6. Envía para revisión

## Notas Importantes

- **Versión**: Actualiza la versión en `package.json` y `capacitor.config.json` antes de cada release
- **Backend**: Asegúrate de que el backend esté en producción y accesible
- **HTTPS**: El backend debe usar HTTPS en producción
- **Permisos**: Solicita solo los permisos necesarios
- **Testing**: Prueba exhaustivamente antes de publicar

## Soporte

Para más información sobre Capacitor:
- Documentación: https://capacitorjs.com/docs
- Guías de Android: https://capacitorjs.com/docs/android
- Guías de iOS: https://capacitorjs.com/docs/ios
