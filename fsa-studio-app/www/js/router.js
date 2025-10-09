// Sistema de enrutamiento
const Router = {
  currentPage: null,

  // Navegar a una página
  navigate(page, data = {}) {
    this.currentPage = page
    this.render(page, data)
  },

  // Renderizar página
  async render(page, data = {}) {
    const app = document.getElementById("app")
    const bottomNav = document.getElementById("bottom-nav")

    // Páginas que no requieren autenticación
    const publicPages = ["login", "register"]

    // Verificar autenticación
    const Auth = window.Auth // Declare Auth variable
    if (!publicPages.includes(page) && !Auth.isAuthenticated()) {
      this.navigate("login")
      return
    }

    // Mostrar/ocultar navegación
    if (publicPages.includes(page)) {
      bottomNav.style.display = "none"
    } else {
      bottomNav.style.display = "flex"
      this.updateActiveNav(page)
    }

    // Cargar contenido de la página
    let content = ""

    switch (page) {
      case "login":
        content = this.getLoginPage()
        break
      case "register":
        content = this.getRegisterPage()
        break
      case "home":
        content = this.getHomePage()
        break
      case "appointments":
        content = this.getAppointmentsPage()
        break
      case "videos":
        content = this.getVideosPage()
        break
      case "tools":
        content = this.getToolsPage()
        break
      case "profile":
        content = this.getProfilePage()
        break
      case "admin-barbers":
        content = this.getAdminBarbersPage()
        break
      case "admin-appointments":
        content = this.getAdminAppointmentsPage()
        break
      case "admin-info":
        content = this.getAdminInfoPage()
        break
      case "admin-videos":
        content = this.getAdminVideosPage()
        break
      case "admin-tools":
        content = this.getAdminToolsPage()
        break
      default:
        content = '<div class="page"><h1>Página no encontrada</h1></div>'
    }

    app.innerHTML = content
    this.attachEventListeners(page)
  },

  // Actualizar navegación activa
  updateActiveNav(page) {
    const navItems = document.querySelectorAll(".nav-item")
    navItems.forEach((item) => {
      if (item.dataset.page === page) {
        item.classList.add("active")
      } else {
        item.classList.remove("active")
      }
    })
  },

  // Adjuntar event listeners
  attachEventListeners(page) {
    switch (page) {
      case "login":
        this.attachLoginListeners()
        break
      case "register":
        this.attachRegisterListeners()
        break
      case "home":
        this.attachHomeListeners()
        break
      case "appointments":
        this.attachAppointmentsListeners()
        break
      case "videos":
        this.attachVideosListeners()
        break
      case "tools":
        this.attachToolsListeners()
        break
      case "admin-barbers":
        this.attachAdminBarbersListeners()
        break
      case "admin-appointments":
        this.attachAdminAppointmentsListeners()
        break
      case "admin-info":
        this.attachAdminInfoListeners()
        break
      case "admin-videos":
        this.attachAdminVideosListeners()
        break
      case "admin-tools":
        this.attachAdminToolsListeners()
        break
      case "profile":
        this.attachProfileListeners()
        break
    }
  },

  // Página de login
  getLoginPage() {
    return `
      <div class="page">
        <div style="max-width: 400px; margin: 0 auto; padding-top: 60px;">
          <div class="text-center mb-4">
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">FSA</h1>
            <p style="font-size: 1.25rem; letter-spacing: 0.3em; color: var(--color-text-muted);">STUDIO</p>
          </div>

          <div class="card">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Iniciar Sesión</h2>
            
            <form id="login-form">
              <div class="form-group">
                <label class="form-label">Email</label>
                <input 
                  type="email" 
                  class="form-input" 
                  id="login-email" 
                  placeholder="tu@email.com"
                  required
                />
              </div>

              <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input 
                  type="password" 
                  class="form-input" 
                  id="login-password" 
                  placeholder="••••••••"
                  required
                />
              </div>

              <div id="login-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                Iniciar Sesión
              </button>
            </form>

            <div class="text-center mt-3">
              <p class="text-muted">
                ¿No tienes cuenta? 
                <a href="#" id="go-to-register" style="color: var(--color-primary); text-decoration: none;">
                  Regístrate
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    `
  },

  // Página de registro
  getRegisterPage() {
    return `
      <div class="page">
        <div style="max-width: 400px; margin: 0 auto; padding-top: 40px;">
          <div class="text-center mb-4">
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">FSA</h1>
            <p style="font-size: 1.25rem; letter-spacing: 0.3em; color: var(--color-text-muted);">STUDIO</p>
          </div>

          <div class="card">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Crear Cuenta</h2>
            
            <form id="register-form">
              <div class="form-group">
                <label class="form-label">Nombre Completo</label>
                <input 
                  type="text" 
                  class="form-input" 
                  id="register-nombre" 
                  placeholder="Juan Pérez"
                  required
                />
              </div>

              <div class="form-group">
                <label class="form-label">Email</label>
                <input 
                  type="email" 
                  class="form-input" 
                  id="register-email" 
                  placeholder="tu@email.com"
                  required
                />
              </div>

              <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input 
                  type="tel" 
                  class="form-input" 
                  id="register-telefono" 
                  placeholder="+34 600 000 000"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input 
                  type="password" 
                  class="form-input" 
                  id="register-password" 
                  placeholder="••••••••"
                  required
                  minlength="6"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Confirmar Contraseña</label>
                <input 
                  type="password" 
                  class="form-input" 
                  id="register-password-confirm" 
                  placeholder="••••••••"
                  required
                  minlength="6"
                />
              </div>

              <div id="register-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="register-btn">
                Crear Cuenta
              </button>
            </form>

            <div class="text-center mt-3">
              <p class="text-muted">
                ¿Ya tienes cuenta? 
                <a href="#" id="go-to-login" style="color: var(--color-primary); text-decoration: none;">
                  Inicia sesión
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    `
  },

  // Página de inicio
  getHomePage() {
    const user = window.Auth.getCurrentUser() // Declare Auth variable

    // Si es admin, mostrar dashboard
    if (user.rol === "admin") {
      return this.getAdminDashboard()
    }

    // Si es cliente, mostrar home normal
    return `
      <div class="page">
        <div style="margin-bottom: 2rem;">
          <h1 style="margin-bottom: 0.5rem;">Bienvenido, ${user.nombre}</h1>
          <p class="text-muted">Gestiona tus citas y explora nuestros servicios</p>
        </div>

        <div class="card" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%); border: none; color: var(--color-background);">
          <h2 style="color: var(--color-background); margin-bottom: 1rem;">Agenda tu próxima cita</h2>
          <p style="color: var(--color-background); opacity: 0.9; margin-bottom: 1.5rem;">
            Reserva tu cita con nuestros barberos profesionales
          </p>
          <button class="btn" style="background-color: var(--color-background); color: var(--color-primary);" onclick="Router.navigate('appointments')">
            Ver Citas
          </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1.5rem;">
          <div class="card" style="text-align: center; cursor: pointer;" onclick="Router.navigate('videos')">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; color: var(--color-primary);">
              <polygon points="23 7 16 12 23 17 23 7"></polygon>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
            </svg>
            <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Videos</h3>
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0;">Nuestro trabajo</p>
          </div>

          <div class="card" style="text-align: center; cursor: pointer;" onclick="Router.navigate('tools')">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; color: var(--color-accent);">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
            <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Herramientas</h3>
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0;">Equipamiento pro</p>
          </div>
        </div>
      </div>
    `
  },

  // Dashboard de administrador
  getAdminDashboard() {
    return `
      <div class="page">
        <h1 style="margin-bottom: 1.5rem;">Panel de Administración</h1>

        <div id="admin-stats-loading" class="text-center" style="padding: 2rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="admin-stats" class="hidden">
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="card" style="text-align: center;">
              <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0.5rem;">Total Citas</p>
              <h2 id="stat-total" style="font-size: 2.5rem; margin-bottom: 0; color: var(--color-primary);">0</h2>
            </div>
            <div class="card" style="text-align: center;">
              <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0.5rem;">Este Mes</p>
              <h2 id="stat-month" style="font-size: 2.5rem; margin-bottom: 0; color: var(--color-accent);">0</h2>
            </div>
          </div>

          <div class="card" style="text-align: center; margin-bottom: 1.5rem;">
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0.5rem;">Ingresos Totales</p>
            <h2 id="stat-revenue" style="font-size: 2.5rem; margin-bottom: 0; color: var(--color-success);">$0</h2>
          </div>
        </div>

        <div style="display: grid; gap: 1rem;">
          <button class="btn btn-primary" onclick="Router.navigate('admin-barbers')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Gestionar Barberos
          </button>

          <button class="btn btn-secondary" onclick="Router.navigate('admin-appointments')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Ver Todas las Citas
          </button>

          <button class="btn btn-secondary" onclick="Router.navigate('admin-info')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Información de la Barbería
          </button>

          <button class="btn btn-secondary" onclick="Router.navigate('admin-videos')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="23 7 16 12 23 17 23 7"></polygon>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
            </svg>
            Gestionar Videos
          </button>

          <button class="btn btn-secondary" onclick="Router.navigate('admin-tools')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
            Gestionar Herramientas
          </button>
        </div>
      </div>
    `
  },

  // Página de gestión de barberos
  getAdminBarbersPage() {
    return `
      <div class="page">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h1 style="margin-bottom: 0;">Barberos</h1>
          <button class="btn btn-primary" id="new-barber-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo
          </button>
        </div>

        <div id="barbers-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="barbers-list" class="hidden"></div>
      </div>

      <div id="barber-modal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="barber-modal-title">Nuevo Barbero</h2>
            <button class="modal-close" id="close-barber-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="barber-form">
              <input type="hidden" id="barber-id" />
              
              <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-input" id="barber-nombre" required />
              </div>

              <div class="form-group">
                <label class="form-label">Especialidad</label>
                <input type="text" class="form-input" id="barber-especialidad" />
              </div>

              <div class="form-group">
                <label class="form-label">URL de Foto</label>
                <input type="url" class="form-input" id="barber-foto" />
              </div>

              <div class="form-group">
                <label class="form-label">Orden</label>
                <input type="number" class="form-input" id="barber-orden" value="0" />
              </div>

              <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input type="checkbox" id="barber-activo" checked style="width: 20px; height: 20px;" />
                  <span class="form-label" style="margin: 0;">Activo</span>
                </label>
              </div>

              <div id="barber-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="save-barber-btn">
                Guardar
              </button>
            </form>
          </div>
        </div>
      </div>
    `
  },

  // Página de todas las citas (admin)
  getAdminAppointmentsPage() {
    return `
      <div class="page">
        <h1 style="margin-bottom: 1.5rem;">Todas las Citas</h1>

        <div id="admin-appointments-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="admin-appointments-list" class="hidden"></div>
      </div>
    `
  },

  // Página de información de la barbería
  getAdminInfoPage() {
    return `
      <div class="page">
        <h1 style="margin-bottom: 1.5rem;">Información de la Barbería</h1>

        <div id="info-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="info-form-container" class="hidden">
          <form id="info-form">
            <div class="card">
              <h3 style="margin-bottom: 1rem;">Información General</h3>
              
              <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-input" id="info-nombre" required />
              </div>

              <div class="form-group">
                <label class="form-label">Dirección</label>
                <textarea class="form-input" id="info-direccion" rows="2"></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input type="tel" class="form-input" id="info-telefono" />
              </div>

              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" id="info-email" />
              </div>

              <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea class="form-input" id="info-descripcion" rows="3"></textarea>
              </div>
            </div>

            <div class="card">
              <h3 style="margin-bottom: 1rem;">Horarios</h3>
              
              <div class="form-group">
                <label class="form-label">Lunes</label>
                <input type="text" class="form-input" id="info-lunes" placeholder="10:00 - 20:00" />
              </div>

              <div class="form-group">
                <label class="form-label">Martes</label>
                <input type="text" class="form-input" id="info-martes" placeholder="10:00 - 20:00" />
              </div>

              <div class="form-group">
                <label class="form-label">Miércoles</label>
                <input type="text" class="form-input" id="info-miercoles" placeholder="10:00 - 20:00" />
              </div>

              <div class="form-group">
                <label class="form-label">Jueves</label>
                <input type="text" class="form-input" id="info-jueves" placeholder="10:00 - 20:00" />
              </div>

              <div class="form-group">
                <label class="form-label">Viernes</label>
                <input type="text" class="form-input" id="info-viernes" placeholder="10:00 - 21:00" />
              </div>

              <div class="form-group">
                <label class="form-label">Sábado</label>
                <input type="text" class="form-input" id="info-sabado" placeholder="09:00 - 21:00" />
              </div>

              <div class="form-group">
                <label class="form-label">Domingo</label>
                <input type="text" class="form-input" id="info-domingo" placeholder="Cerrado" />
              </div>
            </div>

            <div class="card">
              <h3 style="margin-bottom: 1rem;">Redes Sociales</h3>
              
              <div class="form-group">
                <label class="form-label">Instagram</label>
                <input type="text" class="form-input" id="info-instagram" placeholder="@fsastudio" />
              </div>

              <div class="form-group">
                <label class="form-label">Facebook</label>
                <input type="text" class="form-input" id="info-facebook" />
              </div>

              <div class="form-group">
                <label class="form-label">WhatsApp</label>
                <input type="tel" class="form-input" id="info-whatsapp" placeholder="+34 600 000 000" />
              </div>
            </div>

            <div id="info-error" class="text-error text-center mb-2 hidden"></div>

            <button type="submit" class="btn btn-primary btn-block" id="save-info-btn">
              Guardar Cambios
            </button>
          </form>
        </div>
      </div>
    `
  },

  // Página de citas
  getAppointmentsPage() {
    return `
      <div class="page">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h1 style="margin-bottom: 0;">Mis Citas</h1>
          <button class="btn btn-primary" id="new-appointment-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nueva Cita
          </button>
        </div>

        <div id="appointments-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
          <p class="text-muted mt-2">Cargando citas...</p>
        </div>

        <div id="appointments-list" class="hidden"></div>
        <div id="appointments-empty" class="hidden text-center" style="padding: 3rem;">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; opacity: 0.3;">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <p class="text-muted">No tienes citas agendadas</p>
          <button class="btn btn-primary mt-3" id="new-appointment-empty-btn">Agendar Primera Cita</button>
        </div>
      </div>

      <div id="new-appointment-modal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Nueva Cita</h2>
            <button class="modal-close" id="close-modal-btn">&times;</button>
          </div>
          <div class="modal-body">
            <form id="new-appointment-form">
              <div class="form-group">
                <label class="form-label">Servicio</label>
                <select class="form-input" id="appointment-service" required>
                  <option value="">Selecciona un servicio</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Barbero (Opcional)</label>
                <select class="form-input" id="appointment-barber">
                  <option value="">Sin preferencia</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Fecha</label>
                <input type="date" class="form-input" id="appointment-date" required />
              </div>

              <div class="form-group">
                <label class="form-label">Hora</label>
                <select class="form-input" id="appointment-time" required>
                  <option value="">Selecciona una hora</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Notas (Opcional)</label>
                <textarea class="form-input" id="appointment-notes" rows="3" placeholder="Alguna preferencia o comentario..."></textarea>
              </div>

              <div id="appointment-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="create-appointment-btn">
                Agendar Cita
              </button>
            </form>
          </div>
        </div>
      </div>

      <div id="edit-appointment-modal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Reprogramar Cita</h2>
            <button class="modal-close" id="close-edit-modal-btn">&times;</button>
          </div>
          <div class="modal-body">
            <form id="edit-appointment-form">
              <input type="hidden" id="edit-appointment-id" />
              
              <div class="form-group">
                <label class="form-label">Nueva Fecha</label>
                <input type="date" class="form-input" id="edit-appointment-date" required />
              </div>

              <div class="form-group">
                <label class="form-label">Nueva Hora</label>
                <select class="form-input" id="edit-appointment-time" required>
                  <option value="">Selecciona una hora</option>
                </select>
              </div>

              <div id="edit-appointment-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="update-appointment-btn">
                Guardar Cambios
              </button>
            </form>
          </div>
        </div>
      </div>
    `
  },

  // Adding videos page content
  getVideosPage() {
    return `
      <div class="page">
        <h1 style="margin-bottom: 1.5rem;">Videos</h1>
        <p class="text-muted" style="margin-bottom: 2rem;">Mira nuestros mejores trabajos</p>

        <div id="videos-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="videos-container"></div>
      </div>
    `
  },

  // Adding tools page content
  getToolsPage() {
    return `
      <div class="page">
        <h1 style="margin-bottom: 1.5rem;">Herramientas</h1>
        <p class="text-muted" style="margin-bottom: 2rem;">Equipamiento profesional de primera calidad</p>

        <div id="tools-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="tools-container"></div>
      </div>
    `
  },

  // Página de perfil
  getProfilePage() {
    const user = window.Auth.getCurrentUser() // Declare Auth variable
    return `
      <div class="page">
        <h1>Mi Perfil</h1>
        
        <div class="card">
          <div style="margin-bottom: 1rem;">
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0.25rem;">Nombre</p>
            <p style="font-size: 1.125rem; margin-bottom: 0;">${user.nombre}</p>
          </div>
          
          <div style="margin-bottom: 1rem;">
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0.25rem;">Email</p>
            <p style="font-size: 1.125rem; margin-bottom: 0;">${user.email}</p>
          </div>
          
          <div style="margin-bottom: 1rem;">
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0.25rem;">Rol</p>
            <p style="font-size: 1.125rem; margin-bottom: 0;">
              ${user.rol === "admin" ? "Administrador" : "Cliente"}
            </p>
          </div>
        </div>

        <button class="btn btn-secondary btn-block mt-3" id="logout-btn">
          Cerrar Sesión
        </button>
      </div>
    `
  },

  // Event listeners para login
  attachLoginListeners() {
    const form = document.getElementById("login-form")
    const goToRegister = document.getElementById("go-to-register")
    const errorDiv = document.getElementById("login-error")
    const submitBtn = document.getElementById("login-btn")
    const Auth = window.Auth // Declare Auth variable
    const Utils = window.Utils // Declare Utils variable

    form.addEventListener("submit", async (e) => {
      e.preventDefault()

      const email = document.getElementById("login-email").value
      const password = document.getElementById("login-password").value

      errorDiv.classList.add("hidden")
      submitBtn.disabled = true
      submitBtn.innerHTML = '<div class="spinner"></div>'

      try {
        await Auth.login(email, password)
        Utils.showToast("Inicio de sesión exitoso", "success")
        this.navigate("home")
      } catch (error) {
        errorDiv.textContent = error.message
        errorDiv.classList.remove("hidden")
      } finally {
        submitBtn.disabled = false
        submitBtn.textContent = "Iniciar Sesión"
      }
    })

    goToRegister.addEventListener("click", (e) => {
      e.preventDefault()
      this.navigate("register")
    })
  },

  // Event listeners para registro
  attachRegisterListeners() {
    const form = document.getElementById("register-form")
    const goToLogin = document.getElementById("go-to-login")
    const errorDiv = document.getElementById("register-error")
    const submitBtn = document.getElementById("register-btn")
    const Auth = window.Auth // Declare Auth variable
    const Utils = window.Utils // Declare Utils variable

    form.addEventListener("submit", async (e) => {
      e.preventDefault()

      const nombre = document.getElementById("register-nombre").value
      const email = document.getElementById("register-email").value
      const telefono = document.getElementById("register-telefono").value
      const password = document.getElementById("register-password").value
      const passwordConfirm = document.getElementById("register-password-confirm").value

      errorDiv.classList.add("hidden")

      if (password !== passwordConfirm) {
        errorDiv.textContent = "Las contraseñas no coinciden"
        errorDiv.classList.remove("hidden")
        return
      }

      submitBtn.disabled = true
      submitBtn.innerHTML = '<div class="spinner"></div>'

      try {
        await Auth.register(nombre, email, password, telefono)
        Utils.showToast("Registro exitoso", "success")
        this.navigate("home")
      } catch (error) {
        errorDiv.textContent = error.message
        errorDiv.classList.remove("hidden")
      } finally {
        submitBtn.disabled = false
        submitBtn.textContent = "Crear Cuenta"
      }
    })

    goToLogin.addEventListener("click", (e) => {
      e.preventDefault()
      this.navigate("login")
    })
  },

  // Event listeners para perfil
  attachProfileListeners() {
    const logoutBtn = document.getElementById("logout-btn")
    const Auth = window.Auth // Declare Auth variable

    logoutBtn.addEventListener("click", () => {
      Auth.logout()
    })
  },

  // Event listeners para citas
  attachAppointmentsListeners() {
    this.loadAppointments()

    const newAppointmentBtn = document.getElementById("new-appointment-btn")
    const newAppointmentEmptyBtn = document.getElementById("new-appointment-empty-btn")

    if (newAppointmentBtn) {
      newAppointmentBtn.addEventListener("click", () => this.openNewAppointmentModal())
    }

    if (newAppointmentEmptyBtn) {
      newAppointmentEmptyBtn.addEventListener("click", () => this.openNewAppointmentModal())
    }
  },

  // Cargar citas del usuario
  async loadAppointments() {
    const loadingDiv = document.getElementById("appointments-loading")
    const listDiv = document.getElementById("appointments-list")
    const emptyDiv = document.getElementById("appointments-empty")

    try {
      const appointments = await window.Appointments.getUserAppointments()

      loadingDiv.classList.add("hidden")

      if (appointments.length === 0) {
        emptyDiv.classList.remove("hidden")
        return
      }

      listDiv.classList.remove("hidden")
      listDiv.innerHTML = appointments
        .map(
          (apt) => `
        <div class="card" style="margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div>
              <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">${apt.servicio_nombre}</h3>
              <p class="text-muted" style="margin-bottom: 0;">
                ${window.Utils.formatDate(apt.fecha)} • ${window.Utils.formatTime(apt.hora)}
              </p>
            </div>
            <span style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; background-color: ${window.Appointments.getStatusColor(apt.estado)}20; color: ${window.Appointments.getStatusColor(apt.estado)};">
              ${window.Appointments.getStatusText(apt.estado)}
            </span>
          </div>

          ${
            apt.barbero_nombre
              ? `
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background-color: var(--color-surface); border-radius: 0.5rem;">
              <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--color-primary), var(--color-accent)); display: flex; align-items: center; justify-content: center; font-weight: 600;">
                ${apt.barbero_nombre.charAt(0)}
              </div>
              <div>
                <p style="font-weight: 500; margin-bottom: 0;">${apt.barbero_nombre}</p>
                <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 0;">Barbero</p>
              </div>
            </div>
          `
              : ""
          }

          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span class="text-muted">${apt.servicio_duracion} minutos • $${apt.servicio_precio}</span>
          </div>

          ${
            apt.estado === "pendiente" || apt.estado === "confirmada"
              ? `
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-secondary" style="flex: 1;" onclick="Router.editAppointment(${apt.id})">
                Reprogramar
              </button>
              <button class="btn btn-ghost" style="flex: 1; color: var(--color-error);" onclick="Router.cancelAppointment(${apt.id})">
                Cancelar
              </button>
            </div>
          `
              : ""
          }
        </div>
      `,
        )
        .join("")
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  // Abrir modal para nueva cita
  async openNewAppointmentModal() {
    const modal = document.getElementById("new-appointment-modal")
    modal.classList.remove("hidden")

    // Load services and barbers
    try {
      const [services, barbers] = await Promise.all([
        window.Appointments.getServices(),
        window.Appointments.getBarbers(),
      ])

      const serviceSelect = document.getElementById("appointment-service")
      serviceSelect.innerHTML =
        '<option value="">Selecciona un servicio</option>' +
        services.map((s) => `<option value="${s.id}">${s.nombre} - ${s.duracion}min - $${s.precio}</option>`).join("")

      const barberSelect = document.getElementById("appointment-barber")
      barberSelect.innerHTML =
        '<option value="">Sin preferencia</option>' +
        barbers.map((b) => `<option value="${b.id}">${b.nombre}</option>`).join("")

      // Set min date to today
      const dateInput = document.getElementById("appointment-date")
      dateInput.min = new Date().toISOString().split("T")[0]

      // Load time slots
      const timeSelect = document.getElementById("appointment-time")
      const slots = window.Appointments.getAvailableTimeSlots()
      timeSelect.innerHTML =
        '<option value="">Selecciona una hora</option>' +
        slots.map((slot) => `<option value="${slot}">${window.Utils.formatTime(slot)}</option>`).join("")
    } catch (error) {
      window.Utils.showToast("Error al cargar datos", "error")
    }

    // Attach event listeners
    const closeBtn = document.getElementById("close-modal-btn")
    closeBtn.onclick = () => modal.classList.add("hidden")

    const form = document.getElementById("new-appointment-form")
    form.onsubmit = async (e) => {
      e.preventDefault()
      await this.handleCreateAppointment()
    }
  },

  // Manejar creación de cita
  async handleCreateAppointment() {
    const errorDiv = document.getElementById("appointment-error")
    const submitBtn = document.getElementById("create-appointment-btn")

    const data = {
      servicio_id: document.getElementById("appointment-service").value,
      barbero_id: document.getElementById("appointment-barber").value || null,
      fecha: document.getElementById("appointment-date").value,
      hora: document.getElementById("appointment-time").value,
      notas: document.getElementById("appointment-notes").value || null,
    }

    errorDiv.classList.add("hidden")
    submitBtn.disabled = true
    submitBtn.innerHTML = '<div class="spinner"></div>'

    try {
      // Check availability if barber is selected
      if (data.barbero_id) {
        const available = await window.Appointments.checkAvailability(data.barbero_id, data.fecha, data.hora)
        if (!available) {
          throw new Error("El barbero no está disponible en ese horario")
        }
      }

      await window.Appointments.createAppointment(data)
      window.Utils.showToast("Cita agendada exitosamente", "success")

      document.getElementById("new-appointment-modal").classList.add("hidden")
      this.loadAppointments()
    } catch (error) {
      errorDiv.textContent = error.message
      errorDiv.classList.add("hidden") // Keep error hidden if it's a thrown error
      // If it's an actual validation error from the form, it will be handled by the server response.
      if (errorDiv.textContent) {
        errorDiv.classList.remove("hidden")
      }
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = "Agendar Cita"
    }
  },

  // Editar cita
  editAppointment(id) {
    const modal = document.getElementById("edit-appointment-modal")
    modal.classList.remove("hidden")

    document.getElementById("edit-appointment-id").value = id

    // Set min date to today
    const dateInput = document.getElementById("edit-appointment-date")
    dateInput.min = new Date().toISOString().split("T")[0]

    // Load time slots
    const timeSelect = document.getElementById("edit-appointment-time")
    const slots = window.Appointments.getAvailableTimeSlots()
    timeSelect.innerHTML =
      '<option value="">Selecciona una hora</option>' +
      slots.map((slot) => `<option value="${slot}">${window.Utils.formatTime(slot)}</option>`).join("")

    // Attach event listeners
    const closeBtn = document.getElementById("close-edit-modal-btn")
    closeBtn.onclick = () => modal.classList.add("hidden")

    const form = document.getElementById("edit-appointment-form")
    form.onsubmit = async (e) => {
      e.preventDefault()
      await this.handleUpdateAppointment()
    }
  },

  // Manejar actualización de cita
  async handleUpdateAppointment() {
    const errorDiv = document.getElementById("edit-appointment-error")
    const submitBtn = document.getElementById("update-appointment-btn")

    const id = document.getElementById("edit-appointment-id").value
    const fecha = document.getElementById("edit-appointment-date").value
    const hora = document.getElementById("edit-appointment-time").value

    errorDiv.classList.add("hidden")
    submitBtn.disabled = true
    submitBtn.innerHTML = '<div class="spinner"></div>'

    try {
      await window.Appointments.updateAppointment(id, fecha, hora)
      window.Utils.showToast("Cita reprogramada exitosamente", "success")

      document.getElementById("edit-appointment-modal").classList.add("hidden")
      this.loadAppointments()
    } catch (error) {
      errorDiv.textContent = error.message
      errorDiv.classList.remove("hidden")
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = "Guardar Cambios"
    }
  },

  // Cancelar cita
  async cancelAppointment(id) {
    if (!confirm("¿Estás seguro de que deseas cancelar esta cita?")) {
      return
    }

    try {
      await window.Appointments.cancelAppointment(id)
      window.Utils.showToast("Cita cancelada exitosamente", "success")
      this.loadAppointments()
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },

  // Event listeners para home
  attachHomeListeners() {
    const user = window.Auth.getCurrentUser()
    if (user.rol === "admin") {
      this.loadAdminStats()
    }
  },

  // Cargar estadísticas del admin
  async loadAdminStats() {
    const loadingDiv = document.getElementById("admin-stats-loading")
    const statsDiv = document.getElementById("admin-stats")

    try {
      const stats = await window.Admin.getAppointmentStats()

      loadingDiv.classList.add("hidden")
      statsDiv.classList.remove("hidden")

      document.getElementById("stat-total").textContent = stats.total
      document.getElementById("stat-month").textContent = stats.thisMonth
      document.getElementById("stat-revenue").textContent = `$${stats.revenue}`
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  // Event listeners para gestión de barberos
  attachAdminBarbersListeners() {
    this.loadBarbers()

    const newBarberBtn = document.getElementById("new-barber-btn")
    newBarberBtn.addEventListener("click", () => this.openBarberModal())
  },

  // Cargar barberos
  async loadBarbers() {
    const loadingDiv = document.getElementById("barbers-loading")
    const listDiv = document.getElementById("barbers-list")

    try {
      const barbers = await window.Admin.getAllBarbers()

      loadingDiv.classList.add("hidden")
      listDiv.classList.remove("hidden")

      listDiv.innerHTML = barbers
        .map(
          (barber) => `
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="flex: 1;">
              <h3 style="margin-bottom: 0.5rem;">${barber.nombre}</h3>
              ${barber.especialidad ? `<p class="text-muted" style="margin-bottom: 0.5rem;">${barber.especialidad}</p>` : ""}
              <span style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; background-color: ${barber.activo ? "var(--color-success)" : "var(--color-error)"}20; color: ${barber.activo ? "var(--color-success)" : "var(--color-error)"};">
                ${barber.activo ? "Activo" : "Inactivo"}
              </span>
            </div>
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-ghost" onclick="Router.editBarber(${barber.id})" style="padding: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button class="btn btn-ghost" onclick="Router.deleteBarber(${barber.id})" style="padding: 0.5rem; color: var(--color-error);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      `,
        )
        .join("")
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  // Abrir modal de barbero
  openBarberModal(barber = null) {
    const modal = document.getElementById("barber-modal")
    const title = document.getElementById("barber-modal-title")
    const form = document.getElementById("barber-form")

    title.textContent = barber ? "Editar Barbero" : "Nuevo Barbero"
    form.reset()

    if (barber) {
      document.getElementById("barber-id").value = barber.id
      document.getElementById("barber-nombre").value = barber.nombre
      document.getElementById("barber-especialidad").value = barber.especialidad || ""
      document.getElementById("barber-foto").value = barber.foto_url || ""
      document.getElementById("barber-orden").value = barber.orden
      document.getElementById("barber-activo").checked = barber.activo === 1
    }

    modal.classList.remove("hidden")

    const closeBtn = document.getElementById("close-barber-modal")
    closeBtn.onclick = () => modal.classList.add("hidden")

    form.onsubmit = async (e) => {
      e.preventDefault()
      await this.handleSaveBarber()
    }
  },

  // Guardar barbero
  async handleSaveBarber() {
    const errorDiv = document.getElementById("barber-error")
    const submitBtn = document.getElementById("save-barber-btn")

    const id = document.getElementById("barber-id").value
    const data = {
      nombre: document.getElementById("barber-nombre").value,
      especialidad: document.getElementById("barber-especialidad").value,
      foto_url: document.getElementById("barber-foto").value,
      orden: Number.parseInt(document.getElementById("barber-orden").value),
      activo: document.getElementById("barber-activo").checked ? 1 : 0,
    }

    if (id) {
      data.id = Number.parseInt(id)
    }

    errorDiv.classList.add("hidden")
    submitBtn.disabled = true
    submitBtn.innerHTML = '<div class="spinner"></div>'

    try {
      if (id) {
        await window.Admin.updateBarber(data)
        window.Utils.showToast("Barbero actualizado exitosamente", "success")
      } else {
        await window.Admin.createBarber(data)
        window.Utils.showToast("Barbero creado exitosamente", "success")
      }

      document.getElementById("barber-modal").classList.add("hidden")
      this.loadBarbers()
    } catch (error) {
      errorDiv.textContent = error.message
      errorDiv.classList.remove("hidden")
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = "Guardar"
    }
  },

  // Editar barbero
  async editBarber(id) {
    try {
      const barbers = await window.Admin.getAllBarbers()
      const barber = barbers.find((b) => b.id === id)
      if (barber) {
        this.openBarberModal(barber)
      }
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },

  // Eliminar barbero
  async deleteBarber(id) {
    if (!confirm("¿Estás seguro de que deseas eliminar este barbero?")) {
      return
    }

    try {
      await window.Admin.deleteBarber(id)
      window.Utils.showToast("Barbero eliminado exitosamente", "success")
      this.loadBarbers()
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },

  // Event listeners para citas de admin
  attachAdminAppointmentsListeners() {
    this.loadAdminAppointments()
  },

  // Cargar todas las citas (admin)
  async loadAdminAppointments() {
    const loadingDiv = document.getElementById("admin-appointments-loading")
    const listDiv = document.getElementById("admin-appointments-list")

    try {
      const appointments = await window.Admin.getAllAppointments()

      loadingDiv.classList.add("hidden")
      listDiv.classList.remove("hidden")

      listDiv.innerHTML = appointments
        .map(
          (apt) => `
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div>
              <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">${apt.cliente_nombre}</h3>
              <p class="text-muted" style="margin-bottom: 0;">
                ${apt.cliente_email} ${apt.cliente_telefono ? `• ${apt.cliente_telefono}` : ""}
              </p>
            </div>
            <span style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; background-color: ${window.Appointments.getStatusColor(apt.estado)}20; color: ${window.Appointments.getStatusColor(apt.estado)};">
              ${window.Appointments.getStatusText(apt.estado)}
            </span>
          </div>

          <div style="background-color: var(--color-surface); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <p style="margin-bottom: 0.5rem;"><strong>Servicio:</strong> ${apt.servicio_nombre}</p>
            <p style="margin-bottom: 0.5rem;"><strong>Fecha:</strong> ${window.Utils.formatDate(apt.fecha)} a las ${window.Utils.formatTime(apt.hora)}</p>
            ${apt.barbero_nombre ? `<p style="margin-bottom: 0.5rem;"><strong>Barbero:</strong> ${apt.barbero_nombre}</p>` : ""}
            <p style="margin-bottom: 0;"><strong>Duración:</strong> ${apt.servicio_duracion} min • <strong>Precio:</strong> $${apt.servicio_precio}</p>
          </div>

          ${apt.notas ? `<p class="text-muted" style="font-style: italic;">"${apt.notas}"</p>` : ""}
        </div>
      `,
        )
        .join("")
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  // Event listeners para información de la barbería
  attachAdminInfoListeners() {
    this.loadBarberiaInfo()
  },

  // Cargar información de la barbería
  async loadBarberiaInfo() {
    const loadingDiv = document.getElementById("info-loading")
    const formContainer = document.getElementById("info-form-container")

    try {
      const info = await window.Admin.getBarberiaInfo()

      loadingDiv.classList.add("hidden")
      formContainer.classList.remove("hidden")

      document.getElementById("info-nombre").value = info.nombre || ""
      document.getElementById("info-direccion").value = info.direccion || ""
      document.getElementById("info-telefono").value = info.telefono || ""
      document.getElementById("info-email").value = info.email || ""
      document.getElementById("info-descripcion").value = info.descripcion || ""
      document.getElementById("info-lunes").value = info.horario_lunes || ""
      document.getElementById("info-martes").value = info.horario_martes || ""
      document.getElementById("info-miercoles").value = info.horario_miercoles || ""
      document.getElementById("info-jueves").value = info.horario_jueves || ""
      document.getElementById("info-viernes").value = info.horario_viernes || ""
      document.getElementById("info-sabado").value = info.horario_sabado || ""
      document.getElementById("info-domingo").value = info.horario_domingo || ""
      document.getElementById("info-instagram").value = info.instagram || ""
      document.getElementById("info-facebook").value = info.facebook || ""
      document.getElementById("info-whatsapp").value = info.whatsapp || ""

      const form = document.getElementById("info-form")
      form.onsubmit = async (e) => {
        e.preventDefault()
        await this.handleSaveInfo()
      }
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  // Guardar información de la barbería
  async handleSaveInfo() {
    const errorDiv = document.getElementById("info-error")
    const submitBtn = document.getElementById("save-info-btn")

    const data = {
      nombre: document.getElementById("info-nombre").value,
      direccion: document.getElementById("info-direccion").value,
      telefono: document.getElementById("info-telefono").value,
      email: document.getElementById("info-email").value,
      descripcion: document.getElementById("info-descripcion").value,
      horario_lunes: document.getElementById("info-lunes").value,
      horario_martes: document.getElementById("info-martes").value,
      horario_miercoles: document.getElementById("info-miercoles").value,
      horario_jueves: document.getElementById("info-jueves").value,
      horario_viernes: document.getElementById("info-viernes").value,
      horario_sabado: document.getElementById("info-sabado").value,
      horario_domingo: document.getElementById("info-domingo").value,
      instagram: document.getElementById("info-instagram").value,
      facebook: document.getElementById("info-facebook").value,
      whatsapp: document.getElementById("info-whatsapp").value,
    }

    errorDiv.classList.add("hidden")
    submitBtn.disabled = true
    submitBtn.innerHTML = '<div class="spinner"></div>'

    try {
      await window.Admin.updateBarberiaInfo(data)
      window.Utils.showToast("Información actualizada exitosamente", "success")
    } catch (error) {
      errorDiv.textContent = error.message
      errorDiv.classList.remove("hidden")
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = "Guardar Cambios"
    }
  },

  attachVideosListeners() {
    const loadingDiv = document.getElementById("videos-loading")
    window.Videos.loadVideos().then(() => {
      loadingDiv.classList.add("hidden")
    })
  },

  attachToolsListeners() {
    const loadingDiv = document.getElementById("tools-loading")
    window.Tools.loadTools().then(() => {
      loadingDiv.classList.add("hidden")
    })
  },

  // Adding admin videos page content
  getAdminVideosPage() {
    return `
      <div class="page">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h1 style="margin-bottom: 0;">Videos</h1>
          <button class="btn btn-primary" id="new-video-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo Video
          </button>
        </div>

        <div id="admin-videos-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="admin-videos-list" class="hidden"></div>
      </div>

      <div id="video-modal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="video-modal-title">Nuevo Video</h2>
            <button class="modal-close" id="close-video-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="video-form">
              <input type="hidden" id="video-id" />
              
              <div class="form-group">
                <label class="form-label">Título</label>
                <input type="text" class="form-input" id="video-title" required />
              </div>

              <div class="form-group">
                <label class="form-label">URL de YouTube</label>
                <input type="url" class="form-input" id="video-url" placeholder="https://www.youtube.com/watch?v=..." required />
                <p class="text-muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Pega el enlace completo del video de YouTube</p>
              </div>

              <div class="form-group">
                <label class="form-label">Descripción (Opcional)</label>
                <textarea class="form-input" id="video-description" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input type="checkbox" id="video-active" checked style="width: 20px; height: 20px;" />
                  <span class="form-label" style="margin: 0;">Activo</span>
                </label>
              </div>

              <div id="video-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="save-video-btn">
                Guardar
              </button>
            </form>
          </div>
        </div>
      </div>
    `
  },

  // Adding admin tools page content
  getAdminToolsPage() {
    return `
      <div class="page">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h1 style="margin-bottom: 0;">Herramientas</h1>
          <button class="btn btn-primary" id="new-tool-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nueva Herramienta
          </button>
        </div>

        <div id="admin-tools-loading" class="text-center" style="padding: 3rem;">
          <div class="spinner" style="margin: 0 auto;"></div>
        </div>

        <div id="admin-tools-list" class="hidden"></div>
      </div>

      <div id="tool-modal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="tool-modal-title">Nueva Herramienta</h2>
            <button class="modal-close" id="close-tool-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="tool-form">
              <input type="hidden" id="tool-id" />
              
              <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-input" id="tool-name" required />
              </div>

              <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea class="form-input" id="tool-description" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">URL de Imagen (Opcional)</label>
                <input type="url" class="form-input" id="tool-image" placeholder="https://..." />
              </div>

              <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input type="checkbox" id="tool-active" checked style="width: 20px; height: 20px;" />
                  <span class="form-label" style="margin: 0;">Activo</span>
                </label>
              </div>

              <div id="tool-error" class="text-error text-center mb-2 hidden"></div>

              <button type="submit" class="btn btn-primary btn-block" id="save-tool-btn">
                Guardar
              </button>
            </form>
          </div>
        </div>
      </div>
    `
  },

  attachAdminVideosListeners() {
    this.loadAdminVideos()

    const newVideoBtn = document.getElementById("new-video-btn")
    newVideoBtn.addEventListener("click", () => this.openVideoModal())
  },

  async loadAdminVideos() {
    const loadingDiv = document.getElementById("admin-videos-loading")
    const listDiv = document.getElementById("admin-videos-list")

    try {
      const videos = await window.Admin.getAllVideos()

      loadingDiv.classList.add("hidden")
      listDiv.classList.remove("hidden")

      if (videos.length === 0) {
        listDiv.innerHTML = `
          <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64" style="margin: 0 auto 1rem; opacity: 0.3;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <p class="text-muted">No hay videos creados</p>
          </div>
        `
        return
      }

      listDiv.innerHTML = videos
        .map(
          (video) => `
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="flex: 1;">
              <h3 style="margin-bottom: 0.5rem;">${video.title}</h3>
              ${video.description ? `<p class="text-muted" style="margin-bottom: 0.5rem;">${video.description}</p>` : ""}
              <span style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; background-color: ${video.is_active ? "var(--color-success)" : "var(--color-error)"}20; color: ${video.is_active ? "var(--color-success)" : "var(--color-error)"};">
                ${video.is_active ? "Activo" : "Inactivo"}
              </span>
            </div>
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-ghost" onclick="Router.editVideo(${video.id})" style="padding: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button class="btn btn-ghost" onclick="Router.deleteVideo(${video.id})" style="padding: 0.5rem; color: var(--color-error);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      `,
        )
        .join("")
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  openVideoModal(video = null) {
    const modal = document.getElementById("video-modal")
    const title = document.getElementById("video-modal-title")
    const form = document.getElementById("video-form")

    title.textContent = video ? "Editar Video" : "Nuevo Video"
    form.reset()

    if (video) {
      document.getElementById("video-id").value = video.id
      document.getElementById("video-title").value = video.title
      document.getElementById("video-url").value = video.youtube_url
      document.getElementById("video-description").value = video.description || ""
      document.getElementById("video-active").checked = video.is_active === 1
    }

    modal.classList.remove("hidden")

    const closeBtn = document.getElementById("close-video-modal")
    closeBtn.onclick = () => modal.classList.add("hidden")

    form.onsubmit = async (e) => {
      e.preventDefault()
      await this.handleSaveVideo()
    }
  },

  async handleSaveVideo() {
    const errorDiv = document.getElementById("video-error")
    const submitBtn = document.getElementById("save-video-btn")

    const id = document.getElementById("video-id").value
    const data = {
      title: document.getElementById("video-title").value,
      youtube_url: document.getElementById("video-url").value,
      description: document.getElementById("video-description").value,
      is_active: document.getElementById("video-active").checked ? 1 : 0,
    }

    if (id) {
      data.id = Number.parseInt(id)
    }

    errorDiv.classList.add("hidden")
    submitBtn.disabled = true
    submitBtn.innerHTML = '<div class="spinner"></div>'

    try {
      if (id) {
        await window.Admin.updateVideo(data)
        window.Utils.showToast("Video actualizado exitosamente", "success")
      } else {
        await window.Admin.createVideo(data)
        window.Utils.showToast("Video creado exitosamente", "success")
      }

      document.getElementById("video-modal").classList.add("hidden")
      this.loadAdminVideos()
    } catch (error) {
      errorDiv.textContent = error.message
      errorDiv.classList.remove("hidden")
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = "Guardar"
    }
  },

  async editVideo(id) {
    try {
      const videos = await window.Admin.getAllVideos()
      const video = videos.find((v) => v.id === id)
      if (video) {
        this.openVideoModal(video)
      }
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },

  async deleteVideo(id) {
    if (!confirm("¿Estás seguro de que deseas eliminar este video?")) {
      return
    }

    try {
      await window.Admin.deleteVideo(id)
      window.Utils.showToast("Video eliminado exitosamente", "success")
      this.loadAdminVideos()
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },

  attachAdminToolsListeners() {
    this.loadAdminTools()

    const newToolBtn = document.getElementById("new-tool-btn")
    newToolBtn.addEventListener("click", () => this.openToolModal())
  },

  async loadAdminTools() {
    const loadingDiv = document.getElementById("admin-tools-loading")
    const listDiv = document.getElementById("admin-tools-list")

    try {
      const tools = await window.Admin.getAllTools()

      loadingDiv.classList.add("hidden")
      listDiv.classList.remove("hidden")

      if (tools.length === 0) {
        listDiv.innerHTML = `
          <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64" style="margin: 0 auto 1rem; opacity: 0.3;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
            </svg>
            <p class="text-muted">No hay herramientas creadas</p>
          </div>
        `
        return
      }

      listDiv.innerHTML = tools
        .map(
          (tool) => `
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="flex: 1;">
              <h3 style="margin-bottom: 0.5rem;">${tool.name}</h3>
              ${tool.description ? `<p class="text-muted" style="margin-bottom: 0.5rem;">${tool.description}</p>` : ""}
              <span style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; background-color: ${tool.is_active ? "var(--color-success)" : "var(--color-error)"}20; color: ${tool.is_active ? "var(--color-success)" : "var(--color-error)"};">
                ${tool.is_active ? "Activo" : "Inactivo"}
              </span>
            </div>
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-ghost" onclick="Router.editTool(${tool.id})" style="padding: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button class="btn btn-ghost" onclick="Router.deleteTool(${tool.id})" style="padding: 0.5rem; color: var(--color-error);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      `,
        )
        .join("")
    } catch (error) {
      loadingDiv.classList.add("hidden")
      window.Utils.showToast(error.message, "error")
    }
  },

  openToolModal(tool = null) {
    const modal = document.getElementById("tool-modal")
    const title = document.getElementById("tool-modal-title")
    const form = document.getElementById("tool-form")

    title.textContent = tool ? "Editar Herramienta" : "Nueva Herramienta"
    form.reset()

    if (tool) {
      document.getElementById("tool-id").value = tool.id
      document.getElementById("tool-name").value = tool.name
      document.getElementById("tool-description").value = tool.description || ""
      document.getElementById("tool-image").value = tool.image_url || ""
      document.getElementById("tool-active").checked = tool.is_active === 1
    }

    modal.classList.remove("hidden")

    const closeBtn = document.getElementById("close-tool-modal")
    closeBtn.onclick = () => modal.classList.add("hidden")

    form.onsubmit = async (e) => {
      e.preventDefault()
      await this.handleSaveTool()
    }
  },

  async handleSaveTool() {
    const errorDiv = document.getElementById("tool-error")
    const submitBtn = document.getElementById("save-tool-btn")

    const id = document.getElementById("tool-id").value
    const data = {
      name: document.getElementById("tool-name").value,
      description: document.getElementById("tool-description").value,
      image_url: document.getElementById("tool-image").value,
      is_active: document.getElementById("tool-active").checked ? 1 : 0,
    }

    if (id) {
      data.id = Number.parseInt(id)
    }

    errorDiv.classList.add("hidden")
    submitBtn.disabled = true
    submitBtn.innerHTML = '<div class="spinner"></div>'

    try {
      if (id) {
        await window.Admin.updateTool(data)
        window.Utils.showToast("Herramienta actualizada exitosamente", "success")
      } else {
        await window.Admin.createTool(data)
        window.Utils.showToast("Herramienta creada exitosamente", "success")
      }

      document.getElementById("tool-modal").classList.add("hidden")
      this.loadAdminTools()
    } catch (error) {
      errorDiv.textContent = error.message
      errorDiv.classList.remove("hidden")
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = "Guardar"
    }
  },

  async editTool(id) {
    try {
      const tools = await window.Admin.getAllTools()
      const tool = tools.find((t) => t.id === id)
      if (tool) {
        this.openToolModal(tool)
      }
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },

  async deleteTool(id) {
    if (!confirm("¿Estás seguro de que deseas eliminar esta herramienta?")) {
      return
    }

    try {
      await window.Admin.deleteTool(id)
      window.Utils.showToast("Herramienta eliminada exitosamente", "success")
      this.loadAdminTools()
    } catch (error) {
      window.Utils.showToast(error.message, "error")
    }
  },
}

window.Router = Router
