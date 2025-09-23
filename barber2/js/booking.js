// Booking system state
const bookingData = {
  service: null,
  date: null,
  time: null,
  client: {},
}

let currentStep = 1
let availableSlots = []
let currentDate = new Date(); // Mes mostrado en calendario

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

// Convierte getDay() a índice con lunes=0
function getDayIndex(date) {
  return date.getDay(); // 0=Dom ... 6=Sáb
}

function formatDateLocal(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0"); // Enero=0
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

// Función que revisa si un día es laborable
async function checkWorkingDay(dayOfWeek) {
    try {
        const response = await fetch("api/check_working_day.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ day_of_week: dayOfWeek }),
        });
        const result = await response.json();
        return result.is_working_day;
    } catch (error) {
        console.error("Error checking working day:", error);
        return false;
    }
}

// Generate calendar corregido
async function generateCalendar(monthDate = currentDate) {
    const calendar = document.getElementById("calendar");
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const numDays = lastDay.getDate();

    const dayHeaders = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
    let html = '<div class="flex justify-between mb-2">';
    html += `<button id="prev-month-btn" class="px-3 py-1 border rounded hover:bg-gray-100">« Mes Anterior</button>`;
    html += `<span class="font-medium text-primary">${monthDate.toLocaleDateString("es-ES",{month:"long",year:"numeric"})}</span>`;
    html += `<button id="next-month-btn" class="px-3 py-1 border rounded hover:bg-gray-100">Mes Siguiente »</button>`;
    html += '</div>';

    html += '<div class="grid grid-cols-7 gap-2 mb-2">';
    dayHeaders.forEach(d => html += `<div class="text-center text-sm font-medium text-muted py-2">${d}</div>`);
    html += '</div><div class="grid grid-cols-7 gap-2">';

    const offset = getDayIndex(firstDay);
    for (let i = 0; i < offset; i++) html += '<div></div>';

    // Creamos un array de promesas para cada día
    const dayPromises = [];
    for (let day = 1; day <= numDays; day++) {
        const date = new Date(year, month, day);
        const dayIndex = getDayIndex(date);
        dayPromises.push(checkWorkingDay(dayIndex).then(isWorking => ({ date, isWorking })));
    }

    // Esperamos todas las promesas
    const daysInfo = await Promise.all(dayPromises);

    // Ahora generamos el HTML con la info correcta
    daysInfo.forEach(({ date, isWorking }) => {
        const dateStr = formatDateLocal(date); // función que formatea YYYY-MM-DD local
        const dayNum = date.getDate();
        const isToday = date.toDateString() === new Date().toDateString();
        const isPast = date < new Date().setHours(0, 0, 0, 0);

        let classes = "calendar-day w-10 h-10 flex items-center justify-center rounded-lg cursor-pointer text-sm font-medium transition-colors";
        if (isPast || !isWorking) {
            classes += " unavailable";
        } else {
            classes += " hover:bg-primary hover:text-white";
            if (isToday) classes += " border-2 border-primary";
        }

        html += `<div class="${classes}" data-date="${dateStr}">${dayNum}</div>`;
    });

    html += '</div>';
    calendar.innerHTML = html;

    // Click events
    calendar.querySelectorAll(".calendar-day:not(.unavailable)").forEach(dayEl => {
        dayEl.addEventListener("click", () => {
            calendar.querySelectorAll(".calendar-day").forEach(d => d.classList.remove("selected"));
            dayEl.classList.add("selected");
            bookingData.date = dayEl.dataset.date;
            document.getElementById("next-to-step3").disabled = false;
            updateAppointmentSummary();
        });
    });

    document.getElementById("prev-month-btn").addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        generateCalendar(currentDate);
    });
    document.getElementById("next-month-btn").addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        generateCalendar(currentDate);
    });
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

  if (!form.checkValidity()) {
    form.reportValidity()
    return
  }

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
