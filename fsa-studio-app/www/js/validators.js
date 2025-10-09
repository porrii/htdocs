// Sistema de validaciones
const Validators = {
  // Validar email
  email(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return regex.test(email)
  },

  // Validar teléfono
  phone(phone) {
    // Acepta formatos: +34 600 000 000, 600000000, etc.
    const regex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s.]?[(]?[0-9]{1,4}[)]?[-\s.]?[0-9]{1,9}$/
    return regex.test(phone.replace(/\s/g, ""))
  },

  // Validar contraseña
  password(password) {
    return password.length >= 6
  },

  // Validar fecha (no puede ser en el pasado)
  futureDate(dateString) {
    const date = new Date(dateString)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return date >= today
  },

  // Validar hora de negocio (9:00 - 21:00)
  businessHours(timeString) {
    const [hours, minutes] = timeString.split(":").map(Number)
    const totalMinutes = hours * 60 + minutes
    const openTime = 9 * 60 // 9:00
    const closeTime = 21 * 60 // 21:00
    return totalMinutes >= openTime && totalMinutes <= closeTime
  },

  // Validar disponibilidad de barbero
  async barberAvailability(barberId, date, time) {
    if (!barberId) return true // Sin preferencia de barbero

    try {
      const response = await window.API.get(
        `/barbers/check-availability.php?barber_id=${barberId}&fecha=${date}&hora=${time}`,
      )
      return response.success && response.data.available
    } catch (error) {
      console.error("Error checking barber availability:", error)
      return false
    }
  },

  // Validar URL de YouTube
  youtubeUrl(url) {
    const regex = /^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w-]+/
    return regex.test(url)
  },

  // Validar URL de imagen
  imageUrl(url) {
    if (!url) return true // URL opcional
    const regex = /^https?:\/\/.+\.(jpg|jpeg|png|gif|webp|svg)$/i
    return regex.test(url)
  },

  // Validar que no haya conflictos de horario
  async appointmentConflict(barberId, date, time, excludeAppointmentId = null) {
    try {
      const appointments = await window.Appointments.getUserAppointments()

      // Filtrar citas del mismo día y barbero
      const conflicts = appointments.filter((apt) => {
        if (excludeAppointmentId && apt.id === excludeAppointmentId) {
          return false // Excluir la cita que se está editando
        }

        if (apt.estado === "cancelada" || apt.estado === "completada") {
          return false // Ignorar citas canceladas o completadas
        }

        if (apt.fecha !== date) {
          return false // Diferente día
        }

        if (barberId && apt.barbero_id && apt.barbero_id !== barberId) {
          return false // Diferente barbero
        }

        // Verificar si hay conflicto de horario
        return apt.hora === time
      })

      return conflicts.length === 0
    } catch (error) {
      console.error("Error checking appointment conflicts:", error)
      return true // En caso de error, permitir la cita
    }
  },
}

window.Validators = Validators
