// Gestión de autenticación
const Auth = {
  // Verificar si el usuario está autenticado
  isAuthenticated() {
    return Storage.has(CONFIG.STORAGE_KEYS.TOKEN)
  },

  // Obtener usuario actual
  getCurrentUser() {
    return Storage.get(CONFIG.STORAGE_KEYS.USER)
  },

  // Obtener token
  getToken() {
    return Storage.get(CONFIG.STORAGE_KEYS.TOKEN)
  },

  // Verificar si es administrador
  isAdmin() {
    const user = this.getCurrentUser()
    return user && user.rol === "admin"
  },

  // Login
  async login(email, password) {
    if (!window.Validators.email(email)) {
      throw new Error("Email inválido")
    }

    if (!password || password.length === 0) {
      throw new Error("La contraseña es requerida")
    }

    try {
      const response = await API.post("/api/auth/login", { email, password })

      if (response.success) {
        Storage.set(CONFIG.STORAGE_KEYS.TOKEN, response.data.token)
        Storage.set(CONFIG.STORAGE_KEYS.USER, response.data.user)
        return response
      }

      throw new Error(response.message)
    } catch (error) {
      throw error
    }
  },

  // Registro
  async register(nombre, email, password, telefono) {
    if (!nombre || nombre.trim().length < 2) {
      throw new Error("El nombre debe tener al menos 2 caracteres")
    }

    if (!window.Validators.email(email)) {
      throw new Error("Email inválido")
    }

    if (!window.Validators.password(password)) {
      throw new Error("La contraseña debe tener al menos 6 caracteres")
    }

    if (telefono && !window.Validators.phone(telefono)) {
      throw new Error("Teléfono inválido")
    }

    try {
      const response = await API.post("/api/auth/register", {
        nombre,
        email,
        password,
        telefono,
      })

      if (response.success) {
        Storage.set(CONFIG.STORAGE_KEYS.TOKEN, response.data.token)
        Storage.set(CONFIG.STORAGE_KEYS.USER, response.data.user)
        return response
      }

      throw new Error(response.message)
    } catch (error) {
      throw error
    }
  },

  // Verificar token
  async verifyToken() {
    try {
      const response = await API.get("/api/auth/verify")

      if (response.success) {
        Storage.set(CONFIG.STORAGE_KEYS.USER, response.data.user)
        return true
      }

      return false
    } catch (error) {
      this.logout()
      return false
    }
  },

  // Logout
  logout() {
    Storage.remove(CONFIG.STORAGE_KEYS.TOKEN)
    Storage.remove(CONFIG.STORAGE_KEYS.USER)
    Router.navigate("login")
  },
}

window.Auth = Auth

// Declare variables before using them
const CONFIG = {
  STORAGE_KEYS: {
    TOKEN: "token",
    USER: "user",
  },
}

const API = {
  post: async (url, data) => {
    // Mock implementation for demonstration
    return { success: true, data: { token: "mockToken", user: { rol: "admin" } } }
  },
  get: async (url) => {
    // Mock implementation for demonstration
    return { success: true, data: { user: { rol: "admin" } } }
  },
}

const Storage = {
  has: (key) => {
    // Mock implementation for demonstration
    return true
  },
  get: (key) => {
    // Mock implementation for demonstration
    return { rol: "admin" }
  },
  set: (key, value) => {
    // Mock implementation for demonstration
    console.log(`Setting ${key} to ${value}`)
  },
  remove: (key) => {
    // Mock implementation for demonstration
    console.log(`Removing ${key}`)
  },
}

const Router = {
  navigate: (path) => {
    // Mock implementation for demonstration
    console.log(`Navigating to ${path}`)
  },
}
