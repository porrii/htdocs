// Tools management
const Tools = {
  async loadTools() {
    try {
      const response = await window.API.get("/tools/get-all.php")

      if (response.success) {
        this.renderTools(response.data)
      } else {
        window.Utils.showToast(response.message || "Error al cargar herramientas", "error")
      }
    } catch (error) {
      console.error("Error loading tools:", error)
      window.Utils.showToast("Error al cargar herramientas", "error")
    }
  },

  renderTools(tools) {
    const container = document.getElementById("tools-container")

    if (!tools || tools.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64" style="margin: 0 auto 1rem; opacity: 0.3;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
          </svg>
          <p class="text-muted">No hay herramientas disponibles</p>
        </div>
      `
      return
    }

    container.innerHTML = tools
      .map(
        (tool) => `
      <div class="tool-card">
        ${
          tool.image_url
            ? `
          <div class="tool-image">
            <img src="${tool.image_url}" alt="${tool.name}" onerror="this.parentElement.style.display='none'">
          </div>
        `
            : ""
        }
        <div class="tool-info">
          <h3>${tool.name}</h3>
          ${tool.description ? `<p class="text-muted">${tool.description}</p>` : ""}
        </div>
      </div>
    `,
      )
      .join("")
  },
}

window.Tools = Tools
