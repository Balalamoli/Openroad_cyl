<?php
/**
 * API Endpoint para actualizar datos de incidencias
 * Permite activar la actualización manualmente desde la interfaz web
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Función simple de log de seguridad (sin dependencias externas)
function logSecurityEvent($event, $data) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $event: " . json_encode($data) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    // Incluir el servicio de actualización
    require_once __DIR__ . '/../services/DataUpdater.php';
    
    $updater = new DataUpdater();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ejecutar actualización
        $result = $updater->updateData();
        
        // Log de seguridad
        logSecurityEvent('data_update_requested', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'success' => $result['success']
        ]);
        
        echo json_encode($result);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener estado de la última actualización
        $action = $_GET['action'] ?? 'status';
        
        switch ($action) {
            case 'status':
                $status = $updater->getLastUpdateStatus();
                echo json_encode([
                    'success' => true,
                    'status' => $status
                ]);
                break;
                
            case 'logs':
                $lines = intval($_GET['lines'] ?? 20);
                $logs = $updater->getRecentLogs($lines);
                echo json_encode([
                    'success' => true,
                    'logs' => $logs
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'error' => 'Acción no válida'
                ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
    }
    
} catch (Exception $e) {
    // Log del error
    logSecurityEvent('data_update_error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    // Capturar errores fatales de PHP
    logSecurityEvent('data_update_fatal_error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal del servidor: ' . $e->getMessage()
    ]);
}