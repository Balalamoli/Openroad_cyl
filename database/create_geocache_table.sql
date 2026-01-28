-- Tabla para cachear coordenadas de carreteras geocodificadas
CREATE TABLE IF NOT EXISTS carreteras_geocache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    via VARCHAR(50) NOT NULL,
    provincia VARCHAR(50) NOT NULL,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    fecha_geocodificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_via_provincia (via, provincia),
    INDEX idx_via (via),
    INDEX idx_provincia (provincia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
