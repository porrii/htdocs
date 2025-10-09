// Import the API module
const API = require("./api") // Assuming API is imported from a module named 'api'

// Gestión de citas
const Appointments = {
  // Obtener citas del usuario
  async getUserAppointments() {
    try {
      const response = await API.get("/api/appointments/get-user-appointments")
      return response.data
    } catch (error) {
      throw error
    }
  },

  // Crear nueva cita
  async createAppointment(data) {
    if (!window.Validators.futureDate(data.fecha)) {
      throw new Error("La fecha debe ser futura")
    }

    if (!window.Validators.businessHours(data.hora)) {
      throw new Error("La hora debe estar dentro del horario de atención (9:00 - 21:00)")
    }

    if (data.barbero_id) {
      const isAvailable = await window.Validators.barberAvailability(data.barbero_id, data.fecha, data.hora)
      if (!isAvailable) {
        throw new Error("El barbero no está disponible en ese horario")
      }
    }

    const noConflict = await window.Validators.appointmentConflict(data.barbero_id, data.fecha, data.hora)
    if (!noConflict) {
      throw new Error("Ya tienes una cita en ese horario")
    }

    try {
      const response = await API.post("/api/appointments/create", data)

      if (response.success && response.data) {
        const appointment = {
          ...data,
          id: response.data.id,
          servicio_nombre: data.servicio_nombre || "Servicio",
        }
        window.Notifications.scheduleAppointmentReminder(appointment)
      }

      return response
    } catch (error) {
      throw error
    }
  },

  // Actualizar cita (reprogramar)
  async updateAppointment(id, fecha, hora) {
    if (!window.Validators.futureDate(fecha)) {
      throw new Error("La fecha debe ser futura")
    }

    if (!window.Validators.businessHours(hora)) {
      throw new Error("La hora debe estar dentro del horario de atención (9:00 - 21:00)")
    }

    try {
      const response = await API.put("/api/appointments/update", {
        id,
        fecha,
        hora,
      })
      return response
    } catch (error) {
      throw error
    }
  },

  // Cancelar cita
  async cancelAppointment(id) {
    try {
      const response = await API.put("/api/appointments/cancel", { id })
      return response
    } catch (error) {
      throw error
    }
  },

  // Obtener servicios disponibles
  async getServices() {
    try {
      const response = await API.get("/api/services/get-all")
      return response.data
    } catch (error) {
      throw error
    }
  },

  // Obtener barberos disponibles
  async getBarbers() {
    try {
      const response = await API.get("/api/barbers/get-all")
      return response.data
    } catch (error) {
      throw error
    }
  },

  // Verificar disponibilidad de barbero
  async checkAvailability(barberoId, fecha, hora) {
    try {
      const response = await API.get(
        `/api/barbers/check-availability?barbero_id=${barberoId}&fecha=${fecha}&hora=${hora}`,
      )
      return response.data.available
    } catch (error) {
      throw error
    }
  },

  // Obtener horarios disponibles para un día
  getAvailableTimeSlots(date) {
    const slots = []
    const startHour = 10 // 10:00 AM
    const endHour = 20 // 8:00 PM

    for (let hour = startHour; hour < endHour; hour++) {
      slots.push(`${String(hour).padStart(2, "0")}:00:00`)
      slots.push(`${String(hour).padStart(2, "0")}:30:00`)
    }

    return slots
  },

  // Formatear estado de cita
  getStatusText(status) {
    const statusMap = {
      pendiente: "Pendiente",
      confirmada: "Confirmada",
      completada: "Completada",
      cancelada: "Cancelada",
    }
    return statusMap[status] || status
  },

  // Obtener color de estado
  getStatusColor(status) {
    const colorMap = {
      pendiente: "var(--color-warning)",
      confirmada: "var(--color-success)",
      completada: "var(--color-text-muted)",
      cancelada: "var(--color-error)",
    }
    return colorMap[status] || "var(--color-text-secondary)"
  },
}

window.Appointments = Appointments
