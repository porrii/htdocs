// Configuración de la aplicación
const CONFIG = {
  // URL del backend - Cambiar según el entorno
  API_URL:
    window.location.hostname === "localhost"
      ? "http://localhost/fsa-studio-app/backend"
      : "https://tu-ngrok-url.ngrok.io/backend",

  // Configuración de almacenamiento
  STORAGE_KEYS: {
    TOKEN: "fsa_token",
    USER: "fsa_user",
    THEME: "fsa_theme",
  },

  // Configuración de notificaciones
  NOTIFICATION_HOURS_BEFORE: 24, // Horas antes de la cita para notificar

  // Configuración de la app
  APP_NAME: "FSA Studio",
  APP_VERSION: "1.0.0",
}

// Detectar si estamos en Capacitor
const isCapacitor = () => {
  return window.Capacitor !== undefined
}

// Exportar configuración
window.CONFIG = CONFIG
window.isCapacitor = isCapacitor
