<?php
/**
 * OpenRoadCyL - Importador de datos JCyL
 * Sistema inteligente de comparación de JSONs para determinar estados de incidencias
 */

require_once __DIR__ . '/../models/Incidencia.php';
require_once __DIR__ . '/../config/database.php';

class ImporterJCyL {
    private $incidencia;
    private $pdo;

    public function __construct() {
        $this->incidencia = new Incidencia();
        $db = new Database();
        $this->pdo = $db->getConnection();
    }

    /**
     * Importar desde archivo JSON único (método original)
     */
    public function importFromFile($filePath) {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'Archivo no encontrado',
                'imported' => 0,
                'skipped' => 0
            ];
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['incidencias'])) {
            return [
                'success' => false,
                'error' => 'Formato de JSON inválido',
                'imported' => 0,
                'skipped' => 0
            ];
        }

        return $this->processIncidencias($data['incidencias'], 'activa');
    }

    /**
     * Comparar dos archivos JSON y determinar estados automáticamente
     * 
     * @param string $oldJsonPath Ruta al JSON anterior
     * @param string $newJsonPath Ruta al JSON nuevo
     * @return array Resultado de la comparación e importación
     */
    public function compareAndImport($oldJsonPath, $newJsonPath) {
        // Validar archivos
        if (!file_exists($oldJsonPath) || !file_exists($newJsonPath)) {
            return [
                'success' => false,
                'error' => 'Uno o ambos archivos JSON no existen',
                'imported' => 0,
                'updated' => 0,
                'resolved' => 0
            ];
        }

        // Cargar JSONs
        $oldData = json_decode(file_get_contents($oldJsonPath), true);
        $newData = json_decode(file_get_contents($newJsonPath), true);

        if (!$oldData || !$newData || !isset($oldData['incidencias']) || !isset($newData['incidencias'])) {
            return [
                'success' => false,
                'error' => 'Formato de JSON inválido en uno o ambos archivos',
                'imported' => 0,
                'updated' => 0,
                'resolved' => 0
            ];
        }

        // Crear índices para comparación rápida
        $oldIndex = $this->createIncidenciaIndex($oldData['incidencias']);
        $newIndex = $this->createIncidenciaIndex($newData['incidencias']);

        $stats = [
            'imported' => 0,  // Nuevas incidencias (solo en nuevo JSON)
            'updated' => 0,   // Incidencias que continúan (en ambos JSONs)
            'resolved' => 0   // Incidencias resueltas (solo en JSON anterior)
        ];

        try {
            $this->pdo->beginTransaction();

            // 1. Procesar incidencias NUEVAS (solo en nuevo JSON) -> Estado: 'activa'
            foreach ($newIndex as $key => $newIncidencia) {
                if (!isset($oldIndex[$key])) {
                    $result = $this->createIncidenciaFromJCyL($newIncidencia, 'activa');
                    if ($result) {
                        $stats['imported']++;
                    }
                }
            }

            // 2. Procesar incidencias CONTINUAS (en ambos JSONs) -> Estado: 'en_proceso'
            foreach ($newIndex as $key => $newIncidencia) {
                if (isset($oldIndex[$key])) {
                    $result = $this->updateIncidenciaState($newIncidencia, 'en_proceso');
                    if ($result) {
                        $stats['updated']++;
                    }
                }
            }

            // 3. Procesar incidencias RESUELTAS (solo en JSON anterior) -> Estado: 'resuelta'
            foreach ($oldIndex as $key => $oldIncidencia) {
                if (!isset($newIndex[$key])) {
                    $result = $this->updateIncidenciaState($oldIncidencia, 'resuelta');
                    if ($result) {
                        $stats['resolved']++;
                    }
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => "Comparación completada: {$stats['imported']} nuevas, {$stats['updated']} en proceso, {$stats['resolved']} resueltas",
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'resolved' => $stats['resolved'],
                'total_processed' => array_sum($stats)
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error en compareAndImport: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error durante la comparación: ' . $e->getMessage(),
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'resolved' => $stats['resolved']
            ];
        }
    }

    /**
     * Crear índice único para cada incidencia basado en campos clave
     * Esto permite identificar la misma incidencia entre diferentes JSONs
     */
    private function createIncidenciaIndex($incidencias) {
        $index = [];
        
        foreach ($incidencias as $inc) {
            // Crear clave única basada en: Provincia + Via + PKInicio + PKFin + Tipo + Causa
            $key = $this->generateIncidenciaKey($inc);
            $index[$key] = $inc;
        }
        
        return $index;
    }

    /**
     * Generar clave única para identificar una incidencia
     */
    private function generateIncidenciaKey($incidencia) {
        $parts = [
            $incidencia['Provincia'] ?? '',
            $incidencia['Via'] ?? '',
            $incidencia['PKInicio'] ?? '',
            $incidencia['PKFin'] ?? '',
            $incidencia['Tipo'] ?? '',
            $incidencia['Causa'] ?? ''
        ];
        
        return md5(implode('|', $parts));
    }

    /**
     * Crear incidencia desde datos de JCyL con estado específico
     */
    private function createIncidenciaFromJCyL($incJCyL, $estado = 'activa') {
        try {
            // Obtener coordenadas (con timeout para no bloquear)
            $coords = $this->getCoordinatesWithTimeout($incJCyL['Via'], $incJCyL['Provincia']);
            
            // Mapear tipo
            $tipo = $this->mapTypeFromJCyL($incJCyL['Tipo'], $incJCyL['Causa']);
            
            // Construir descripción
            $descripcion = $this->buildDescriptionFromJCyL($incJCyL);
            
            // Crear incidencia
            $this->incidencia->tipo = $tipo;
            $this->incidencia->descripcion = $descripcion;
            $this->incidencia->provincia = $incJCyL['Provincia'];
            $this->incidencia->carretera = $incJCyL['Via'];
            $this->incidencia->pk = $incJCyL['PKInicio'] ?? null;
            $this->incidencia->latitud = $coords['lat'];
            $this->incidencia->longitud = $coords['lng'];
            $this->incidencia->estado = $estado;
            $this->incidencia->fuente = 'jcyl_auto'; // Marcar como importación automática
            
            return $this->incidencia->create();
            
        } catch (Exception $e) {
            error_log("Error creando incidencia desde JCyL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estado de incidencia existente
     */
    private function updateIncidenciaState($incJCyL, $nuevoEstado) {
        try {
            // Buscar incidencia existente por campos únicos
            $sql = "UPDATE incidencias SET 
                        estado = ?, 
                        fecha_actualizacion = CURRENT_TIMESTAMP 
                    WHERE provincia = ? 
                        AND carretera = ? 
                        AND pk = ? 
                        AND fuente LIKE 'jcyl%'
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $nuevoEstado,
                $incJCyL['Provincia'],
                $incJCyL['Via'],
                $incJCyL['PKInicio'] ?? null
            ]);
            
            return $result && $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error actualizando estado de incidencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener coordenadas con timeout para no bloquear el proceso
     */
    private function getCoordinatesWithTimeout($via, $provincia, $timeoutSeconds = 2) {
        try {
            // Primero buscar en cache local
            $sql = "SELECT latitud, longitud FROM carreteras_geocache 
                    WHERE via = ? AND provincia = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$via, $provincia]);
            $cached = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cached) {
                return [
                    'lat' => (float)$cached['latitud'],
                    'lng' => (float)$cached['longitud']
                ];
            }
            
            // Si no está en cache, usar geocodificación con timeout
            $query = urlencode("$via, $provincia, Spain");
            $url = "https://nominatim.openstreetmap.org/search?q=$query&format=json&limit=1";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_HTTPHEADER => ['User-Agent: OpenRoadCyL-Importer']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response && $httpCode === 200) {
                $results = json_decode($response, true);
                if (is_array($results) && count($results) > 0) {
                    $lat = (float)$results[0]['lat'];
                    $lng = (float)$results[0]['lon'];
                    
                    // Guardar en cache
                    try {
                        $insertSql = "INSERT IGNORE INTO carreteras_geocache (via, provincia, latitud, longitud) 
                                      VALUES (?, ?, ?, ?)";
                        $insertStmt = $this->pdo->prepare($insertSql);
                        $insertStmt->execute([$via, $provincia, $lat, $lng]);
                    } catch (Exception $e) {
                        // No es crítico si falla el cache
                    }
                    
                    return ['lat' => $lat, 'lng' => $lng];
                }
            }
            
        } catch (Exception $e) {
            error_log("Error en geocodificación: " . $e->getMessage());
        }
        
        // Fallback: coordenadas del centro de la provincia
        return $this->getCoordsForProvincia($provincia);
    }

    /**
     * Mapear tipo de JCyL a nuestros tipos
     */
    private function mapTypeFromJCyL($tipo, $causa) {
        $tipoLower = strtolower($tipo ?? '');
        $causaLower = strtolower($causa ?? '');
        
        // Mapeo por causa primero (más específico)
        if (strpos($causaLower, 'obra') !== false) return 'Obras';
        if (strpos($causaLower, 'nieve') !== false || strpos($causaLower, 'hielo') !== false) return 'Meteorológica';
        if (strpos($causaLower, 'desprendimiento') !== false) return 'Meteorológica';
        if (strpos($causaLower, 'accidente') !== false) return 'Accidente';
        
        // Mapeo por tipo después
        if (strpos($tipoLower, 'obra') !== false) return 'Obras';
        if (strpos($tipoLower, 'cortada') !== false || strpos($tipoLower, 'cerrada') !== false) return 'Retención';
        if (strpos($tipoLower, 'cadena') !== false) return 'Meteorológica';
        
        return 'Retención'; // Default
    }

    /**
     * Construir descripción desde datos de JCyL
     */
    private function buildDescriptionFromJCyL($inc) {
        $parts = [];
        
        if (!empty($inc['Tramo'])) $parts[] = $inc['Tramo'];
        if (!empty($inc['Causa'])) $parts[] = "Causa: " . $inc['Causa'];
        if (!empty($inc['Observaciones']) && $inc['Observaciones'] !== '--') {
            $parts[] = $inc['Observaciones'];
        }
        
        return substr(implode('. ', $parts), 0, 500);
    }

    /**
     * Obtener coordenadas por provincia (fallback)
     */
    private function getCoordsForProvincia($provincia) {
        $coords = [
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
        
        return $coords[$provincia] ?? $coords['Valladolid'];
    }

    /**
     * Procesar lista de incidencias (método auxiliar)
     */
    private function processIncidencias($incidencias, $estado = 'activa') {
        $imported = 0;
        $skipped = 0;

        foreach ($incidencias as $inc) {
            try {
                $created = $this->createIncidenciaFromJCyL($inc, $estado);
                if ($created) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (Exception $e) {
                error_log("Error procesando incidencia: " . $e->getMessage());
                $skipped++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'message' => "Procesadas: $imported importadas, $skipped omitidas"
        ];
    }
}
?>