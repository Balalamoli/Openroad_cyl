# OpenRoadCyL - Sistema de Gestión de Incidencias de Tráfico

Sistema intermodular para la gestión de incidencias de tráfico en Castilla y León, desarrollado con arquitectura MVC pura en PHP y frontend SPA moderno.

## 🚀 Características Principales

- **Arquitectura MVC** pura en PHP
- **Single Page Application (SPA)** con JavaScript ES6
- **Mapas interactivos** con Leaflet.js
- **Estadísticas visuales** con Chart.js
- **Sistema de autenticación** seguro
- **Green Coding** implementado para sostenibilidad
- **API REST** interna optimizada
- **Gestión de favoritos** por usuario

## 📁 Estructura del Proyecto

```
OpenRoadCyL/
├── backend/
│   ├── api/
│   │   ├── fetch_data.php      # Obtención de datos externos con caché
│   │   ├── incidencias.php     # API REST de incidencias
│   │   └── usuarios.php        # API REST de usuarios
│   ├── config/
│   │   └── database.php        # Configuración de base de datos
│   ├── controllers/
│   │   ├── IncidenciaController.php
│   │   └── UsuarioController.php
│   ├── models/
│   │   ├── Incidencia.php
│   │   └── Usuario.php
│   └── views/                  # (Para futuras vistas PHP si necesario)
├── frontend/
│   ├── css/
│   │   └── styles.css          # Estilos optimizados y responsivos
│   ├── js/
│   │   └── main.js             # Aplicación SPA principal
│   ├── img/                    # Imágenes y recursos
│   └── index.html              # Página principal
├── database/
│   └── create_tables.sql       # Script de creación de BD
├── GREEN_CODING_DOCUMENTATION.md
└── README.md
```

## 🛠️ Instalación y Configuración

### Requisitos Previos

- **PHP 7.4+** con extensiones PDO y MySQL
- **MySQL 5.7+** o **MariaDB 10.3+**
- **Servidor web** (Apache/Nginx) con soporte PHP
- **Navegador moderno** con soporte ES6

### Paso 1: Configurar Base de Datos

1. Crear la base de datos:
```sql
mysql -u root -p < database/create_tables.sql
```

2. Configurar credenciales en `backend/config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'openroadcyl';
private $username = 'tu_usuario';
private $password = 'tu_contraseña';
```

### Paso 2: Configurar Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ backend/api/$1 [L]
```

#### Nginx
```nginx
location /api/ {
    try_files $uri $uri/ /backend/api/$1;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### Paso 3: Permisos y Seguridad

```bash
# Permisos de archivos
chmod 644 frontend/*.html frontend/css/*.css frontend/js/*.js
chmod 755 backend/api/*.php
chmod 600 backend/config/database.php

# Configurar sesiones PHP (php.ini)
session.cookie_httponly = 1
session.use_only_cookies = 1
session.cookie_secure = 1  # Solo en HTTPS
```

## 🚀 Uso del Sistema

### Acceso Principal
Abrir `frontend/index.html` en el navegador o configurar el servidor web para servir desde la carpeta `frontend/`.

### Funcionalidades Principales

1. **Visualización de Incidencias**
   - Mapa interactivo con marcadores por tipo
   - Filtros por provincia, tipo y estado
   - Información detallada en popups

2. **Estadísticas**
   - Gráfico de barras por provincia
   - Gráfico circular por tipo de incidencia
   - Datos actualizados en tiempo real

3. **Sistema de Usuarios**
   - Registro e inicio de sesión
   - Gestión de incidencias favoritas
   - Sesiones seguras con PHP

4. **Actualización de Datos**
   - Caché inteligente de 1 hora
   - Actualización manual disponible
   - Simulación de API de Junta de CyL

## 🌱 Green Coding Implementado

El sistema implementa múltiples técnicas de sostenibilidad:

- **Caché inteligente** para reducir peticiones API
- **Compresión GZIP** en respuestas
- **Consultas optimizadas** con índices de BD
- **Transferencia de datos minificada**
- **Carga paralela** de recursos
- **Gestión eficiente de memoria**

Ver `GREEN_CODING_DOCUMENTATION.md` para detalles completos.

## 🔧 API Endpoints

### Incidencias
```
GET  /api/incidencias.php?action=list[&provincia=X&tipo=Y&estado=Z]
GET  /api/incidencias.php?action=detail&id=X
GET  /api/incidencias.php?action=stats-provincia
GET  /api/incidencias.php?action=stats-tipo
GET  /api/incidencias.php?action=provincias
GET  /api/incidencias.php?action=tipos
POST /api/incidencias.php (crear nueva incidencia)
```

### Usuarios
```
GET  /api/usuarios.php?action=session
GET  /api/usuarios.php?action=profile
GET  /api/usuarios.php?action=favoritos
GET  /api/usuarios.php?action=logout
POST /api/usuarios.php (login, register, gestionar favoritos)
```

### Actualización de Datos
```
GET /api/fetch_data.php (actualizar desde API externa)
```

## 🔒 Seguridad Implementada

- **PDO con prepared statements** para prevenir SQL injection
- **password_hash()** para encriptación de contraseñas
- **Sesiones seguras** con regeneración de ID
- **Validación de entrada** en todos los endpoints
- **Headers de seguridad** CORS configurados
- **Sanitización de datos** antes de almacenamiento

## 📱 Responsive Design

El sistema es completamente responsivo con:
- **Diseño móvil-first**
- **Breakpoints optimizados** para tablet y desktop
- **Mapas adaptables** a diferentes tamaños de pantalla
- **Navegación táctil** optimizada

## 🧪 Testing y Desarrollo

### Datos de Prueba
El sistema incluye datos de ejemplo:
- Usuario: `admin@openroadcyl.es` / `password`
- Usuario: `test@openroadcyl.es` / `password`
- Incidencias de ejemplo en todas las provincias

### Desarrollo Local
```bash
# Servidor PHP integrado
php -S localhost:8000 -t frontend/

# O usar XAMPP/WAMP/MAMP
```

## 🤝 Contribución

1. Fork del proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para detalles.

## 📞 Soporte

Para soporte técnico o consultas:
- **Email**: soporte@openroadcyl.es
- **Documentación**: Ver archivos MD en el repositorio
- **Issues**: Usar el sistema de issues de GitHub

---

**OpenRoadCyL** - Desarrollado con ❤️ y sostenibilidad para Castilla y León