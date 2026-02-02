<?php
/**
 * DataUpdater - Servicio para actualizar datos de incidencias automáticamente
 * Descarga datos de la API de la Junta de Castilla y León
 */

class DataUpdater {
    private $dataDir;
    private $logFile;
    private $apiUrl;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../data/';
        $this->logFile = __DIR__ . '/../logs/data_updater.log';
        $this->apiUrl = 'https://datosabiertos.jcyl.es/web/jcyl/risp/es/transporte/incidencias_carreteras/1284212099243.json';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Actualiza los datos de incidencias
     */
    public function updateData() {
        $this->log("Iniciando actualización de datos...");
        
        try {
            // 1. Descargar nuevos datos
            $newData = $this->downloadData();
            if (!$newData) {
                throw new Exception("Error al descargar datos de la API");
            }
            
            // 2. Validar datos
            if (!$this->validateData($newData)) {
                throw new Exception("Los datos descargados no son válidos");
            }
            
            // 3. Rotar archivos (actual -> anterior)
            $this->rotateFiles();
            
            // 4. Guardar nuevos datos
            $this->saveNewData($newData);
            
            // 5. Ejecutar comparación automática
            $this->executeComparison();
            
            $this->log("Actualización completada exitosamente");
            return [
                'success' => true,
                'message' => 'Datos actualizados correctamente',
                'timestamp' => date('Y-m-d H:i:s'),
                'records' => count($newData)
            ];
            
        } catch (Exception $e) {
            $this->log("Error en actualización: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Descarga datos de la API
     */
    private function downloadData() {
        $this->log("Descargando datos de: " . $this->apiUrl);
        
        // Configurar contexto para la petición HTTP
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: OpenRoadCyL/1.0',
                    'Accept: application/json',
                    'Connection: close'
                ],
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ]);
        
        // Descargar datos
        $jsonData = @file_get_contents($this->apiUrl, false, $context);
        
        if ($jsonData === false) {
            $error = error_get_last();
            $this->log("Error: No se pudieron descargar los datos. " . ($error['message'] ?? 'Error desconocido'));
            return false;
        }
        
        // Verificar que no sea una página de error HTML
        if (strpos($jsonData, '<html>') !== false || strpos($jsonData, '<!DOCTYPE') !== false) {
            $this->log("Error: La respuesta parece ser HTML en lugar de JSON");
            return false;
        }
        
        // Decodificar JSON
        $response = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Error: JSON inválido - " . json_last_error_msg());
            $this->log("Primeros 500 caracteres de la respuesta: " . substr($jsonData, 0, 500));
            return false;
        }
        
        // Extraer los datos de incidencias del objeto respuesta
        if (!isset($response['incidencias']) || !is_array($response['incidencias'])) {
            $this->log("Error: No se encontró el array 'incidencias' en la respuesta");
            return false;
        }
        
        $data = $this->transformApiData($response['incidencias']);
        
        $this->log("Datos descargados correctamente: " . count($data) . " registros");
        $this->log("Título del dataset: " . ($response['titulo'] ?? 'N/A'));
        $this->log("Fecha del dataset: " . ($response['fecha'] ?? 'N/A'));
        
        return $data;
    }
    
    /**
     * Transforma los datos de la API al formato esperado por la aplicación
     */
    private function transformApiData($apiData) {
        $transformedData = [];
        
        foreach ($apiData as $incident) {
            // Convertir el formato de la API al formato esperado
            $transformedIncident = [
                'fields' => [
                    'provincia' => $incident['Provincia'] ?? '',
                    'via' => $incident['Via'] ?? '',
                    'pkinicio' => $incident['PKInicio'] ?? '',
                    'pkfin' => $incident['PKFin'] ?? '',
                    'tramo' => $incident['Tramo'] ?? '',
                    'tipo' => $incident['Tipo'] ?? '',
                    'causa' => $incident['Causa'] ?? '',
                    'calzada' => $incident['Calzada'] ?? '',
                    'rutaalt' => $incident['RutaAlt'] ?? '',
                    'observaciones' => $incident['Observaciones'] ?? '',
                    'fechaalta' => $incident['FechaAlta'] ?? '',
                    'masinfo' => $incident['MasInfo'] ?? '',
                    'numincidencia' => $incident['NumIncidencia'] ?? '',
                    'fechamodificacion' => $incident['FechaModificacion'] ?? ''
                ]
            ];
            
            $transformedData[] = $transformedIncident;
        }
        
        return $transformedData;
    }
    
    /**
     * Valida que los datos descargados sean correctos
     */
    private function validateData($data) {
        if (!is_array($data) || empty($data)) {
            $this->log("Error: Datos vacíos o no válidos");
            return false;
        }
        
        // Verificar que al menos el primer registro tenga los campos esperados
        $firstRecord = $data[0];
        $requiredFields = ['fields'];
        
        foreach ($requiredFields as $field) {
            if (!isset($firstRecord[$field])) {
                $this->log("Error: Campo requerido '$field' no encontrado");
                return false;
            }
        }
        
        $this->log("Validación de datos exitosa");
        return true;
    }
    
    /**
     * Rota los archivos: actual -> anterior
     */
    private function rotateFiles() {
        $currentFile = $this->dataDir . 'incidencias_jcyl.json';
        $previousFile = $this->dataDir . 'incidencias_jcyl_anterior.json';
        
        // Si existe el archivo actual, moverlo a anterior
        if (file_exists($currentFile)) {
            if (file_exists($previousFile)) {
                unlink($previousFile); // Eliminar el anterior previo
            }
            rename($currentFile, $previousFile);
            $this->log("Archivo rotado: actual -> anterior");
        }
    }
    
    /**
     * Guarda los nuevos datos
     */
    private function saveNewData($data) {
        $currentFile = $this->dataDir . 'incidencias_jcyl.json';
        
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($currentFile, $jsonData) === false) {
            throw new Exception("Error al guardar el archivo de datos");
        }
        
        $this->log("Nuevos datos guardados en: " . $currentFile);
    }
    
    /**
     * Ejecuta la comparación automática después de actualizar
     */
    private function executeComparison() {
        $this->log("Ejecutando comparación automática...");
        
        try {
            // Usar ImporterJCyL directamente en lugar del script de consola
            require_once __DIR__ . '/../controllers/ImporterJCyL.php';
            
            $dataDir = __DIR__ . '/../data/';
            $files = glob($dataDir . '*.json');
            
            if (count($files) < 2) {
                $this->log("Advertencia: Se necesitan al menos 2 archivos JSON para comparar");
                return;
            }
            
            // Ordenar por fecha (más reciente primero)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $archivoNuevo = $files[0];
            $archivoAnterior = $files[1];
            
            $this->log("Comparando: " . basename($archivoAnterior) . " vs " . basename($archivoNuevo));
            
            $importer = new ImporterJCyL();
            $result = $importer->compareAndImport($archivoAnterior, $archivoNuevo);
            
            if ($result['success']) {
                $this->log("Comparación exitosa: {$result['imported']} nuevas, {$result['updated']} en proceso, {$result['resolved']} resueltas");
            } else {
                $this->log("Error en comparación: " . $result['error']);
            }
            
            $this->log("Comparación automática completada");
        } catch (Exception $e) {
            $this->log("Error en comparación automática: " . $e->getMessage());
            // No lanzar excepción para no interrumpir el proceso principal
        }
    }
    
    /**
     * Registra eventos en el log
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // También mostrar en consola si se ejecuta desde CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
    
    /**
     * Obtiene el estado de la última actualización
     */
    public function getLastUpdateStatus() {
        $currentFile = $this->dataDir . 'incidencias_jcyl.json';
        $previousFile = $this->dataDir . 'incidencias_jcyl_anterior.json';
        
        $status = [
            'current_file_exists' => file_exists($currentFile),
            'previous_file_exists' => file_exists($previousFile),
            'current_file_size' => file_exists($currentFile) ? filesize($currentFile) : 0,
            'current_file_modified' => file_exists($currentFile) ? date('Y-m-d H:i:s', filemtime($currentFile)) : null,
            'log_exists' => file_exists($this->logFile)
        ];
        
        return $status;
    }
    
    /**
     * Lee las últimas líneas del log
     */
    public function getRecentLogs($lines = 20) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logContent = file($this->logFile, FILE_IGNORE_NEW_LINES);
        return array_slice($logContent, -$lines);
    }
}