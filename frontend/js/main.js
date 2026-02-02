/**
 * OpenRoadCyL - JavaScript Principal
 */

class OpenRoadCyL {
    constructor() {
        const path = window.location.pathname;
        this.apiBase = path.includes('/proyecto_base/') ? '/proyecto_base/backend/api' : '/backend/api';
        this.map = null;
        this.markers = [];
        this.incidencias = [];
        this.user = null;
        this.charts = {};
        this.miniMap = null;
        this.miniMapMarker = null;
        
        this.cache = {
            provincias: null,
            tipos: null,
            lastFetch: null,
            cacheDuration: 300000,
            geocoding: {}
        };
        
        this.init();
    }

    async init() {
        this.setupEventListeners();
        this.initMap();
        await this.checkUserSession();
        await this.loadInitialData();
        this.showSection('mapa');
    }

    setupEventListeners() {
        // Navegación
        document.getElementById('btn-mapa').addEventListener('click', () => this.showSection('mapa'));
        document.getElementById('btn-estadisticas').addEventListener('click', () => this.showSection('estadisticas'));
        document.getElementById('btn-favoritos').addEventListener('click', () => this.showSection('favoritos'));

        // Autenticación
        document.getElementById('btn-login').addEventListener('click', () => this.showAuthModal('login'));
        document.getElementById('btn-register').addEventListener('click', () => this.showAuthModal('register'));
        document.getElementById('btn-logout').addEventListener('click', () => this.logout());

        // Modal de autenticación
        document.querySelector('.close').addEventListener('click', () => this.hideAuthModal());
        document.getElementById('switch-to-register').addEventListener('click', (e) => {
            e.preventDefault();
            this.switchAuthForm('register');
        });
        document.getElementById('switch-to-login').addEventListener('click', (e) => {
            e.preventDefault();
            this.switchAuthForm('login');
        });

        // Modal de nueva incidencia
        const btnNuevaIncidencia = document.getElementById('btn-nueva-incidencia');
        if (btnNuevaIncidencia) {
            btnNuevaIncidencia.addEventListener('click', () => this.showIncidenciaModal());
        }
        
        const closeIncidenciaBtn = document.querySelector('.close-incidencia');
        if (closeIncidenciaBtn) {
            closeIncidenciaBtn.addEventListener('click', () => this.hideIncidenciaModal());
        }
        
        document.getElementById('incidencia-modal').addEventListener('click', (e) => {
            if (e.target.id === 'incidencia-modal') {
                this.hideIncidenciaModal();
            }
        });

        // Formularios
        document.getElementById('form-login').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('form-register').addEventListener('submit', (e) => this.handleRegister(e));
        document.getElementById('form-nueva-incidencia').addEventListener('submit', (e) => this.handleNewIncidencia(e));

        // Filtros
        document.getElementById('btn-aplicar-filtros').addEventListener('click', () => this.applyFilters());
        document.getElementById('btn-limpiar-filtros').addEventListener('click', () => this.clearFilters());
        
        // Comparar JSONs automáticamente
        const btnCompararAuto = document.getElementById('btn-comparar-auto');
        if (btnCompararAuto) {
            btnCompararAuto.addEventListener('click', () => this.compararJSONsAuto());
        }

        document.getElementById('auth-modal').addEventListener('click', (e) => {
            if (e.target.id === 'auth-modal') {
                this.hideAuthModal();
            }
        });
    }

    initMap() {
        setTimeout(() => {
            try {
                this.map = L.map('map').setView([41.6518, -4.7245], 8);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 18,
                    updateWhenIdle: true,
                    updateWhenZooming: false,
                    keepBuffer: 2
                }).addTo(this.map);
            } catch (error) {
                setTimeout(() => this.initMap(), 1000);
            }
        }, 100);
    }

    async checkUserSession() {
        try {
            const response = await this.fetchAPI('/usuarios.php?action=session');
            if (response.success && response.authenticated) {
                this.user = response.user;
                this.updateAuthUI();
            }
        } catch (error) {
            this.showNotification('Error verificando sesión', 'error');
        }
    }

    async loadInitialData() {
        this.showLoading(true);
        
        try {
            const [incidenciasResult] = await Promise.all([
                this.loadIncidencias(),
                this.loadProvincias(),
                this.loadTipos()
            ]);

            if (incidenciasResult) {
                this.renderMapMarkers();
                this.updateCounter();
            }
        } catch (error) {
            this.showNotification('Error cargando datos', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadIncidencias(filters = {}) {
        const now = Date.now();
        if (this.cache.lastFetch && (now - this.cache.lastFetch) < this.cache.cacheDuration && !Object.keys(filters).length) {
            return true;
        }

        try {
            const params = new URLSearchParams(filters);
            params.append('_t', Date.now());
            const response = await this.fetchAPI(`/incidencias.php?action=list&${params}`);
            
            if (response.success) {
                this.incidencias = response.data;
                this.cache.lastFetch = now;
                return true;
            }
            return false;
        } catch (error) {
            return false;
        }
    }

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
            return false;
        }
    }

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
            return false;
        }
    }

    renderMapMarkers() {
        if (!this.map) {
            setTimeout(() => this.renderMapMarkers(), 500);
            return;
        }
        
        this.markers.forEach(marker => this.map.removeLayer(marker));
        this.markers = [];

        this.incidencias.forEach((incidencia) => {
            if (incidencia.lat && incidencia.lng) {
                const icon = this.getIncidenciaIcon(incidencia.tipo, incidencia.estado);
                const marker = L.marker([incidencia.lat, incidencia.lng], { icon })
                    .bindPopup(this.createPopupContent(incidencia))
                    .addTo(this.map);
                this.markers.push(marker);
            }
        });
    }

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

    createPopupContent(incidencia) {
        const favoriteBtn = this.user ? 
            `<button onclick="app.toggleFavorite(${incidencia.id})" class="btn-favorite">
                Favorito
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
            this.showNotification('Error cargando estadísticas', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    renderProvinciaChart(data) {
        const ctx = document.getElementById('chart-provincias').getContext('2d');
        
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

    async applyFilters() {
        const filters = {};
        
        const provincia = document.getElementById('filter-provincia').value;
        const tipo = document.getElementById('filter-tipo').value;
        const estado = document.getElementById('filter-estado').value;

        if (provincia) filters.provincia = provincia;
        if (tipo) filters.tipo = tipo;
        if (estado) filters.estado = estado;

        this.showLoading(true);
        
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

    async clearFilters() {
        document.getElementById('filter-provincia').value = '';
        document.getElementById('filter-tipo').value = '';
        document.getElementById('filter-estado').value = '';
        
        this.cache.lastFetch = null;
        await this.loadIncidencias();
        this.renderMapMarkers();
        this.updateCounter();
        this.showNotification('Filtros limpiados', 'success');
    }

    async refreshData() {
        this.cache.lastFetch = null;
        this.showLoading(true);
        
        try {
            document.getElementById('filter-provincia').value = '';
            document.getElementById('filter-tipo').value = '';
            document.getElementById('filter-estado').value = '';
            
            const incidenciasLoaded = await this.loadIncidencias();
            
            if (incidenciasLoaded) {
                this.renderMapMarkers();
                this.updateCounter();
                this.showNotification('Datos actualizados', 'success');
            } else {
                this.showNotification('Error al actualizar datos', 'error');
            }
        } catch (error) {
            this.showNotification('Error al actualizar', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async compararJSONsAuto() {
        this.showLoading(true);
        
        try {
            const response = await this.fetchAPI('/ejecutar_comparacion.php', {
                method: 'POST'
            });
            
            if (response.success) {
                const notification = document.createElement('div');
                notification.className = 'notification success comparison-result';
                notification.innerHTML = `
                    <div><strong>Estados actualizados correctamente</strong></div>
                    <div class="result-summary">
                        <div class="result-item">
                            <strong>${response.imported}</strong><br>
                            <small>Nuevas (Activas)</small>
                        </div>
                        <div class="result-item">
                            <strong>${response.updated}</strong><br>
                            <small>En Proceso</small>
                        </div>
                        <div class="result-item">
                            <strong>${response.resolved}</strong><br>
                            <small>Resueltas</small>
                        </div>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                        Total procesadas: ${response.total_processed}
                    </div>
                    ${response.files_compared ? `
                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">
                        ${response.files_compared.anterior} → ${response.files_compared.nuevo}
                    </div>
                    ` : ''}
                `;
                
                const container = document.getElementById('notifications');
                container.appendChild(notification);
                
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 15000);
                
                await this.refreshData();
                
            } else {
                this.showNotification(`Error: ${response.error}`, 'error');
            }
            
        } catch (error) {
            this.showNotification('Error al ejecutar comparación automática: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async showSection(section) {
        document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`btn-${section}`).classList.add('active');

        document.querySelectorAll('.map-section, .stats-section, .favorites-section').forEach(sec => {
            sec.style.display = 'none';
        });

        const sectionElement = document.getElementById(`${section}-section`);
        sectionElement.style.display = 'block';

        if (section === 'estadisticas') {
            await this.loadEstadisticas();
        } else if (section === 'favoritos') {
            await this.loadFavoritos();
        } else if (section === 'mapa') {
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 100);
        }
    }

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
            this.showNotification('Error cargando favoritos', 'error');
        }
    }

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

    async showIncidenciaModal() {
        if (!this.cache.provincias || this.cache.provincias.length === 0) {
            await this.loadProvincias();
        }
        
        if (this.cache.provincias) {
            const selectProvincias = document.getElementById('incidencia-provincia');
            selectProvincias.innerHTML = '<option value="">Selecciona una provincia</option>';
            this.cache.provincias.forEach(provincia => {
                const option = document.createElement('option');
                option.value = provincia;
                option.textContent = provincia;
                selectProvincias.appendChild(option);
            });
        }
        
        document.getElementById('incidencia-modal').style.display = 'flex';
        
        setTimeout(() => this.initMiniMap(), 100);
    }

    hideIncidenciaModal() {
        document.getElementById('incidencia-modal').style.display = 'none';
        document.getElementById('form-nueva-incidencia').reset();
        document.getElementById('coord-lat').textContent = '--';
        document.getElementById('coord-lng').textContent = '--';
        
        if (this.miniMap) {
            this.miniMap.remove();
            this.miniMap = null;
            this.miniMapMarker = null;
        }
    }

    initMiniMap() {
        const mapContainer = document.getElementById('ubicacion-map');
        
        if (this.miniMap || !L) {
            return;
        }

        try {
            this.miniMap = L.map('ubicacion-map').setView([41.6518, -4.7245], 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 18
            }).addTo(this.miniMap);

            this.miniMap.on('click', (e) => {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                document.getElementById('incidencia-latitud').value = lat;
                document.getElementById('incidencia-longitud').value = lng;

                document.getElementById('coord-lat').textContent = lat.toFixed(4);
                document.getElementById('coord-lng').textContent = lng.toFixed(4);

                if (this.miniMapMarker) {
                    this.miniMapMarker.setLatLng([lat, lng]);
                } else {
                    this.miniMapMarker = L.marker([lat, lng], {
                        icon: L.icon({
                            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41]
                        })
                    }).addTo(this.miniMap);
                }
            });

        } catch (error) {
            // Error silencioso en inicialización de mini mapa
        }
    }

    async handleNewIncidencia(e) {
        e.preventDefault();

        const tipo = document.getElementById('incidencia-tipo').value;
        const descripcion = document.getElementById('incidencia-descripcion').value;
        const provincia = document.getElementById('incidencia-provincia').value;
        const carretera = document.getElementById('incidencia-carretera').value;
        const pk = document.getElementById('incidencia-pk').value;
        const latitud = document.getElementById('incidencia-latitud').value;
        const longitud = document.getElementById('incidencia-longitud').value;

        // Validar campos obligatorios
        if (!tipo || !descripcion || !provincia || !carretera || !latitud || !longitud) {
            this.showNotification('Por favor completa todos los campos requeridos', 'warning');
            return;
        }

        this.showLoading(true);

        try {
            const payload = {
                action: 'create',
                tipo,
                descripcion,
                provincia,
                carretera,
                pk: pk || null,
                latitud: parseFloat(latitud),
                longitud: parseFloat(longitud),
                estado: 'activa'
            };

            const response = await this.fetchAPI('/incidencias.php', {
                method: 'POST',
                body: JSON.stringify(payload)
            });

            if (response.success) {
                this.showNotification('Incidencia reportada correctamente', 'success');
                this.hideIncidenciaModal();
                
                // Limpiar filtros antes de recargar
                document.getElementById('filter-provincia').value = '';
                document.getElementById('filter-tipo').value = '';
                document.getElementById('filter-estado').value = '';
                
                // IMPORTANTE: Forzar recarga sin caché
                this.cache.lastFetch = null; // Invalidar caché completamente
                
                const incidenciasLoaded = await this.loadIncidencias(); // Sin filtros
                
                if (incidenciasLoaded) {
                    this.renderMapMarkers();
                    this.updateCounter();
                } else {
                    console.error('Error cargando incidencias después de crear');
                }
            } else {
                this.showNotification(response.message || 'Error al reportar incidencia', 'error');
            }
        } catch (error) {
            this.showNotification('Error al reportar incidencia: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
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
            // Error silencioso en logout
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
                await this.loadFavoritos();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error al eliminar favorito', 'error');
        }
    }

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
        
        const firstOption = select.children[0];
        select.innerHTML = '';
        select.appendChild(firstOption);
        
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option;
            select.appendChild(optionElement);
        });
        
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
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new OpenRoadCyL();
    
    // Funcionalidad del menú móvil
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic en un enlace de navegación
        const navButtons = navMenu.querySelectorAll('.nav-btn, .btn-auth, .btn-logout');
        navButtons.forEach(button => {
            button.addEventListener('click', () => {
                mobileMenuToggle.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
        
        // Cerrar menú al hacer clic fuera de él
        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && !navMenu.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
        
        // Cerrar menú al cambiar el tamaño de la ventana (si se vuelve a desktop)
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                mobileMenuToggle.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    }
});

window.addEventListener('beforeunload', () => {
    if (window.app && window.app.charts) {
        Object.values(window.app.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
    }
});