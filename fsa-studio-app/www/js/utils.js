// Utilidades generales
const Utils = {
  // Mostrar toast notification
  showToast(message, type = "success") {
    const toast = document.createElement("div")
    toast.className = `toast ${type}`
    toast.textContent = message

    document.body.appendChild(toast)

    setTimeout(() => {
      toast.style.animation = "slideDown 0.3s ease-in-out reverse"
      setTimeout(() => {
        document.body.removeChild(toast)
      }, 300)
    }, 3000)
  },

  // Formatear fecha
  formatDate(date) {
    const d = new Date(date)
    const day = String(d.getDate()).padStart(2, "0")
    const month = String(d.getMonth() + 1).padStart(2, "0")
    const year = d.getFullYear()
    return `${day}/${month}/${year}`
  },

  // Formatear hora
  formatTime(time) {
    return time.substring(0, 5)
  },

  // Validar email
  isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
  },

  // Debounce
  debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  },

  // Verificar conexión
  async checkConnection() {
    const CONFIG = { API_URL: "https://example.com" } // Declare CONFIG variable here
    if (!navigator.onLine) {
      this.showToast("Sin conexión a internet", "error")
      return false
    }

    try {
      const response = await fetch(CONFIG.API_URL + "/api/auth/verify", {
        method: "HEAD",
        cache: "no-cache",
      })
      return response.ok
    } catch (error) {
      this.showToast("No se puede conectar con el servidor", "error")
      return false
    }
  },
}

window.Utils = Utils
