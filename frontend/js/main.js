/**
 * OpenRoadCyL - JavaScript Principal
 * SPA con ES6, Leaflet.js y Chart.js
 * Green Coding: Optimizaciones de rendimiento y reducción de peticiones
 */

class OpenRoadCyL {
    constructor() {
        this.apiBase = '../backend/api';
        this.map = null;
        this.markers = [];
        this.incidencias = [];
        this.user = null;
        this.charts = {};
        
        // Green Coding: Cache de datos para evitar peticiones repetidas
        this.cache = {
            provincias: null,
            tipos: null,
            lastFetch: null,
            cacheDuration: 300000 // 5 minutos
        };
        
        this.init();
    }

    /**
     * Inicialización de la aplicación
     */
    async init() {
        this.setupEventListeners();
        this.initMap();
        await this.checkUserSession();
        await this.loadInitialData();
        this.showSection('mapa');
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Navegación
        document.getElementById('btn-mapa').addEventListener('click', () => this.showSection('mapa'));
        document.getElementById('btn-estadisticas').addEventListener('click', () => this.showSection('estadisticas'));
        document.getElementById('btn-favoritos').addEventListener('click', () => this.showSection('favoritos'));

        // Autenticación
        document.getElementById('btn-login').addEventListener('click', () => this.showAuthModal('login'));
        document.getElementById('btn-register').addEventListener('click', () => this.showAuthModal('register'));
        document.getElementById('btn-logout').addEventListener('click', () => this.logout());

        // Modal
        document.querySelector('.close').addEventListener('click', () => this.hideAuthModal());
        document.getElementById('switch-to-register').addEventListener('click', (e) => {
            e.preventDefault();
            this.switchAuthForm('register');
        });
        document.getElementById('switch-to-login').addEventListener('click', (e) => {
            e.preventDefault();
            this.switchAuthForm('login');
        });

        // Formularios
        document.getElementById('form-login').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('form-register').addEventListener('submit', (e) => this.handleRegister(e));

        // Filtros
        document.getElementById('btn-aplicar-filtros').addEventListener('click', () => this.applyFilters());
        document.getElementById('btn-limpiar-filtros').addEventListener('click', () => this.clearFilters());
        document.getElementById('btn-actualizar').addEventListener('click', () => this.refreshData());

        // Green Coding: Cerrar modal al hacer clic fuera
        document.getElementById('auth-modal').addEventListener('click', (e) => {
            if (e.target.id === 'auth-modal') {
                this.hideAuthModal();
            }
        });
    }

    /**
     * Inicializar mapa de Leaflet
     * Green Coding: Configuración optimizada del mapa
     */
    initMap() {
        // Centrar en Castilla y León
        this.map = L.map('map').setView([41.6518, -4.7245], 8);

        // Green Coding: Usar tiles con caché del navegador
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18,
            // Green Coding: Configuraciones para optimizar rendimiento
            updateWhenIdle: true,
            updateWhenZooming: false,
            keepBuffer: 2
        }).addTo(this.map);
    }

    /**
     * Verificar sesión de usuario
     */
    async checkUserSession() {
        try {
            const response = await this.fetchAPI('/usuarios.php?action=session');
            if (response.success && response.authenticated) {
                this.user = response.user;
                this.updateAuthUI();
            }
        } catch (error) {
            console.error('Error verificando sesión:', error);
        }
    }

    /**
     * Cargar datos iniciales
     * Green Coding: Carga paralela de datos para optimizar tiempo
     */
    async loadInitialData() {
        this.showLoading(true);
        
        try {
            // Green Coding: Cargar datos en paralelo
            const [incidenciasResult, provinciasResult, tiposResult] = await Promise.all([
                this.loadIncidencias(),
                this.loadProvincias(),
                this.loadTipos()
            ]);

            if (incidenciasResult) {
                this.renderMapMarkers();
                this.updateCounter();
            }

        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showNotification('Error cargando datos', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Cargar incidencias con caché inteligente
     * Green Coding: Sistema de caché para evitar peticiones innecesarias
     */
    async loadIncidencias(filters = {}) {
        // Green Coding: Verificar caché
        const now = Date.now();
        if (this.cache.lastFetch && (now - this.cache.lastFetch) < this.cache.cacheDuration && !Object.keys(filters).length) {
            return true;
        }

        try {
            const params = new URLSearchParams(filters);
            const response = await this.fetchAPI(`/incidencias.php?action=list&${params}`);
            
            if (response.success) {
                this.incidencias = response.data;
                this.cache.lastFetch = now;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error cargando incidencias:', error);
            return false;
        }
    }

    /**
     * Cargar provincias disponibles
     */
    async loadProvincias() {
        if (this.cache.provincias) return true;

        try {
            const response = await this.fetchAPI('/incidencias.php?action=provincias');
            if (response.success) {
                this.cache.provincias = response.data;
                this.populateSelect('filter-provincia', response.data);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error cargando provincias:', error);
            return false;
        }
    }

    /**
     * Cargar tipos disponibles
     */
    async loadTipos() {
        if (this.cache.tipos) return true;

        try {
            const response = await this.fetchAPI('/incidencias.php?action=tipos');
            if (response.success) {
                this.cache.tipos = response.data;
                this.populateSelect('filter-tipo', response.data);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error cargando tipos:', error);
            return false;
        }
    }

    /**
     * Renderizar marcadores en el mapa
     * Green Coding: Optimización de marcadores para mejor rendimiento
     */
    renderMapMarkers() {
        // Limpiar marcadores existentes
        this.markers.forEach(marker => this.map.removeLayer(marker));
        this.markers = [];

        // Green Coding: Crear marcadores de forma eficiente
        this.incidencias.forEach(incidencia => {
            if (incidencia.lat && incidencia.lng) {
                const icon = this.getIncidenciaIcon(incidencia.tipo, incidencia.estado);
                
                const marker = L.marker([incidencia.lat, incidencia.lng], { icon })
                    .bindPopup(this.createPopupContent(incidencia))
                    .addTo(this.map);
                
                this.markers.push(marker);
            }
        });
    }

    /**
     * Obtener icono según tipo y estado de incidencia
     */
    getIncidenciaIcon(tipo, estado) {
        const colors = {
            'Accidente': '#e74c3c',
            'Obras': '#f39c12',
            'Meteorológica': '#3498db',
            'Retención': '#9b59b6'
        };

        const color = colors[tipo] || '#95a5a6';
        const opacity = estado === 'resuelta' ? 0.6 : 1;

        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="background-color: ${color}; opacity: ${opacity}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
    }

    /**
     * Crear contenido del popup
     */
    createPopupContent(incidencia) {
        const favoriteBtn = this.user ? 
            `<button onclick="app.toggleFavorite(${incidencia.id})" class="btn-favorite">
                ⭐ Favorito
            </button>` : '';

        return `
            <div class="popup-content">
                <h4>${incidencia.tipo}</h4>
                <p><strong>Descripción:</strong> ${incidencia.descripcion}</p>
                <p><strong>Carretera:</strong> ${incidencia.carretera} (PK ${incidencia.pk || 'N/A'})</p>
                <p><strong>Provincia:</strong> ${incidencia.provincia}</p>
                <p><strong>Estado:</strong> <span class="estado-${incidencia.estado}">${incidencia.estado}</span></p>
                <p><strong>Actualizado:</strong> ${incidencia.fecha}</p>
                ${favoriteBtn}
            </div>
        `;
    }

    /**
     * Cargar y mostrar estadísticas
     */
    async loadEstadisticas() {
        this.showLoading(true);
        
        try {
            const [provinciaStats, tipoStats] = await Promise.all([
                this.fetchAPI('/incidencias.php?action=stats-provincia'),
                this.fetchAPI('/incidencias.php?action=stats-tipo')
            ]);

            if (provinciaStats.success) {
                this.renderProvinciaChart(provinciaStats.data);
            }

            if (tipoStats.success) {
                this.renderTipoChart(tipoStats.data);
            }

        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            this.showNotification('Error cargando estadísticas', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Renderizar gráfico de provincias
     * Green Coding: Configuración optimizada de Chart.js
     */
    renderProvinciaChart(data) {
        const ctx = document.getElementById('chart-provincias').getContext('2d');
        
        // Destruir gráfico anterior si existe
        if (this.charts.provincias) {
            this.charts.provincias.destroy();
        }

        this.charts.provincias = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.provincia),
                datasets: [{
                    label: 'Total Incidencias',
                    data: data.map(item => item.total),
                    backgroundColor: 'rgba(44, 90, 160, 0.8)',
                    borderColor: 'rgba(44, 90, 160, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Renderizar gráfico de tipos
     */
    renderTipoChart(data) {
        const ctx = document.getElementById('chart-tipos').getContext('2d');
        
        if (this.charts.tipos) {
            this.charts.tipos.destroy();
        }

        const colors = [
            'rgba(231, 76, 60, 0.8)',
            'rgba(243, 156, 18, 0.8)',
            'rgba(52, 152, 219, 0.8)',
            'rgba(155, 89, 182, 0.8)',
            'rgba(46, 204, 113, 0.8)'
        ];

        this.charts.tipos = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.tipo),
                datasets: [{
                    data: data.map(item => item.total),
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Aplicar filtros
     */
    async applyFilters() {
        const filters = {};
        
        const provincia = document.getElementById('filter-provincia').value;
        const tipo = document.getElementById('filter-tipo').value;
        const estado = document.getElementById('filter-estado').value;

        if (provincia) filters.provincia = provincia;
        if (tipo) filters.tipo = tipo;
        if (estado) filters.estado = estado;

        this.showLoading(true);
        
        // Invalidar caché cuando se aplican filtros
        this.cache.lastFetch = null;
        const success = await this.loadIncidencias(filters);
        if (success) {
            this.renderMapMarkers();
            this.updateCounter();
            this.showNotification('Filtros aplicados correctamente', 'success');
        } else {
            this.showNotification('Error aplicando filtros', 'error');
        }
        
        this.showLoading(false);
    }

    /**
     * Limpiar filtros
     */
    async clearFilters() {
        document.getElementById('filter-provincia').value = '';
        document.getElementById('filter-tipo').value = '';
        document.getElementById('filter-estado').value = '';
        
        // Green Coding: Invalidar caché para forzar recarga
        this.cache.lastFetch = null;
        await this.loadIncidencias();
        this.renderMapMarkers();
        this.updateCounter();
        this.showNotification('Filtros limpiados', 'success');
    }

    /**
     * Refrescar datos
     */
    async refreshData() {
        // Green Coding: Invalidar caché para forzar actualización
        this.cache.lastFetch = null;
        await this.loadInitialData();
        this.showNotification('Datos actualizados', 'success');
    }

    /**
     * Mostrar sección específica
     */
    async showSection(section) {
        // Actualizar navegación
        document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`btn-${section}`).classList.add('active');

        // Ocultar todas las secciones
        document.querySelectorAll('.map-section, .stats-section, .favorites-section').forEach(sec => {
            sec.style.display = 'none';
        });

        // Mostrar sección seleccionada
        const sectionElement = document.getElementById(`${section}-section`);
        sectionElement.style.display = 'block';

        // Cargar datos específicos de la sección
        if (section === 'estadisticas') {
            await this.loadEstadisticas();
        } else if (section === 'favoritos') {
            await this.loadFavoritos();
        } else if (section === 'mapa') {
            // Green Coding: Solo redimensionar mapa si es necesario
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 100);
        }
    }

    /**
     * Cargar favoritos del usuario
     */
    async loadFavoritos() {
        if (!this.user) {
            document.getElementById('favoritos-list').innerHTML = 
                '<p class="no-auth-message">Inicia sesión para ver tus favoritos</p>';
            return;
        }

        try {
            const response = await this.fetchAPI('/usuarios.php?action=favoritos');
            if (response.success) {
                this.renderFavoritos(response.data);
            }
        } catch (error) {
            console.error('Error cargando favoritos:', error);
            this.showNotification('Error cargando favoritos', 'error');
        }
    }

    /**
     * Renderizar lista de favoritos
     */
    renderFavoritos(favoritos) {
        const container = document.getElementById('favoritos-list');
        
        if (favoritos.length === 0) {
            container.innerHTML = '<p class="no-auth-message">No tienes incidencias favoritas</p>';
            return;
        }

        container.innerHTML = favoritos.map(fav => `
            <div class="favorite-item">
                <div class="favorite-info">
                    <h4>${fav.tipo} - ${fav.carretera}</h4>
                    <p>${fav.descripcion}</p>
                    <p><strong>Provincia:</strong> ${fav.provincia} | <strong>Estado:</strong> ${fav.estado}</p>
                </div>
                <button onclick="app.removeFavorite(${fav.id})" class="btn-secondary">
                    Eliminar
                </button>
            </div>
        `).join('');
    }

    /**
     * Gestión de autenticación
     */
    showAuthModal(type) {
        document.getElementById('auth-modal').style.display = 'flex';
        this.switchAuthForm(type);
    }

    hideAuthModal() {
        document.getElementById('auth-modal').style.display = 'none';
    }

    switchAuthForm(type) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        
        if (type === 'login') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        try {
            const response = await this.fetchAPI('/usuarios.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'login',
                    email,
                    password
                })
            });

            if (response.success) {
                this.user = response.user;
                this.updateAuthUI();
                this.hideAuthModal();
                this.showNotification('Sesión iniciada correctamente', 'success');
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Error en login:', error);
            this.showNotification('Error al iniciar sesión', 'error');
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('register-nombre').value;
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;

        try {
            const response = await this.fetchAPI('/usuarios.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'register',
                    nombre,
                    email,
                    password
                })
            });

            if (response.success) {
                this.user = response.user;
                this.updateAuthUI();
                this.hideAuthModal();
                this.showNotification('Usuario registrado correctamente', 'success');
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Error en registro:', error);
            this.showNotification('Error al registrar usuario', 'error');
        }
    }

    async logout() {
        try {
            await this.fetchAPI('/usuarios.php?action=logout');
            this.user = null;
            this.updateAuthUI();
            this.showNotification('Sesión cerrada correctamente', 'success');
        } catch (error) {
            console.error('Error en logout:', error);
        }
    }

    updateAuthUI() {
        const loginBtn = document.getElementById('btn-login');
        const registerBtn = document.getElementById('btn-register');
        const userMenu = document.getElementById('user-menu');
        const userName = document.getElementById('user-name');

        if (this.user) {
            loginBtn.style.display = 'none';
            registerBtn.style.display = 'none';
            userMenu.style.display = 'flex';
            userName.textContent = this.user.nombre;
        } else {
            loginBtn.style.display = 'block';
            registerBtn.style.display = 'block';
            userMenu.style.display = 'none';
        }
    }

    /**
     * Gestión de favoritos
     */
    async toggleFavorite(incidenciaId) {
        if (!this.user) {
            this.showNotification('Inicia sesión para gestionar favoritos', 'warning');
            return;
        }

        try {
            const response = await this.fetchAPI('/usuarios.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'favorito',
                    incidencia_id: incidenciaId,
                    accion: 'add'
                })
            });

            if (response.success) {
                this.showNotification(response.message, 'success');
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Error gestionando favorito:', error);
            this.showNotification('Error al gestionar favorito', 'error');
        }
    }

    async removeFavorite(incidenciaId) {
        try {
            const response = await this.fetchAPI('/usuarios.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'favorito',
                    incidencia_id: incidenciaId,
                    accion: 'remove'
                })
            });

            if (response.success) {
                this.showNotification(response.message, 'success');
                await this.loadFavoritos(); // Recargar lista
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Error eliminando favorito:', error);
            this.showNotification('Error al eliminar favorito', 'error');
        }
    }

    /**
     * Utilidades
     */
    async fetchAPI(endpoint, options = {}) {
        const url = `${this.apiBase}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }

    populateSelect(selectId, options) {
        const select = document.getElementById(selectId);
        const currentValue = select.value;
        
        // Mantener la primera opción (placeholder)
        const firstOption = select.children[0];
        select.innerHTML = '';
        select.appendChild(firstOption);
        
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option;
            select.appendChild(optionElement);
        });
        
        // Restaurar valor seleccionado
        select.value = currentValue;
    }

    updateCounter() {
        const counter = document.getElementById('total-incidencias');
        counter.textContent = `${this.incidencias.length} incidencias`;
    }

    showLoading(show) {
        const loading = document.getElementById('loading');
        loading.style.display = show ? 'flex' : 'none';
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notifications');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        container.appendChild(notification);
        
        // Green Coding: Auto-eliminar notificación para evitar acumulación
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Green Coding: Inicializar aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.app = new OpenRoadCyL();
});

// Green Coding: Limpiar recursos al cerrar la página
window.addEventListener('beforeunload', () => {
    if (window.app && window.app.charts) {
        Object.values(window.app.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
    }
});