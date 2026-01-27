<?php
/**
 * OpenRoadCyL - Importador de datos JCyL
 * Lee datos abiertos de la Junta de Castilla y León
 */

require_once '../models/Incidencia.php';

class ImporterJCyL {
    private $incidencia;
    
    // Coordenadas aproximadas de provincias CyL
    private $provincias = [
        'Ávila' => ['lat' => 40.66, 'lng' => -4.69],
        'Burgos' => ['lat' => 42.34, 'lng' => -3.69],
        'León' => ['lat' => 42.6, 'lng' => -5.5],
        'Palencia' => ['lat' => 42.0, 'lng' => -4.53],
        'Salamanca' => ['lat' => 40.97, 'lng' => -5.66],
        'Soria' => ['lat' => 41.77, 'lng' => -2.47],
        'Segovia' => ['lat' => 40.95, 'lng' => -4.12],
        'Valladolid' => ['lat' => 41.65, 'lng' => -4.73],
        'Zamora' => ['lat' => 41.50, 'lng' => -5.75]
    ];

    public function __construct() {
        try {
            $this->incidencia = new Incidencia();
        } catch (Exception $e) {
            throw new Exception("Error inicializando modelo Incidencia: " . $e->getMessage());
        }
    }

    /**
     * Importar incidencias desde archivo JSON de JCyL
     */
    public function importFromFile($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("Archivo no encontrado: $filePath");
            }

            $json = file_get_contents($filePath);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON inválido: " . json_last_error_msg());
            }

            if (!isset($data['incidencias']) || !is_array($data['incidencias'])) {
                throw new Exception("Estructura JSON incorrecta - no hay campo 'incidencias'");
            }

            return $this->processIncidents($data['incidencias']);

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'imported' => 0,
                'skipped' => 0
            ];
        }
    }

    /**
     * Procesar lista de incidencias
     */
    private function processIncidents($incidents) {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($incidents as $index => $inc) {
            try {
                if ($this->processIncident($inc)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (Exception $e) {
                $skipped++;
                $errors[] = "Fila $index: " . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => count($incidents),
            'errors' => $errors
        ];
    }

    /**
     * Procesar una incidencia individual
     */
    private function processIncident($inc) {
        // Validar campos obligatorios
        if (empty($inc['Provincia']) || empty($inc['Via'])) {
            throw new Exception("Provincia o Vía vacíos");
        }

        // Verificar si ya existe
        if ($this->incidentExists($inc)) {
            return false;
        }

        // Mapear tipo de incidencia JCyL a nuestros tipos
        $tipo = $this->mapType($inc['Tipo'] ?? 'Precaución', $inc['Causa'] ?? '');
        
        // Obtener coordenadas
        $coords = $this->getCoordinates($inc['Provincia']);
        
        // Construir descripción
        $descripcion = $this->getDescription($inc);
        
        // Preparar datos para guardar
        $this->incidencia->tipo = $tipo;
        $this->incidencia->descripcion = $descripcion;
        $this->incidencia->provincia = $inc['Provincia'];
        $this->incidencia->carretera = $inc['Via'];
        $this->incidencia->pk = $inc['PKInicio'] ?? null;
        $this->incidencia->latitud = $coords['lat'];
        $this->incidencia->longitud = $coords['lng'];
        $this->incidencia->estado = 'activa';

        return $this->incidencia->create();
    }

    /**
     * Verificar si la incidencia ya existe
     */
    private function incidentExists($inc) {
        try {
            $existentes = $this->incidencia->getAll(
                $inc['Provincia'] ?? null,
                null,
                'activa'
            );

            foreach ($existentes as $ex) {
                // Comparar carretera y punto kilométrico
                if ($ex['carretera'] === $inc['Via'] && 
                    abs($ex['pk'] - ($inc['PKInicio'] ?? 0)) < 1) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mapear tipo de incidencia JCyL a nuestros tipos
     */
    private function mapType($tipo, $causa) {
        $tipo = strtolower($tipo);
        $causa = strtolower($causa ?? '');

        // Mapeos directos
        $map = [
            'obras' => 'Obras',
            'nieve' => 'Meteorológica',
            'hielo' => 'Meteorológica',
            'cadenas' => 'Meteorológica',
            'cerrada' => 'Retención',
            'cortada' => 'Retención',
            'accidente' => 'Accidente',
            'inundación' => 'Meteorológica',
            'desprendimientos' => 'Meteorológica'
        ];

        // Intentar mapear por causa primero
        foreach ($map as $key => $val) {
            if (strpos($causa, $key) !== false) {
                return $val;
            }
        }

        // Luego por tipo
        foreach ($map as $key => $val) {
            if (strpos($tipo, $key) !== false) {
                return $val;
            }
        }

        // Default
        return 'Retención';
    }

    /**
     * Obtener coordenadas por provincia
     */
    private function getCoordinates($provincia) {
        if (isset($this->provincias[$provincia])) {
            return $this->provincias[$provincia];
        }
        // Default a Valladolid si no encuentra
        return $this->provincias['Valladolid'];
    }

    /**
     * Construir descripción de la incidencia
     */
    private function getDescription($inc) {
        $parts = [];
        
        if (!empty($inc['Tramo'])) {
            $parts[] = $inc['Tramo'];
        }
        
        if (!empty($inc['Causa'])) {
            $parts[] = "Causa: " . $inc['Causa'];
        }
        
        if (!empty($inc['Observaciones']) && $inc['Observaciones'] !== '--') {
            $parts[] = $inc['Observaciones'];
        }
        
        $desc = implode(". ", $parts);
        return substr($desc, 0, 500); // Limitar a 500 caracteres
    }
}
?>
