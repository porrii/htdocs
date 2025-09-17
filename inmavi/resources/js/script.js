document.addEventListener("DOMContentLoaded", () => {
  // Initialize AOS animation library
  AOS.init({
    once: true,
    disable: "mobile",
    duration: 800,
  })

  // Mobile Menu Toggle
  const menuToggle = document.querySelector(".menu-toggle")
  const navMenu = document.querySelector(".nav-menu")

  menuToggle.addEventListener("click", () => {
    navMenu.classList.toggle("active")
  })

  // Close menu when clicking on a nav link
  const navLinks = document.querySelectorAll(".nav-link")
  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      navMenu.classList.remove("active")
    })
  })

  // Language Switcher
  const esBtn = document.getElementById("es-btn")
  const enBtn = document.getElementById("en-btn")
  const langElements = document.querySelectorAll(".lang")
  const navLinkElements = document.querySelectorAll(".nav-link")

  // Set default language
  let currentLang = "es"

  // Function to switch language
  function switchLanguage(lang) {
    currentLang = lang

    // Update active button
    if (lang === "es") {
      esBtn.classList.add("active")
      enBtn.classList.remove("active")
    } else {
      enBtn.classList.add("active")
      esBtn.classList.remove("active")
    }

    // Update all language elements
    langElements.forEach((el) => {
      if (el.dataset[lang]) {
        el.textContent = el.dataset[lang]
      }
    })

    // Update navigation links
    navLinkElements.forEach((link) => {
      if (link.dataset[lang]) {
        link.textContent = link.dataset[lang]
      }
    })
  }

  // Event listeners for language buttons
  esBtn.addEventListener("click", () => {
    switchLanguage("es")
  })

  enBtn.addEventListener("click", () => {
    switchLanguage("en")
  })

  // Testimonial Slider
  const testimonialSlides = document.querySelectorAll(".testimonial-slide")
  const dots = document.querySelectorAll(".dot")
  const prevButton = document.querySelector(".testimonial-btn.prev")
  const nextButton = document.querySelector(".testimonial-btn.next")
  let currentSlide = 0

  function showSlide(index) {
    testimonialSlides.forEach((slide) => {
      slide.classList.remove("active")
    })
    dots.forEach((dot) => {
      dot.classList.remove("active")
    })

    testimonialSlides[index].classList.add("active")
    dots[index].classList.add("active")
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % testimonialSlides.length
    showSlide(currentSlide)
  }

  function prevSlide() {
    currentSlide = (currentSlide - 1 + testimonialSlides.length) % testimonialSlides.length
    showSlide(currentSlide)
  }

  if (prevButton && nextButton) {
    prevButton.addEventListener("click", prevSlide)
    nextButton.addEventListener("click", nextSlide)

    // Auto slide every 5 seconds
    setInterval(nextSlide, 5000)
  }

  // Dot navigation
  dots.forEach((dot, index) => {
    dot.addEventListener("click", () => {
      currentSlide = index
      showSlide(currentSlide)
    })
  })

  // Project Filtering
  const filterButtons = document.querySelectorAll(".filter-btn")
  const galleryItems = document.querySelectorAll(".gallery-item")

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      // Remove active class from all buttons
      filterButtons.forEach((btn) => btn.classList.remove("active"))

      // Add active class to clicked button
      button.classList.add("active")

      // Get filter value
      const filterValue = button.getAttribute("data-filter")

      // Filter gallery items
      galleryItems.forEach((item) => {
        if (filterValue === "all" || item.getAttribute("data-category") === filterValue) {
          item.style.display = "block"
        } else {
          item.style.display = "none"
        }
      })
    })
  })

  // Animate stats counter
  const statNumbers = document.querySelectorAll(".stat-number")

  function animateCounter(el) {
    const target = Number.parseInt(el.getAttribute("data-count"))
    const duration = 2000 // 2 seconds
    const step = target / (duration / 16) // 16ms per frame (approx 60fps)
    let current = 0

    const timer = setInterval(() => {
      current += step
      if (current >= target) {
        clearInterval(timer)
        el.textContent = target
      } else {
        el.textContent = Math.floor(current)
      }
    }, 16)
  }

  // Intersection Observer for stats
  const statsObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          statNumbers.forEach(animateCounter)
          statsObserver.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.5 },
  )

  const statsContainer = document.querySelector(".stats-container")
  if (statsContainer) {
    statsObserver.observe(statsContainer)
  }

  // Back to top button
  const backToTopButton = document.querySelector(".back-to-top")

  window.addEventListener("scroll", () => {
    if (window.scrollY > 300) {
      backToTopButton.classList.add("active")
    } else {
      backToTopButton.classList.remove("active")
    }
  })

  backToTopButton.addEventListener("click", (e) => {
    e.preventDefault()
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    })
  })

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()

      const targetId = this.getAttribute("href")
      if (targetId === "#") return

      const targetElement = document.querySelector(targetId)

      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80, // Offset for fixed header
          behavior: "smooth",
        })
      }
    })
  })

  // Add shadow to header on scroll
  window.addEventListener("scroll", () => {
    const scrollY = window.scrollY
    const header = document.getElementById("header")

    if (scrollY > 50) {
      header.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.1)"
    } else {
      header.style.boxShadow = "none"
    }
  })
})

document.getElementById("contactForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const statusDiv = document.getElementById("form-status");

    // Detectar idioma actual (es o en)
    const langElement = document.querySelector(".lang");
    const currentLang = langElement?.getAttribute("data-en") === langElement?.textContent.trim() ? "en" : "es";

    const messages = {
        success: {
            es: "Mensaje enviado con éxito.",
            en: "Message sent successfully."
        },
        error: {
            es: "Error al enviar el mensaje.",
            en: "Error sending the message."
        }
    };

    fetch(form.action, {
        method: "POST",
        body: formData
    })
    .then(response => {
        if (response.ok) {
            statusDiv.textContent = messages.success[currentLang];
            statusDiv.style.color = "green";
            form.reset();
        } else {
            return response.text().then(text => {
                throw new Error(text);
            });
        }
    })
    .catch(error => {
        statusDiv.textContent = messages.error[currentLang];
        statusDiv.style.color = "red";
        console.error("Error al enviar:", error);
    });
});