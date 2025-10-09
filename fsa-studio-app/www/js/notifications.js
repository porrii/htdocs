// Sistema de notificaciones y recordatorios
const Notifications = {
  // Verificar si las notificaciones están soportadas
  isSupported() {
    return "Notification" in window
  },

  // Solicitar permiso para notificaciones
  async requestPermission() {
    if (!this.isSupported()) {
      return false
    }

    try {
      const permission = await Notification.requestPermission()
      return permission === "granted"
    } catch (error) {
      console.error("Error requesting notification permission:", error)
      return false
    }
  },

  // Mostrar notificación
  show(title, options = {}) {
    if (!this.isSupported() || Notification.permission !== "granted") {
      return null
    }

    try {
      return new Notification(title, {
        icon: "/icon.png",
        badge: "/icon.png",
        ...options,
      })
    } catch (error) {
      console.error("Error showing notification:", error)
      return null
    }
  },

  // Programar recordatorio de cita
  scheduleAppointmentReminder(appointment) {
    // Calcular tiempo hasta la cita
    const appointmentDate = new Date(`${appointment.fecha}T${appointment.hora}`)
    const now = new Date()
    const timeUntilAppointment = appointmentDate.getTime() - now.getTime()

    // Recordatorio 24 horas antes
    const oneDayBefore = timeUntilAppointment - 24 * 60 * 60 * 1000
    if (oneDayBefore > 0) {
      setTimeout(() => {
        this.show("Recordatorio de Cita - FSA Studio", {
          body: `Tienes una cita mañana a las ${window.Utils.formatTime(appointment.hora)} para ${appointment.servicio_nombre}`,
          tag: `appointment-${appointment.id}-1day`,
          requireInteraction: true,
        })
      }, oneDayBefore)
    }

    // Recordatorio 1 hora antes
    const oneHourBefore = timeUntilAppointment - 60 * 60 * 1000
    if (oneHourBefore > 0) {
      setTimeout(() => {
        this.show("Recordatorio de Cita - FSA Studio", {
          body: `Tu cita es en 1 hora: ${appointment.servicio_nombre}`,
          tag: `appointment-${appointment.id}-1hour`,
          requireInteraction: true,
        })
      }, oneHourBefore)
    }
  },

  // Inicializar notificaciones para el usuario
  async init() {
    if (!this.isSupported()) {
      console.log("Notifications not supported")
      return
    }

    // Solicitar permiso si aún no se ha hecho
    if (Notification.permission === "default") {
      await this.requestPermission()
    }

    // Cargar citas próximas y programar recordatorios
    try {
      const appointments = await window.Appointments.getUserAppointments()
      const upcomingAppointments = appointments.filter((apt) => {
        const aptDate = new Date(`${apt.fecha}T${apt.hora}`)
        return aptDate > new Date() && (apt.estado === "pendiente" || apt.estado === "confirmada")
      })

      upcomingAppointments.forEach((apt) => {
        this.scheduleAppointmentReminder(apt)
      })
    } catch (error) {
      console.error("Error initializing notifications:", error)
    }
  },
}

window.Notifications = Notifications
