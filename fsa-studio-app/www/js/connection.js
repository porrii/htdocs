// Sistema de detección de conexión
const Connection = {
  isOnline: navigator.onLine,
  listeners: [],

  // Inicializar detector de conexión
  init() {
    // Eventos de conexión
    window.addEventListener("online", () => {
      this.isOnline = true
      this.notifyListeners(true)
      window.Utils.showToast("Conexión restaurada", "success")
    })

    window.addEventListener("offline", () => {
      this.isOnline = false
      this.notifyListeners(false)
      window.Utils.showToast("Sin conexión al servidor", "error")
    })

    // Verificar conexión periódicamente
    setInterval(() => {
      this.checkConnection()
    }, 30000) // Cada 30 segundos
  },

  // Verificar conexión al servidor
  async checkConnection() {
    try {
      const response = await fetch(window.API_CONFIG.BASE_URL + "/health-check.php", {
        method: "HEAD",
        cache: "no-cache",
      })

      const wasOnline = this.isOnline
      this.isOnline = response.ok

      // Si cambió el estado, notificar
      if (wasOnline !== this.isOnline) {
        this.notifyListeners(this.isOnline)
        if (this.isOnline) {
          window.Utils.showToast("Conexión restaurada", "success")
        } else {
          window.Utils.showToast("Sin conexión al servidor", "error")
        }
      }
    } catch (error) {
      const wasOnline = this.isOnline
      this.isOnline = false

      if (wasOnline) {
        this.notifyListeners(false)
        window.Utils.showToast("Sin conexión al servidor", "error")
      }
    }
  },

  // Agregar listener para cambios de conexión
  addListener(callback) {
    this.listeners.push(callback)
  },

  // Notificar a todos los listeners
  notifyListeners(isOnline) {
    this.listeners.forEach((callback) => {
      try {
        callback(isOnline)
      } catch (error) {
        console.error("Error in connection listener:", error)
      }
    })
  },

  // Obtener estado de conexión
  getStatus() {
    return this.isOnline
  },
}

window.Connection = Connection
