// Inicialización de la aplicación
document.addEventListener("DOMContentLoaded", async () => {
  console.log("[v0] FSA Studio App iniciando...")

  // Importar las variables necesarias
  const Router = window.Router
  const Auth = window.Auth
  const Utils = window.Utils
  const Connection = window.Connection
  const Notifications = window.Notifications
  const CapacitorPlugins = window.CapacitorPlugins

  await CapacitorPlugins.init()

  Connection.init()
  console.log("[v0] Sistema de conexión inicializado")

  // Ocultar splash screen después de 2 segundos
  setTimeout(() => {
    const splashScreen = document.getElementById("splash-screen")
    const app = document.getElementById("app")

    splashScreen.style.display = "none"
    app.style.display = "block"

    console.log("[v0] Splash screen ocultado")
  }, 2000)

  // Inicializar navegación
  const navItems = document.querySelectorAll(".nav-item")
  navItems.forEach((item) => {
    item.addEventListener("click", () => {
      const page = item.dataset.page
      Router.navigate(page)
    })
  })

  // Verificar autenticación
  if (Auth.isAuthenticated()) {
    console.log("[v0] Usuario autenticado, verificando token...")
    const isValid = await Auth.verifyToken()

    if (isValid) {
      console.log("[v0] Token válido, navegando a home")

      setTimeout(async () => {
        if (CapacitorPlugins.isCapacitor) {
          // Usar notificaciones de Capacitor
          const appointments = await window.Appointments.getUserAppointments()
          const upcomingAppointments = appointments.filter((apt) => {
            const aptDate = new Date(`${apt.fecha}T${apt.hora}`)
            return aptDate > new Date() && (apt.estado === "pendiente" || apt.estado === "confirmada")
          })

          for (const apt of upcomingAppointments) {
            await CapacitorPlugins.scheduleAppointmentReminder(apt)
          }
        } else {
          // Usar notificaciones web
          await Notifications.init()
        }
        console.log("[v0] Sistema de notificaciones inicializado")
      }, 2500)

      setTimeout(() => {
        Router.navigate("home")
      }, 2000)
    } else {
      console.log("[v0] Token inválido, navegando a login")
      setTimeout(() => {
        Router.navigate("login")
      }, 2000)
    }
  } else {
    console.log("[v0] Usuario no autenticado, navegando a login")
    setTimeout(() => {
      Router.navigate("login")
    }, 2000)
  }

  console.log("[v0] App inicializada correctamente")
})
