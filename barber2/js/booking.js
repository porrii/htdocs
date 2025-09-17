// Booking system state
const bookingData = {
  service: null,
  date: null,
  time: null,
  client: {},
}

let currentStep = 1
let availableSlots = []

// Initialize booking system
document.addEventListener("DOMContentLoaded", () => {
  initializeServiceSelection()
  initializeStepNavigation()

  // Check if service is pre-selected from URL
  const urlParams = new URLSearchParams(window.location.search)
  const serviceId = urlParams.get("service")
  if (serviceId) {
    selectService(serviceId)
  }
})

// Initialize service selection
function initializeServiceSelection() {
  const serviceOptions = document.querySelectorAll(".service-option")

  serviceOptions.forEach((option) => {
    option.addEventListener("click", () => {
      // Remove selection from all options
      serviceOptions.forEach((opt) => opt.classList.remove("border-primary", "bg-primary/5"))

      // Add selection to clicked option
      option.classList.add("border-primary", "bg-primary/5")

      // Store service data
      bookingData.service = {
        id: option.dataset.serviceId,
        name: option.dataset.serviceName,
        price: option.dataset.servicePrice,
        duration: option.dataset.serviceDuration,
      }

      // Enable next button
      document.getElementById("next-to-step2").disabled = false
    })
  })
}

// Select service programmatically
function selectService(serviceId) {
  const serviceOption = document.querySelector(`[data-service-id="${serviceId}"]`)
  if (serviceOption) {
    serviceOption.click()
  }
}

// Initialize step navigation
function initializeStepNavigation() {
  // Step 1 to 2
  document.getElementById("next-to-step2").addEventListener("click", () => {
    if (bookingData.service) {
      goToStep(2)
      generateCalendar()
      updateServiceInfo()
    }
  })

  // Step 2 navigation
  document.getElementById("back-to-step1").addEventListener("click", () => goToStep(1))
  document.getElementById("next-to-step3").addEventListener("click", () => {
    if (bookingData.date) {
      goToStep(3)
      loadTimeSlots()
      updateAppointmentSummary()
    }
  })

  // Step 3 navigation
  document.getElementById("back-to-step2").addEventListener("click", () => goToStep(2))
  document.getElementById("next-to-step4").addEventListener("click", () => {
    if (bookingData.time) {
      goToStep(4)
      updateFinalSummary()
    }
  })

  // Step 4 navigation
  document.getElementById("back-to-step3").addEventListener("click", () => goToStep(3))
  document.getElementById("confirm-booking").addEventListener("click", confirmBooking)
}

// Navigate to specific step
function goToStep(step) {
  // Hide all steps
  document.querySelectorAll(".booking-step").forEach((stepEl) => stepEl.classList.add("hidden"))

  // Show target step
  document.getElementById(`step${step}`).classList.remove("hidden")

  // Update step indicators
  updateStepIndicators(step)

  currentStep = step
}

// Update step indicators
function updateStepIndicators(activeStep) {
  for (let i = 1; i <= 4; i++) {
    const indicator = document.getElementById(`step${i}-indicator`)

    if (i < activeStep) {
      indicator.className =
        "step-completed w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold"
    } else if (i === activeStep) {
      indicator.className = "step-active w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold"
    } else {
      indicator.className =
        "step-inactive w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold"
    }
  }
}

// Generate calendar
function generateCalendar() {
  const calendar = document.getElementById("calendar")
  const today = new Date()
  const currentMonth = today.getMonth()
  const currentYear = today.getFullYear()

  // Generate next 30 days
  const calendarHTML = generateCalendarDays(today, 30)
  calendar.innerHTML = calendarHTML

  // Add click handlers to available dates
  calendar.querySelectorAll(".calendar-day:not(.unavailable)").forEach((day) => {
    day.addEventListener("click", () => {
      // Remove selection from all days
      calendar.querySelectorAll(".calendar-day").forEach((d) => d.classList.remove("selected"))

      // Select clicked day
      day.classList.add("selected")

      // Store selected date
      bookingData.date = day.dataset.date

      // Enable next button
      document.getElementById("next-to-step3").disabled = false
    })
  })
}

// Generate calendar days HTML
function generateCalendarDays(startDate, numDays) {
  let html = '<div class="grid grid-cols-7 gap-2 mb-4">'

  // Day headers
  const dayHeaders = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"]
  dayHeaders.forEach((day) => {
    html += `<div class="text-center text-sm font-medium text-muted py-2">${day}</div>`
  })
  html += "</div>"

  html += '<div class="grid grid-cols-7 gap-2">'

  for (let i = 0; i < numDays; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)

    const dayOfWeek = date.getDay()
    const isToday = date.toDateString() === new Date().toDateString()
    const isPast = date < new Date().setHours(0, 0, 0, 0)

    const dateStr = date.toISOString().split("T")[0]
    const dayNum = date.getDate()

    let classes =
      "calendar-day w-10 h-10 flex items-center justify-center rounded-lg cursor-pointer text-sm font-medium transition-colors"

    if (isPast || !checkWorkingDay(dayOfWeek)) {
      classes += " unavailable"
    } else {
      classes += " hover:bg-primary hover:text-white"
      if (isToday) {
        classes += " border-2 border-primary"
      }
    }

    html += `<div class="${classes}" data-date="${dateStr}">${dayNum}</div>`
  }

  html += "</div>"
  return html
}

// Check if day is working day
function checkWorkingDay(dayOfWeek) {
  return fetch("api/check_working_day.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ day_of_week: dayOfWeek }),
  })
    .then((response) => response.json())
    .then((result) => result.is_working_day)
    .catch((error) => {
      console.error("Error checking working day:", error)
      return false
    })
}

// Load available time slots
async function loadTimeSlots() {
  if (!bookingData.date || !bookingData.service) return

  try {
    const response = await fetch("api/get_available_slots.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        date: bookingData.date,
        service_duration: bookingData.service.duration,
      }),
    })

    availableSlots = await response.json()
    renderTimeSlots()
  } catch (error) {
    console.error("Error loading time slots:", error)
  }
}

// Render time slots
function renderTimeSlots() {
  const timeSlotsContainer = document.getElementById("time-slots")
  timeSlotsContainer.innerHTML = ""

  availableSlots.forEach((slot) => {
    const timeSlot = document.createElement("div")
    timeSlot.className = `time-slot p-3 border-2 border-gray-200 rounded-lg text-center cursor-pointer font-medium ${
      slot.available ? "hover:border-primary" : "unavailable"
    }`
    timeSlot.textContent = slot.time
    timeSlot.dataset.time = slot.time

    if (slot.available) {
      timeSlot.addEventListener("click", () => {
        // Remove selection from all slots
        timeSlotsContainer.querySelectorAll(".time-slot").forEach((s) => s.classList.remove("selected"))

        // Select clicked slot
        timeSlot.classList.add("selected")

        // Store selected time
        bookingData.time = slot.time

        // Enable next button
        document.getElementById("next-to-step4").disabled = false

        // Update summary
        updateAppointmentSummary()
      })
    }

    timeSlotsContainer.appendChild(timeSlot)
  })
}

// Update service info display
function updateServiceInfo() {
  if (!bookingData.service) return

  const serviceInfo = document.getElementById("selected-service-info")
  serviceInfo.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="font-medium">Servicio:</span>
                <span>${bookingData.service.name}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Duración:</span>
                <span>${bookingData.service.duration} minutos</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Precio:</span>
                <span class="text-secondary font-bold">€${bookingData.service.price}</span>
            </div>
        </div>
    `
}

// Update appointment summary
function updateAppointmentSummary() {
  if (!bookingData.service || !bookingData.date) return

  const summary = document.getElementById("appointment-summary")
  const dateObj = new Date(bookingData.date)
  const formattedDate = dateObj.toLocaleDateString("es-ES", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  })

  let summaryHTML = `
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="font-medium">Servicio:</span>
                <span>${bookingData.service.name}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Fecha:</span>
                <span>${formattedDate}</span>
            </div>
    `

  if (bookingData.time) {
    summaryHTML += `
            <div class="flex justify-between">
                <span class="font-medium">Hora:</span>
                <span>${bookingData.time}</span>
            </div>
        `
  }

  summaryHTML += `
            <div class="flex justify-between">
                <span class="font-medium">Duración:</span>
                <span>${bookingData.service.duration} min</span>
            </div>
            <div class="flex justify-between border-t pt-3">
                <span class="font-bold">Total:</span>
                <span class="text-secondary font-bold text-lg">€${bookingData.service.price}</span>
            </div>
        </div>
    `

  summary.innerHTML = summaryHTML
}

// Update final summary
function updateFinalSummary() {
  const finalSummary = document.getElementById("final-summary")
  const dateObj = new Date(bookingData.date)
  const formattedDate = dateObj.toLocaleDateString("es-ES", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  })

  finalSummary.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="font-medium">Servicio:</span>
                <span>${bookingData.service.name}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Fecha:</span>
                <span>${formattedDate}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Hora:</span>
                <span>${bookingData.time}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Duración:</span>
                <span>${bookingData.service.duration} minutos</span>
            </div>
            <div class="flex justify-between border-t pt-3">
                <span class="font-bold text-lg">Total a Pagar:</span>
                <span class="text-secondary font-bold text-xl">€${bookingData.service.price}</span>
            </div>
        </div>
    `
}

// Confirm booking
async function confirmBooking() {
  const form = document.getElementById("booking-form")
  const formData = new FormData(form)

  // Validate form
  if (!form.checkValidity()) {
    form.reportValidity()
    return
  }

  // Prepare booking data
  const bookingPayload = {
    service_id: bookingData.service.id,
    appointment_date: bookingData.date,
    appointment_time: bookingData.time,
    client_name: formData.get("client_name"),
    client_email: formData.get("client_email"),
    client_phone: formData.get("client_phone"),
    notes: formData.get("notes"),
  }

  try {
    // Disable button to prevent double submission
    const confirmBtn = document.getElementById("confirm-booking")
    confirmBtn.disabled = true
    confirmBtn.textContent = "Procesando..."

    const response = await fetch("api/create_booking.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(bookingPayload),
    })

    const result = await response.json()

    if (result.success) {
      // Show success modal
      document.getElementById("success-modal").classList.remove("hidden")
    } else {
      alert("Error al crear la reserva: " + result.message)
      confirmBtn.disabled = false
      confirmBtn.textContent = "Confirmar Reserva"
    }
  } catch (error) {
    console.error("Error confirming booking:", error)
    alert("Error al procesar la reserva. Por favor, inténtalo de nuevo.")

    const confirmBtn = document.getElementById("confirm-booking")
    confirmBtn.disabled = false
    confirmBtn.textContent = "Confirmar Reserva"
  }
}
