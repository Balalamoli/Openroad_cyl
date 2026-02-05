# � OpenRoadCyL - Sistema de Gestión de Incidencias de Tráfico

## 🎯 **¿Qué es OpenRoadCyL?**

OpenRoadCyL es una aplicación web completa para la **gestión y visualización de incidencias de tráfico en Castilla y León**. El sistema se integra con la API oficial de Datos Abiertos de la Junta de Castilla y León para proporcionar información actualizada en tiempo real sobre el estado de las carreteras.

## ⚡ **Instalación Rápida**

### **Requisitos:**
- PHP 7.4+ 
- MySQL 5.7+
- Apache/Nginx

### **Pasos:**
```bash
# 1. Descargar proyecto
# Descomprimir en tu servidor web

# 2. Instalar base de datos (UN SOLO COMANDO)
mysql -u root -p < database/bd_final.sql

# 3. Configurar conexión
# Editar backend/config/database.php con tus credenciales

# 4. Configurar nombre de proyecto
# Editar frontend/js/main.js línea 9:
const projectName = 'tu_nombre_carpeta';

# 5. ¡Listo para usar!
```

### **Usuarios incluidos:**
- **Admin:** `admin@openroadcyl.es` / `admin123`
- **Test:** `test@openroadcyl.es` / `test123`

## 🌟 **Características Principales**

### **🗺️ Mapa Interactivo**
- Visualización de todas las incidencias en Castilla y León
- Marcadores con colores por tipo de incidencia
- Información detallada en popups
- Filtros por provincia, tipo y estado

### **📊 Estadísticas en Tiempo Real**
- Gráficos por provincia y tipo
- Contadores dinámicos
- Colores sincronizados con el mapa
- Datos actualizados automáticamente

### **👤 Sistema de Usuarios**
- Registro y login seguro
- Favoritos personales por usuario
- Sesiones seguras con timeout
- Separación completa de datos

### **🔄 Actualización Automática**
- Descarga datos oficiales cada 30 minutos
- Comparación inteligente de cambios
- Estados automáticos: **activa**, **en_proceso**, **resuelta**
- Logs detallados de operaciones

### **� Diseño Responsive**
- Funciona en móviles, tablets y desktop
- Menú hamburguesa en dispositivos pequeños
- Mapas optimizados para touch
- Experiencia consistente

## 🎨 **Estados y Tipos de Incidencias**

### **Estados Automáticos:**
| Estado | Color | Descripción |
|--------|-------|-------------|
| 🟢 **activa** | Verde | Nueva incidencia detectada |
| 🟡 **en_proceso** | Amarillo | Incidencia que continúa |
| � **resuelta** | Rojo | Incidencia solucionada |

### **Tipos de Incidencias:**
| Tipo | Color | Ejemplos |
|------|-------|----------|
| **Accidente** | � Rojo | Colisiones, vuelcos |
| **Obras** | � Naranja | Mantenimiento, construcción |
| **Meteorológica** | 🔵 Azul | Nieve, hielo, desprendimientos |
| **Retención** | 🟣 Morado | Tráfico denso, cortes |

## 🏗️ **Arquitectura del Sistema**

### **Frontend:**
- **HTML5 + CSS3 + JavaScript ES6+**
- **Leaflet** para mapas interactivos
- **Chart.js** para gráficos estadísticos
- **Diseño responsive** mobile-first

### **Backend:**
- **PHP 7.4+** con arquitectura MVC
- **MySQL** con índices optimizados
- **REST APIs** con respuestas JSON
- **Seguridad** multicapa

### **Integración:**
- **API JCyL** para datos oficiales
- **Nominatim** para geocodificación
- **OpenStreetMap** para tiles de mapa

## � **Esteructura del Proyecto**

```
openroadcyl/
├── frontend/                 # Interfaz de usuario
│   ├── index.html           # Página principal
│   ├── js/main.js           # Lógica JavaScript
│   ├── css/styles.css       # Estilos
│   └── pages/               # Páginas adicionales
├── backend/                 # Servidor y APIs
│   ├── api/                 # Endpoints REST
│   ├── controllers/         # Lógica de negocio
│   ├── models/              # Modelos de datos
│   ├── services/            # Servicios (actualización)
│   ├── config/              # Configuración
│   ├── data/                # Archivos JSON
│   └── logs/                # Logs del sistema
└── database/                # Scripts de base de datos
    └── bd_final.sql         # Instalación completa
```

## 🔧 **Configuración Avanzada**

### **Actualización Automática (Cron):**
```bash
# Ejecutar cada 30 minutos
*/30 * * * * /usr/bin/php /path/to/backend/scripts/update_data.php
```

### **Configuración de Rutas:**
```javascript
// En frontend/js/main.js línea 9
const projectName = 'mi_proyecto'; // Cambiar por tu carpeta
```

### **Base de Datos:**
```php
// En backend/config/database.php
private $host = 'localhost';
private $db_name = 'openroadcyl';
private $username = 'tu_usuario';
private $password = 'tu_password';
```

## �️ **Seguridad Implementada**

- **Contraseñas hasheadas** con bcrypt
- **Prepared statements** contra SQL Injection
- **Headers de seguridad** HTTP
- **Validación y sanitización** de entrada
- **Sesiones seguras** con timeout
- **Logs de auditoría** completos

## 📊 **Rendimiento**

- ⚡ **Carga inicial:** < 2 segundos
- 🔄 **Actualización:** Cada 30 minutos
- 💾 **Cache geocodificación:** 90%+ hit rate
- 📈 **Consultas optimizadas** con índices

## 📚 **Documentación Técnica Detallada**

Para información técnica completa, consulta la documentación en `/docs/`:

- **[Frontend](docs/01-FRONTEND-INTERFAZ.md)** - Interfaz y JavaScript
- **[Backend](docs/02-BACKEND-APIS.md)** - APIs y controladores  
- **[Actualización](docs/03-SISTEMA-ACTUALIZACION.md)** - Sistema automático
- **[Base de Datos](docs/04-BASE-DATOS.md)** - Estructura y consultas
- **[Integración JCyL](docs/05-INTEGRACION-JCYL.md)** - API oficial
- **[Seguridad](docs/06-SEGURIDAD-AUTENTICACION.md)** - Autenticación
- **[Instalación](docs/07-INSTALACION-CONFIGURACION.md)** - Guía completa

## � **Enlaces Útiles**

- **API Oficial JCyL:** https://datosabiertos.jcyl.es/
- **Leaflet:** https://leafletjs.com/
- **Chart.js:** https://www.chartjs.org/
- **PHP PDO:** https://www.php.net/manual/en/book.pdo.php

## 🚀 **Características Técnicas**

### **Funcionalidades:**
✅ Mapa interactivo con marcadores  
✅ Sistema de usuarios con favoritos  
✅ Actualización automática de datos  
✅ Estadísticas y gráficos dinámicos  
✅ Diseño responsive  
✅ Filtros avanzados  
✅ Geocodificación con cache  
✅ Logs de seguridad  
✅ Estados automáticos de incidencias  
✅ Integración con API oficial  

### **Tecnologías:**
- **Frontend:** HTML5, CSS3, JavaScript ES6+, Leaflet, Chart.js
- **Backend:** PHP 7.4+, MySQL 5.7+, PDO, REST APIs
- **Seguridad:** bcrypt, prepared statements, headers HTTP
- **Integración:** API JCyL, Nominatim, OpenStreetMap

---

**OpenRoadCyL** - Sistema completo de gestión de incidencias de tráfico para Castilla y León  
*Desarrollado para mejorar la información vial en la comunidad autónoma* 🚗💨