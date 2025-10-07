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
  loadFooterSchedule()
})

// Load config variables
fetch('api/config.php')
  .then(res => res.json())
  .then(config => {
    document.querySelector('#site-name').textContent = config.site_name;
    document.querySelector('#site-location').textContent = config.site_location;
    document.querySelector('#site-mail').textContent = config.site_mail;
    document.querySelector('#site-number').textContent = config.site_number;
    // document.querySelector('#site-url').textContent = config.site_url;
    document.getElementById('current-year').textContent = new Date().getFullYear();
  });

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

function formatTime(time) {
  if (!time) return ""
  return time.slice(0, 5) // muestra solo HH:MM
}

function createScheduleDay(dayName, dayData) {
  const dayElement = document.createElement("div")
  dayElement.className = "flex justify-between items-center py-2 border-b border-white/20"

  let schedule = "Cerrado"
  if (dayData.is_working_day) {
    schedule = ""
    if (dayData.morning_start && dayData.morning_end) {
      schedule += `${formatTime(dayData.morning_start)} - ${formatTime(dayData.morning_end)}`
    }
    if (dayData.afternoon_start && dayData.afternoon_end) {
      if (schedule) schedule += " | "
      schedule += `${formatTime(dayData.afternoon_start)} - ${formatTime(dayData.afternoon_end)}`
    }
  }

  dayElement.innerHTML = `
        <span class="font-medium">${dayName}</span>
        <span class="opacity-90">${schedule}</span>
    `

  return dayElement
}

// Load footer schedule from database
async function loadFooterSchedule() {
    try {
        const response = await fetch("api/get_schedule.php");
        const schedule = await response.json();

        const footerSchedule = document.getElementById("footer-schedule");
        footerSchedule.innerHTML = "";

        const days = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];

        schedule.forEach((day) => {
            const dayElement = createFooterScheduleDay(days[day.day_of_week], day);
            footerSchedule.appendChild(dayElement);
        });
    } catch (error) {
        console.error("Error loading footer schedule:", error);
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
  const card = document.createElement("div");
  card.className = "bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow";

  const thumbnail = getYouTubeThumbnail(video.video_url, video.thumbnail_url);

  card.innerHTML = `
    <div class="aspect-video bg-gray-200 relative cursor-pointer" onclick="playVideo('${video.video_url}')">
      <img src="${thumbnail}" 
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
  `;

  return card;
}

// Create schedule footer card element
function createFooterScheduleDay(dayName, dayData) {
    let schedule = "Cerrado";
    if (dayData.is_working_day) {
        schedule = "";
        if (dayData.morning_start && dayData.morning_end) {
            schedule += `${formatTime(dayData.morning_start)} - ${formatTime(dayData.morning_end)}`;
        }
        if (dayData.afternoon_start && dayData.afternoon_end) {
            if (schedule) schedule += " | ";
            schedule += `${formatTime(dayData.afternoon_start)} - ${formatTime(dayData.afternoon_end)}`;
        }
    }

    const dayElement = document.createElement("p");
    dayElement.textContent = `${dayName}: ${schedule}`;
    return dayElement;
}

// Book service function
function bookService(serviceId) {
  window.location.href = `booking.php?service=${serviceId}`
}

// Conseguir embed para video
function getYouTubeEmbedUrl(url) {
  // Extrae el ID del video de YouTube
  const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([\w-]+)/);
  return match ? `https://www.youtube.com/embed/${match[1]}?autoplay=1` : null;
}

// Conseguir thumbnail si no se proporciona
function getYouTubeThumbnail(url, customThumbnail) {
  if (customThumbnail) return customThumbnail; // si hay thumbnail subida, se usa
  const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([\w-]+)/);
  return match ? `https://img.youtube.com/vi/${match[1]}/hqdefault.jpg` : 'public/barbershop-video-thumbnail.jpg';
}

// Play video function
function playVideo(videoUrl) {
  const embedUrl = getYouTubeEmbedUrl(videoUrl);
  if (!embedUrl) return alert("URL de YouTube no válida");

  const modal = document.createElement("div");
  modal.className = "fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4";
  modal.onclick = (e) => { if (e.target === modal) document.body.removeChild(modal); };

  const videoContainer = document.createElement("div");
  videoContainer.className = "relative max-w-4xl w-full aspect-video";

  const iframe = document.createElement("iframe");
  iframe.src = embedUrl;
  iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share";
  iframe.allowFullscreen = true;
  iframe.className = "w-full h-full";

  const closeButton = document.createElement("button");
  closeButton.className = "absolute -top-10 right-0 text-white hover:text-gray-300";
  closeButton.innerHTML = `
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `;
  closeButton.onclick = () => document.body.removeChild(modal);

  videoContainer.appendChild(closeButton);
  videoContainer.appendChild(iframe);
  modal.appendChild(videoContainer);
  document.body.appendChild(modal);
}

// Send contact mail
document.querySelector('form').addEventListener('submit', async (e) => {
  e.preventDefault();

  const nombre = e.target.querySelector('input[type="text"]').value.trim();
  const email = e.target.querySelector('input[type="email"]').value.trim();
  const mensaje = e.target.querySelector('textarea').value.trim();

  if (!nombre || !email || !mensaje) {
      alert('Por favor, completa todos los campos.');
      return;
  }

  const data = { nombre, email, mensaje };

  try {
      const response = await fetch('api/send_contact.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
      });

      const result = await response.json();

      if (result.success) {
          e.target.reset();
      } else {
          alert('❌ Error: ' + result.message);
      }
  } catch (error) {
      alert('⚠️ Error de conexión. Intenta nuevamente.');
      console.error(error);
  }
});

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
