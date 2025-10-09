// Cliente API para comunicación con el backend
const API = {
  // Realizar petición HTTP
  async request(endpoint, options = {}) {
    const url = `${window.CONFIG.API_URL}${endpoint}`
    const token = Storage.get(window.CONFIG.STORAGE_KEYS.TOKEN)

    const defaultOptions = {
      headers: {
        "Content-Type": "application/json",
        ...(token && { Authorization: `Bearer ${token}` }),
      },
    }

    const config = {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...options.headers,
      },
    }

    try {
      const response = await fetch(url, config)
      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || "Error en la petición")
      }

      return data
    } catch (error) {
      // Verificar si hay conexión
      if (!navigator.onLine) {
        throw new Error("Sin conexión a internet")
      }
      throw error
    }
  },

  // Métodos HTTP
  get(endpoint) {
    return this.request(endpoint, { method: "GET" })
  },

  post(endpoint, data) {
    return this.request(endpoint, {
      method: "POST",
      body: JSON.stringify(data),
    })
  },

  put(endpoint, data) {
    return this.request(endpoint, {
      method: "PUT",
      body: JSON.stringify(data),
    })
  },

  delete(endpoint) {
    return this.request(endpoint, { method: "DELETE" })
  },
}

window.API = API
