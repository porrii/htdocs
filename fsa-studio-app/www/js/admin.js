// Import or declare the API variable before using it
const API = {} // Placeholder for the API object, should be replaced with actual API implementation

// Gestión del panel de administración
const Admin = {
  // Barberos
  async getAllBarbers() {
    try {
      const response = await API.get("/api/admin/barbers/get-all")
      return response.data
    } catch (error) {
      throw error
    }
  },

  async createBarber(data) {
    try {
      const response = await API.post("/api/admin/barbers/create", data)
      return response
    } catch (error) {
      throw error
    }
  },

  async updateBarber(data) {
    try {
      const response = await API.put("/api/admin/barbers/update", data)
      return response
    } catch (error) {
      throw error
    }
  },

  async deleteBarber(id) {
    try {
      const response = await API.delete(`/api/admin/barbers/delete?id=${id}`)
      return response
    } catch (error) {
      throw error
    }
  },

  // Citas (Admin)
  async getAllAppointments() {
    try {
      const response = await API.get("/api/admin/appointments/get-all")
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getAppointmentStats() {
    try {
      const response = await API.get("/api/admin/appointments/stats")
      return response.data
    } catch (error) {
      throw error
    }
  },

  // Información de la barbería
  async getBarberiaInfo() {
    try {
      const response = await API.get("/api/admin/info/get")
      return response.data
    } catch (error) {
      throw error
    }
  },

  async updateBarberiaInfo(data) {
    try {
      const response = await API.put("/api/admin/info/update", data)
      return response
    } catch (error) {
      throw error
    }
  },

  // Videos management methods
  async getAllVideos() {
    try {
      const response = await API.get("/api/admin/videos/get-all")
      return response.data
    } catch (error) {
      throw error
    }
  },

  async createVideo(data) {
    try {
      const response = await API.post("/api/admin/videos/create", data)
      return response
    } catch (error) {
      throw error
    }
  },

  async updateVideo(data) {
    try {
      const response = await API.put("/api/admin/videos/update", data)
      return response
    } catch (error) {
      throw error
    }
  },

  async deleteVideo(id) {
    try {
      const response = await API.post("/api/admin/videos/delete", { id })
      return response
    } catch (error) {
      throw error
    }
  },

  // Tools management methods
  async getAllTools() {
    try {
      const response = await API.get("/api/admin/tools/get-all")
      return response.data
    } catch (error) {
      throw error
    }
  },

  async createTool(data) {
    try {
      const response = await API.post("/api/admin/tools/create", data)
      return response
    } catch (error) {
      throw error
    }
  },

  async updateTool(data) {
    try {
      const response = await API.put("/api/admin/tools/update", data)
      return response
    } catch (error) {
      throw error
    }
  },

  async deleteTool(id) {
    try {
      const response = await API.post("/api/admin/tools/delete", { id })
      return response
    } catch (error) {
      throw error
    }
  },
}

window.Admin = Admin
