<?php
/**
 * OpenRoadCyL - Modelo Incidencia
 * Manejo de datos de incidencias de tráfico
 */

require_once '../config/database.php';

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
     * Crea nueva incidencia
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (tipo, descripcion, provincia, carretera, pk, latitud, longitud, estado) 
                  VALUES (:tipo, :descripcion, :provincia, :carretera, :pk, :latitud, :longitud, :estado)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->provincia = htmlspecialchars(strip_tags($this->provincia));
        $this->carretera = htmlspecialchars(strip_tags($this->carretera));

        $stmt->bindParam(':tipo', $this->tipo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':provincia', $this->provincia);
        $stmt->bindParam(':carretera', $this->carretera);
        $stmt->bindParam(':pk', $this->pk);
        $stmt->bindParam(':latitud', $this->latitud);
        $stmt->bindParam(':longitud', $this->longitud);
        $stmt->bindParam(':estado', $this->estado);

        return $stmt->execute();
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