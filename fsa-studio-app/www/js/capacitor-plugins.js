// Integración de plugins de Capacitor
const CapacitorPlugins = {
  isCapacitor: false,
  LocalNotifications: null,
  PushNotifications: null,
  Network: null,

  // Inicializar plugins
  async init() {
    // Verificar si Capacitor está disponible
    if (window.Capacitor) {
      this.isCapacitor = true
      console.log("[v0] Capacitor detectado, inicializando plugins...")

      try {
        // Importar plugins
        const { LocalNotifications } = window.Capacitor.Plugins
        const { PushNotifications } = window.Capacitor.Plugins
        const { Network } = window.Capacitor.Plugins

        this.LocalNotifications = LocalNotifications
        this.PushNotifications = PushNotifications
        this.Network = Network

        // Inicializar notificaciones locales
        await this.initLocalNotifications()

        // Inicializar monitoreo de red
        await this.initNetworkMonitoring()

        console.log("[v0] Plugins de Capacitor inicializados correctamente")
      } catch (error) {
        console.error("[v0] Error inicializando plugins de Capacitor:", error)
      }
    } else {
      console.log("[v0] Capacitor no detectado, ejecutando en navegador")
    }
  },

  // Inicializar notificaciones locales
  async initLocalNotifications() {
    if (!this.LocalNotifications) return

    try {
      // Solicitar permisos
      const permission = await this.LocalNotifications.requestPermissions()

      if (permission.display === "granted") {
        console.log("[v0] Permisos de notificaciones locales concedidos")
      }
    } catch (error) {
      console.error("[v0] Error solicitando permisos de notificaciones:", error)
    }
  },

  // Programar notificación local
  async scheduleNotification(title, body, scheduleAt) {
    if (!this.LocalNotifications) {
      console.log("[v0] LocalNotifications no disponible")
      return
    }

    try {
      await this.LocalNotifications.schedule({
        notifications: [
          {
            title: title,
            body: body,
            id: Date.now(),
            schedule: { at: new Date(scheduleAt) },
            sound: "default",
            smallIcon: "ic_stat_icon_config_sample",
            iconColor: "#D4AF37",
          },
        ],
      })
      console.log("[v0] Notificación programada:", title)
    } catch (error) {
      console.error("[v0] Error programando notificación:", error)
    }
  },

  // Programar recordatorio de cita
  async scheduleAppointmentReminder(appointment) {
    if (!this.isCapacitor) return

    const appointmentDate = new Date(`${appointment.fecha}T${appointment.hora}`)
    const now = new Date()

    // Recordatorio 24 horas antes
    const oneDayBefore = new Date(appointmentDate.getTime() - 24 * 60 * 60 * 1000)
    if (oneDayBefore > now) {
      await this.scheduleNotification(
        "Recordatorio de Cita - FSA Studio",
        `Tienes una cita mañana a las ${window.Utils.formatTime(appointment.hora)} para ${appointment.servicio_nombre}`,
        oneDayBefore,
      )
    }

    // Recordatorio 1 hora antes
    const oneHourBefore = new Date(appointmentDate.getTime() - 60 * 60 * 1000)
    if (oneHourBefore > now) {
      await this.scheduleNotification(
        "Recordatorio de Cita - FSA Studio",
        `Tu cita es en 1 hora: ${appointment.servicio_nombre}`,
        oneHourBefore,
      )
    }
  },

  // Inicializar monitoreo de red
  async initNetworkMonitoring() {
    if (!this.Network) return

    try {
      // Obtener estado inicial
      const status = await this.Network.getStatus()
      console.log("[v0] Estado de red:", status)

      // Escuchar cambios de red
      this.Network.addListener("networkStatusChange", (status) => {
        console.log("[v0] Cambio de red:", status)

        if (status.connected) {
          window.Utils.showToast("Conexión restaurada", "success")
        } else {
          window.Utils.showToast("Sin conexión a internet", "error")
        }
      })
    } catch (error) {
      console.error("[v0] Error monitoreando red:", error)
    }
  },

  // Obtener estado de red
  async getNetworkStatus() {
    if (!this.Network) {
      return { connected: navigator.onLine }
    }

    try {
      return await this.Network.getStatus()
    } catch (error) {
      console.error("[v0] Error obteniendo estado de red:", error)
      return { connected: navigator.onLine }
    }
  },
}

window.CapacitorPlugins = CapacitorPlugins
window.isCapacitor = () => CapacitorPlugins.isCapacitor
