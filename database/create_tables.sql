-- OpenRoadCyL - Script de creación de tablas
-- Base de datos para gestión de incidencias de tráfico en Castilla y León

CREATE DATABASE IF NOT EXISTS openroadcyl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE openroadcyl;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de incidencias
CREATE TABLE incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    provincia VARCHAR(50) NOT NULL,
    carretera VARCHAR(100) NOT NULL,
    pk VARCHAR(20),
    estado ENUM('activa', 'resuelta', 'en_proceso') DEFAULT 'activa',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    fuente VARCHAR(100) DEFAULT 'jcyl_api',
    INDEX idx_provincia (provincia),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado),
    INDEX idx_coordenadas (latitud, longitud)
);

-- Tabla de favoritos (relación usuario-incidencia)
CREATE TABLE favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    incidencia_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (usuario_id, incidencia_id)
);

-- Insertar datos de ejemplo para desarrollo
INSERT INTO usuarios (nombre, email, password) VALUES 
('Admin', 'admin@openroadcyl.es', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Usuario Test', 'test@openroadcyl.es', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertar incidencias de ejemplo
INSERT INTO incidencias (tipo, descripcion, provincia, carretera, pk, latitud, longitud) VALUES 
('Accidente', 'Colisión múltiple en A-6', 'León', 'A-6', '324', 42.5987, -5.5671),
('Obras', 'Obras de mantenimiento', 'Valladolid', 'A-62', '156', 41.6518, -4.7245),
('Meteorológica', 'Niebla densa', 'Burgos', 'A-1', '245', 42.3440, -3.6969),
('Retención', 'Tráfico denso por festividad', 'Salamanca', 'A-50', '89', 40.9701, -5.6635);