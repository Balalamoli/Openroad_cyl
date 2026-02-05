# 📚 Documentación OpenRoadCyL

## 🎯 **Resumen del Sistema**

OpenRoadCyL es una aplicación web completa para la gestión y visualización de incidencias de tráfico en Castilla y León. El sistema se integra con la API oficial de Datos Abiertos de la Junta de Castilla y León para proporcionar información actualizada en tiempo real sobre el estado de las carreteras.

## 📁 **Estructura de la Documentación**

### **01. Frontend - Interfaz de Usuario**
**Archivo:** [`01-FRONTEND-INTERFAZ.md`](01-FRONTEND-INTERFAZ.md)

**Contenido:**
- 🎨 Arquitectura del frontend (HTML, CSS, JavaScript)
- 🗺️ Sistema de mapas interactivos con Leaflet
- 📊 Gráficos estadísticos con Chart.js
- 🔄 Gestión de datos y cache inteligente
- 📱 Diseño responsive y menú móvil
- 🔗 Integración con APIs del backend

**Tecnologías:** HTML5, CSS3, JavaScript ES6+, Leaflet, Chart.js

---

### **02. Backend - APIs y Controladores**
**Archivo:** [`02-BACKEND-APIS.md`](02-BACKEND-APIS.md)

**Contenido:**
- 🌐 Endpoints REST para incidencias, usuarios y actualización
- 🎛️ Controladores de lógica de negocio
- 🗄️ Modelos de datos y acceso a base de datos
- 🔐 Sistema de autenticación y autorización
- 📊 Generación de estadísticas y reportes
- 🛡️ Medidas de seguridad implementadas

**Tecnologías:** PHP 7.4+, MySQL, PDO, REST APIs

---

### **03. Sistema de Actualización Automática**
**Archivo:** [`03-SISTEMA-ACTUALIZACION.md`](03-SISTEMA-ACTUALIZACION.md)

**Contenido:**
- 🔄 Descarga automática de datos desde API JCyL
- 🧠 Comparación inteligente de JSONs
- 📊 Determinación automática de estados de incidencias
- 🌍 Sistema de geocodificación con cache
- ⚡ Optimizaciones de rendimiento
- 📈 Métricas y monitoreo del sistema

**Características:** Actualización cada 30 minutos, comparación automática, logs detallados

---

### **04. Base de Datos**
**Archivo:** [`04-BASE-DATOS.md`](04-BASE-DATOS.md)

**Contenido:**
- 🏗️ Estructura completa de tablas
- 🔍 Índices y optimizaciones de consultas
- 🔄 Flujos de datos principales
- 🛡️ Integridad referencial y constraints
- 📊 Consultas típicas y estadísticas
- 🔧 Scripts de mantenimiento

**Tablas:** usuarios, incidencias, favoritos, carreteras_geocache

---

### **05. Integración con API JCyL**
**Archivo:** [`05-INTEGRACION-JCYL.md`](05-INTEGRACION-JCYL.md)

**Contenido:**
- 🌐 Conexión con Datos Abiertos de la Junta de Castilla y León
- 📊 Estructura y mapeo de datos oficiales
- 🔄 Transformación de formatos de datos
- 🗺️ Geocodificación de ubicaciones
- 🛡️ Manejo robusto de errores
- 📈 Estadísticas de integración

**Fuente:** https://datosabiertos.jcyl.es/web/jcyl/risp/es/transporte/incidencias_carreteras/

---

### **06. Seguridad y Autenticación**
**Archivo:** [`06-SEGURIDAD-AUTENTICACION.md`](06-SEGURIDAD-AUTENTICACION.md)

**Contenido:**
- 🔐 Sistema de login y registro de usuarios
- 🔒 Hashing seguro de contraseñas (bcrypt)
- 🛡️ Protección contra ataques comunes (SQL Injection, XSS, CSRF)
- 📊 Logging de eventos de seguridad
- 🔍 Validación y sanitización de entrada
- 🌐 Headers de seguridad HTTP

**Características:** Sesiones seguras, prepared statements, logs de auditoría

---

### **07. Instalación y Configuración**
**Archivo:** [`07-INSTALACION-CONFIGURACION.md`](07-INSTALACION-CONFIGURACION.md)

**Contenido:**
- 📋 Requisitos del sistema
- 🚀 Guía de instalación paso a paso
- ⚙️ Configuración de servidor web (Apache/Nginx)
- 🔧 Configuración de base de datos
- 🔄 Instalación de actualización automática (cron)
- 🌐 Configuración SSL y dominio
- 🔍 Verificación y troubleshooting

**Requisitos:** PHP 7.4+, MySQL 5.7+, Apache/Nginx

---

## 🎯 **Características Principales del Sistema**

### **🗺️ Visualización Interactiva**
- Mapa de Castilla y León con marcadores de incidencias
- Colores diferenciados por tipo de incidencia
- Popups informativos con detalles completos
- Filtros dinámicos por provincia, tipo y estado

### **📊 Estadísticas en Tiempo Real**
- Gráficos por provincia y tipo de incidencia
- Contadores dinámicos de incidencias activas
- Colores sincronizados entre mapa y estadísticas
- Actualización automática de datos

### **👤 Gestión de Usuarios**
- Sistema de registro y login seguro
- Gestión personal de incidencias favoritas
- Separación de datos por usuario
- Sesiones seguras con timeout automático

### **🔄 Actualización Automática**
- Descarga cada 30 minutos desde API oficial
- Comparación inteligente de datos
- Estados automáticos: activa, en_proceso, resuelta
- Logs detallados de todas las operaciones

### **📱 Diseño Responsive**
- Interfaz adaptada para móviles y tablets
- Menú hamburguesa en dispositivos pequeños
- Mapas optimizados para touch
- Experiencia consistente en todos los dispositivos

## 🔧 **Arquitectura Técnica**

### **Frontend**
```
HTML5 + CSS3 + JavaScript ES6+
├── Leaflet (Mapas interactivos)
├── Chart.js (Gráficos estadísticos)
├── Fetch API (Comunicación con backend)
└── Responsive Design (Mobile-first)
```

### **Backend**
```
PHP 7.4+ + MySQL 5.7+
├── REST APIs (JSON responses)
├── MVC Pattern (Models, Controllers)
├── PDO (Database abstraction)
└── Security Layer (Authentication, validation)
```

### **Integración Externa**
```
APIs Externas
├── Junta de Castilla y León (Datos oficiales)
├── Nominatim (Geocodificación)
└── OpenStreetMap (Tiles de mapa)
```

## 📊 **Estados de Incidencias**

| Estado | Color | Descripción | Condición |
|--------|-------|-------------|-----------|
| 🟢 **activa** | Verde | Nueva incidencia | Solo en JSON nuevo |
| 🟡 **en_proceso** | Amarillo | Incidencia continúa | En ambos JSONs |
| 🔴 **resuelta** | Rojo | Incidencia solucionada | Solo en JSON anterior |

## 🎨 **Tipos de Incidencias**

| Tipo | Color | Icono | Ejemplos |
|------|-------|-------|----------|
| **Accidente** | 🔴 Rojo | 🚗 | Colisiones, vuelcos |
| **Obras** | 🟠 Naranja | 🚧 | Mantenimiento, construcción |
| **Meteorológica** | 🔵 Azul | 🌨️ | Nieve, hielo, desprendimientos |
| **Retención** | 🟣 Morado | 🚦 | Tráfico denso, cortes |

## 🚀 **Instalación Rápida**

```bash
# 1. Clonar/descargar proyecto
git clone https://github.com/tu-usuario/openroadcyl.git

# 2. Configurar base de datos
mysql -u root -p < database/create_tables.sql
mysql -u root -p < database/create_geocache_table.sql

# 3. Configurar conexión
# Editar backend/config/database.php

# 4. Configurar servidor web
# Copiar configuración Apache/Nginx

# 5. Instalar actualización automática
crontab -e
# Agregar: */30 * * * * /usr/bin/php /path/to/backend/scripts/update_data.php
```

## 📈 **Métricas del Sistema**

### **Rendimiento:**
- ⚡ Carga inicial: < 2 segundos
- 🔄 Actualización de datos: 30 minutos
- 💾 Cache de geocodificación: 90%+ hit rate
- 📊 Consultas optimizadas con índices

### **Seguridad:**
- 🔐 Contraseñas hasheadas con bcrypt
- 🛡️ Prepared statements (SQL Injection)
- 🔒 Headers de seguridad HTTP
- 📊 Logs de auditoría completos

### **Escalabilidad:**
- 👥 Soporte multi-usuario
- 📊 Separación de datos por usuario
- 🔄 Sistema de cache inteligente
- 📈 Arquitectura preparada para crecimiento

## 🔗 **Enlaces Útiles**

- **API Oficial JCyL:** https://datosabiertos.jcyl.es/
- **Leaflet Documentation:** https://leafletjs.com/
- **Chart.js Documentation:** https://www.chartjs.org/
- **PHP PDO Manual:** https://www.php.net/manual/en/book.pdo.php
- **MySQL Documentation:** https://dev.mysql.com/doc/

## 📞 **Soporte y Contacto**

Para consultas técnicas, problemas de instalación o sugerencias de mejora, consulta la documentación específica de cada componente o revisa los logs del sistema para información detallada sobre errores.

---

**OpenRoadCyL** - Sistema de Gestión de Incidencias de Tráfico para Castilla y León
*Desarrollado con ❤️ para mejorar la información vial en la comunidad autónoma*