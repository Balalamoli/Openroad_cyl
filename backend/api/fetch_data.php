<?php
/**
 * OpenRoadCyL - Fetch Data from External API
 * Script para obtener datos de la Junta de CyL con sistema de caché
 * Implementa Green Coding mediante caché inteligente
 */

require_once '../config/database.php';

class DataFetcher {
    private $db;
    private $cache_duration = 3600; // 1 hora en segundos (Green Coding)
    private $api_url = 'https://datosabiertos.jcyl.es/web/jcyl/risp/es/transporte/incidencias-trafico';
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Verifica si necesita actualizar datos basado en caché
     * Green Coding: Evita llamadas innecesarias a API externa
     */
    private function needsUpdate() {
        $query = "SELECT MAX(fecha_actualizacion) as ultima_actualizacion FROM incidencias WHERE fuente = 'jcyl_api'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if (!$result['ultima_actualizacion']) {
            return true; // No hay datos, necesita actualizar
        }
        
        $ultima_actualizacion = strtotime($result['ultima_actualizacion']);
        $ahora = time();
        
        return ($ahora - $ultima_actualizacion) > $this->cache_duration;
    }

    /**
     * Simula datos de la API de la Junta de CyL
     * En producción, aquí iría la llamada real a la API
     */
    private function fetchFromExternalAPI() {
        // Simulación de datos de la API de la Junta de CyL
        // Green Coding: Datos minificados, solo campos esenciales
        return [
            [
                'tipo' => 'Accidente',
                'descripcion' => 'Vehículo averiado en carril derecho',
                'provincia' => 'Ávila',
                'carretera' => 'N-110',
                'pk' => '45.2',
                'latitud' => 40.6566,
                'longitud' => -4.6813,
                'estado' => 'activa'
            ],
            [
                'tipo' => 'Obras',
                'descripcion' => 'Trabajos de asfaltado nocturno',
                'provincia' => 'Segovia',
                'carretera' => 'A-601',
                'pk' => '78.5',
                'latitud' => 40.9429,
                'longitud' => -4.1088,
                'estado' => 'activa'
            ],
            [
                'tipo' => 'Meteorológica',
                'descripcion' => 'Hielo en calzada por bajas temperaturas',
                'provincia' => 'Soria',
                'carretera' => 'N-122',
                'pk' => '156.8',
                'latitud' => 41.7665,
                'longitud' => -2.4790,
                'estado' => 'activa'
            ],
            [
                'tipo' => 'Retención',
                'descripcion' => 'Tráfico lento por alta densidad',
                'provincia' => 'Palencia',
                'carretera' => 'A-67',
                'pk' => '89.3',
                'latitud' => 42.0096,
                'longitud' => -4.5288,
                'estado' => 'activa'
            ],
            [
                'tipo' => 'Accidente',
                'descripcion' => 'Colisión entre dos vehículos',
                'provincia' => 'Zamora',
                'carretera' => 'A-66',
                'pk' => '234.7',
                'latitud' => 41.5034,
                'longitud' => -5.7447,
                'estado' => 'en_proceso'
            ]
        ];
    }

    /**
     * Guarda los datos en la base de datos local
     * Green Coding: Transacciones para optimizar escritura
     */
    private function saveToDatabase($data) {
        try {
            $this->db->beginTransaction();
            
            // Limpiar datos antiguos de la API externa (Green Coding: evita duplicados)
            $delete_query = "DELETE FROM incidencias WHERE fuente = 'jcyl_api'";
            $this->db->prepare($delete_query)->execute();
            
            // Insertar nuevos datos
            $insert_query = "INSERT INTO incidencias (tipo, descripcion, provincia, carretera, pk, latitud, longitud, estado, fuente) 
                           VALUES (:tipo, :descripcion, :provincia, :carretera, :pk, :latitud, :longitud, :estado, 'jcyl_api')";
            
            $stmt = $this->db->prepare($insert_query);
            
            foreach ($data as $incidencia) {
                $stmt->execute([
                    ':tipo' => $incidencia['tipo'],
                    ':descripcion' => $incidencia['descripcion'],
                    ':provincia' => $incidencia['provincia'],
                    ':carretera' => $incidencia['carretera'],
                    ':pk' => $incidencia['pk'],
                    ':latitud' => $incidencia['latitud'],
                    ':longitud' => $incidencia['longitud'],
                    ':estado' => $incidencia['estado']
                ]);
            }
            
            $this->db->commit();
            return count($data);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error guardando datos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Método principal para actualizar datos
     * Green Coding: Solo actualiza si es necesario
     */
    public function updateData() {
        if (!$this->needsUpdate()) {
            return [
                'status' => 'cached',
                'message' => 'Datos actuales, usando caché (Green Coding)',
                'records' => 0
            ];
        }

        try {
            $data = $this->fetchFromExternalAPI();
            $records_saved = $this->saveToDatabase($data);
            
            return [
                'status' => 'updated',
                'message' => 'Datos actualizados desde API externa',
                'records' => $records_saved
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error actualizando datos: ' . $e->getMessage(),
                'records' => 0
            ];
        }
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header('Content-Type: application/json');
    
    $fetcher = new DataFetcher();
    $result = $fetcher->updateData();
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
?>