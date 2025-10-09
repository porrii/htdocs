// Videos management
const Videos = {
  async loadVideos() {
    try {
      const response = await window.API.get("/videos/get-all.php")

      if (response.success) {
        this.renderVideos(response.data)
      } else {
        window.Utils.showToast(response.message || "Error al cargar videos", "error")
      }
    } catch (error) {
      console.error("Error loading videos:", error)
      window.Utils.showToast("Error al cargar videos", "error")
    }
  },

  renderVideos(videos) {
    const container = document.getElementById("videos-container")

    if (!videos || videos.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64" style="margin: 0 auto 1rem; opacity: 0.3;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
          </svg>
          <p class="text-muted">No hay videos disponibles</p>
        </div>
      `
      return
    }

    container.innerHTML = videos
      .map(
        (video) => `
      <div class="video-card">
        <div class="video-wrapper">
          <iframe 
            src="${video.youtube_url}" 
            title="${video.title}"
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
          </iframe>
        </div>
        <div class="video-info">
          <h3>${video.title}</h3>
          ${video.description ? `<p class="text-muted">${video.description}</p>` : ""}
        </div>
      </div>
    `,
      )
      .join("")
  },
}

window.Videos = Videos
