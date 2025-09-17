// Mobile menu toggle
document.getElementById("mobile-menu-btn").addEventListener("click", () => {
  const mobileMenu = document.getElementById("mobile-menu")
  mobileMenu.classList.toggle("hidden")
})

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault()
    const target = document.querySelector(this.getAttribute("href"))
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      })
    }
    // Close mobile menu if open
    document.getElementById("mobile-menu").classList.add("hidden")
  })
})

// Load dynamic content
document.addEventListener("DOMContentLoaded", () => {
  loadServices()
  loadProducts()
  loadVideos()
  loadSchedule()
})

// Load services from database
async function loadServices() {
  try {
    const response = await fetch("api/get_services.php")
    const services = await response.json()

    const servicesGrid = document.getElementById("services-grid")
    servicesGrid.innerHTML = ""

    services.forEach((service) => {
      const serviceCard = createServiceCard(service)
      servicesGrid.appendChild(serviceCard)
    })
  } catch (error) {
    console.error("Error loading services:", error)
  }
}

// Load products from database
async function loadProducts() {
  try {
    const response = await fetch("api/get_products.php")
    const products = await response.json()

    const productsGrid = document.getElementById("products-grid")
    productsGrid.innerHTML = ""

    products.forEach((product) => {
      const productCard = createProductCard(product)
      productsGrid.appendChild(productCard)
    })
  } catch (error) {
    console.error("Error loading products:", error)
  }
}

// Load videos from database
async function loadVideos() {
  try {
    const response = await fetch("api/get_videos.php")
    const videos = await response.json()

    const videosGrid = document.getElementById("videos-grid")
    videosGrid.innerHTML = ""

    videos.forEach((video) => {
      const videoCard = createVideoCard(video)
      videosGrid.appendChild(videoCard)
    })
  } catch (error) {
    console.error("Error loading videos:", error)
  }
}

// Load schedule from database
async function loadSchedule() {
  try {
    const response = await fetch("api/get_schedule.php")
    const schedule = await response.json()

    const scheduleInfo = document.getElementById("schedule-info")
    scheduleInfo.innerHTML = ""

    const days = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"]

    schedule.forEach((day) => {
      const dayElement = createScheduleDay(days[day.day_of_week], day)
      scheduleInfo.appendChild(dayElement)
    })
  } catch (error) {
    console.error("Error loading schedule:", error)
  }
}

// Create service card element
function createServiceCard(service) {
  const card = document.createElement("div")
  card.className = "service-card bg-white rounded-2xl shadow-lg overflow-hidden"

  card.innerHTML = `
        <div class="aspect-w-16 aspect-h-9 bg-gray-200">
            <img src="${service.image_url || "/barber2/public/barbershop-service.png"}" 
                 alt="${service.name}" 
                 class="w-full h-48 object-cover">
        </div>
        <div class="p-6">
            <h3 class="text-xl font-bold text-primary mb-2">${service.name}</h3>
            <p class="text-muted mb-4">${service.description}</p>
            <div class="flex justify-between items-center">
                <div class="text-2xl font-bold text-secondary">€${service.price}</div>
                <div class="text-sm text-muted">${service.duration} min</div>
            </div>
            <button onclick="bookService(${service.id})" 
                    class="w-full mt-4 bg-primary text-white py-2 rounded-lg hover:bg-primary/90 transition-colors">
                Reservar
            </button>
        </div>
    `

  return card
}

// Create product card element
function createProductCard(product) {
  const card = document.createElement("div")
  card.className = "bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow"

  card.innerHTML = `
        <div class="aspect-square bg-gray-200">
            <img src="${product.image_url || "/barber2/public/placeholder.svg?height=200&width=200&query=" + product.type + " " + product.name}" 
                 alt="${product.name}" 
                 class="w-full h-full object-cover">
        </div>
        <div class="p-4">
            <h3 class="font-semibold text-primary mb-1">${product.name}</h3>
            <p class="text-sm text-muted mb-2">${product.brand || ""}</p>
            <p class="text-xs text-gray-600">${product.description}</p>
        </div>
    `

  return card
}

// Create video card element
function createVideoCard(video) {
  const card = document.createElement("div")
  card.className = "bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow"

  card.innerHTML = `
        <div class="aspect-video bg-gray-200 relative cursor-pointer" onclick="playVideo('${video.video_url}')">
            <img src="${video.thumbnail_url || "public/barbershop-video-thumbnail.jpg"}" 
                 alt="${video.title}" 
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors">
                <div class="bg-white/90 rounded-full p-3">
                    <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="p-4">
            <h3 class="font-semibold text-primary mb-2">${video.title}</h3>
            <p class="text-sm text-muted">${video.description}</p>
        </div>
    `

  return card
}

// Create schedule day element
function createScheduleDay(dayName, dayData) {
  const dayElement = document.createElement("div")
  dayElement.className = "flex justify-between items-center py-2 border-b border-white/20"

  let schedule = "Cerrado"
  if (dayData.is_working_day) {
    schedule = ""
    if (dayData.morning_start && dayData.morning_end) {
      schedule += `${dayData.morning_start} - ${dayData.morning_end}`
    }
    if (dayData.afternoon_start && dayData.afternoon_end) {
      if (schedule) schedule += " | "
      schedule += `${dayData.afternoon_start} - ${dayData.afternoon_end}`
    }
  }

  dayElement.innerHTML = `
        <span class="font-medium">${dayName}</span>
        <span class="opacity-90">${schedule}</span>
    `

  return dayElement
}

// Book service function
function bookService(serviceId) {
  window.location.href = `booking.php?service=${serviceId}`
}

// Play video function
function playVideo(videoUrl) {
  // Create modal for video playback
  const modal = document.createElement("div")
  modal.className = "fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4"
  modal.onclick = (e) => {
    if (e.target === modal) {
      document.body.removeChild(modal)
    }
  }

  const videoContainer = document.createElement("div")
  videoContainer.className = "relative max-w-4xl w-full"

  videoContainer.innerHTML = `
        <button onclick="document.body.removeChild(this.closest('.fixed'))" 
                class="absolute -top-10 right-0 text-white hover:text-gray-300">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <div class="aspect-video">
            <iframe src="${videoUrl}" 
                    class="w-full h-full rounded-lg" 
                    frameborder="0" 
                    allowfullscreen>
            </iframe>
        </div>
    `

  modal.appendChild(videoContainer)
  document.body.appendChild(modal)
}

// Navbar scroll effect
window.addEventListener("scroll", () => {
  const navbar = document.querySelector("nav")
  if (window.scrollY > 100) {
    navbar.classList.add("bg-white/95")
    navbar.classList.remove("bg-white/90")
  } else {
    navbar.classList.add("bg-white/90")
    navbar.classList.remove("bg-white/95")
  }
})
