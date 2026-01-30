<?php
/**
 * OpenRoadCyL - Modelo Incidencia
 * Manejo de datos de incidencias de tráfico
 */

require_once __DIR__ . '/../config/database.php';

class Incidencia {
    private $conn;
    private $table_name = "incidencias";

    public $id;
    public $tipo;
    public $descripcion;
    public $provincia;
    public $carretera;
    public $pk;
    public $estado;
    public $fecha_creacion;
    public $fecha_actualizacion;
    public $latitud;
    public $longitud;
    public $fuente;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene todas las incidencias con filtros opcionales
     * Green Coding: Consulta optimizada con índices
     */
    public function getAll($provincia = null, $tipo = null, $estado = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filtros opcionales (Green Coding: solo consulta datos necesarios)
        if ($provincia) {
            $query .= " AND provincia = :provincia";
            $params[':provincia'] = $provincia;
        }
        
        if ($tipo) {
            $query .= " AND tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        
        if ($estado) {
            $query .= " AND estado = :estado";
            $params[':estado'] = $estado;
        }

        $query .= " ORDER BY fecha_actualizacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene incidencias por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas por provincia
     * Green Coding: Consulta agregada eficiente
     */
    public function getStatsByProvincia() {
        $query = "SELECT provincia, COUNT(*) as total, 
                         SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activas,
                         SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas,
                         SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso
                  FROM " . $this->table_name . " 
                  GROUP BY provincia 
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas por tipo
     */
    public function getStatsByTipo() {
        $query = "SELECT tipo, COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  GROUP BY tipo 
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si ya existe una incidencia duplicada
     * Compara por: carretera + pk + provincia + tipo
     * Maneja correctamente valores NULL en pk
     * @return bool true si existe duplicado
     */
    public function existeDuplicado() {
        // Normalizar datos para comparación
        $carretera_norm = trim(strtoupper($this->carretera));
        $provincia_norm = trim($this->provincia);
        $pk_norm = $this->pk ? trim($this->pk) : null;
        
        // Consulta que maneja correctamente valores NULL
        if ($pk_norm === null || $pk_norm === '') {
            // Si PK es NULL o vacío, buscar por carretera + provincia + tipo
            $query = "SELECT id FROM " . $this->table_name . " 
                      WHERE UPPER(TRIM(carretera)) = :carretera 
                      AND provincia = :provincia 
                      AND tipo = :tipo
                      AND (pk IS NULL OR pk = '' OR pk = '0')
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':carretera', $carretera_norm);
            $stmt->bindParam(':provincia', $provincia_norm);
            $stmt->bindParam(':tipo', $this->tipo);
        } else {
            // Si PK tiene valor, buscar por carretera + pk + provincia
            $query = "SELECT id FROM " . $this->table_name . " 
                      WHERE UPPER(TRIM(carretera)) = :carretera 
                      AND TRIM(pk) = :pk 
                      AND provincia = :provincia 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':carretera', $carretera_norm);
            $stmt->bindParam(':pk', $pk_norm);
            $stmt->bindParam(':provincia', $provincia_norm);
        }
        
        $stmt->execute();
        
        $existe = $stmt->rowCount() > 0;
        
        if ($existe) {
            error_log("Duplicado detectado: {$carretera_norm} PK {$pk_norm} - {$provincia_norm} - {$this->tipo}");
        }
        
        return $existe;
    }

    /**
     * Crea nueva incidencia
     * MODIFICADO: Ahora verifica duplicados antes de insertar y normaliza datos
     * @return bool true si se creó, false si ya existía o hubo error
     */
    public function create() {
        // Sanitizar y normalizar datos primero
        $this->tipo = htmlspecialchars(strip_tags(trim($this->tipo)));
        $this->descripcion = htmlspecialchars(strip_tags(trim($this->descripcion)));
        $this->provincia = htmlspecialchars(strip_tags(trim($this->provincia)));
        $this->carretera = htmlspecialchars(strip_tags(trim(strtoupper($this->carretera))));
        
        // Normalizar PK (convertir valores vacíos a NULL)
        if (empty($this->pk) || $this->pk === '0' || $this->pk === 0) {
            $this->pk = null;
        } else {
            $this->pk = trim($this->pk);
        }
        
        // Establecer estado por defecto si no está definido
        if (empty($this->estado)) {
            $this->estado = 'activa';
        }

        // VERIFICAR: Si ya existe una incidencia duplicada
        if ($this->existeDuplicado()) {
            error_log("Incidencia duplicada NO insertada: {$this->carretera} PK {$this->pk} - {$this->provincia} - {$this->tipo}");
            return false; // No insertar, ya existe
        }

        // Si no existe duplicado, insertar
        $query = "INSERT INTO " . $this->table_name . " 
                  (tipo, descripcion, provincia, carretera, pk, latitud, longitud, estado, fuente, fecha_creacion, fecha_actualizacion) 
                  VALUES (:tipo, :descripcion, :provincia, :carretera, :pk, :latitud, :longitud, :estado, :fuente, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);

        // Establecer fuente por defecto
        $fuente = $this->fuente ?? 'manual';

        $stmt->bindParam(':tipo', $this->tipo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':provincia', $this->provincia);
        $stmt->bindParam(':carretera', $this->carretera);
        $stmt->bindParam(':pk', $this->pk);
        $stmt->bindParam(':latitud', $this->latitud);
        $stmt->bindParam(':longitud', $this->longitud);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':fuente', $fuente);

        $resultado = $stmt->execute();
        
        if ($resultado) {
            error_log("Incidencia CREADA exitosamente: {$this->carretera} PK {$this->pk} - {$this->provincia} - {$this->tipo}");
        } else {
            error_log("Error al insertar incidencia: {$this->carretera} PK {$this->pk} - {$this->provincia}");
        }
        
        return $resultado;
    }

    /**
     * Obtiene las provincias disponibles
     */
    public function getProvincias() {
        try {
            $query = "SELECT DISTINCT provincia FROM " . $this->table_name . " ORDER BY provincia";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Error en Incidencia::getProvincias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los tipos disponibles
     */
    public function getTipos() {
        try {
            $query = "SELECT DISTINCT tipo FROM " . $this->table_name . " ORDER BY tipo";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Error en Incidencia::getTipos: " . $e->getMessage());
            return [];
        }
    }
}
?>