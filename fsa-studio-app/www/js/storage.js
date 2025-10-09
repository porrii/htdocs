// Gestión de almacenamiento local
const Storage = {
  // Guardar dato
  set(key, value) {
    try {
      const serialized = JSON.stringify(value)
      localStorage.setItem(key, serialized)
      return true
    } catch (error) {
      console.error("Error al guardar en storage:", error)
      return false
    }
  },

  // Obtener dato
  get(key) {
    try {
      const item = localStorage.getItem(key)
      return item ? JSON.parse(item) : null
    } catch (error) {
      console.error("Error al leer de storage:", error)
      return null
    }
  },

  // Eliminar dato
  remove(key) {
    try {
      localStorage.removeItem(key)
      return true
    } catch (error) {
      console.error("Error al eliminar de storage:", error)
      return false
    }
  },

  // Limpiar todo
  clear() {
    try {
      localStorage.clear()
      return true
    } catch (error) {
      console.error("Error al limpiar storage:", error)
      return false
    }
  },

  // Verificar si existe
  has(key) {
    return localStorage.getItem(key) !== null
  },
}

window.Storage = Storage
