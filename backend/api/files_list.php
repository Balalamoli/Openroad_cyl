<?php
/**
 * OpenRoadCyL - API para listar archivos JSON disponibles
 * Endpoint simple para obtener archivos disponibles para comparación
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $dataDir = dirname(__FILE__) . '/../data/';
    $files = [];
    
    if (is_dir($dataDir)) {
        $scanFiles = scandir($dataDir);
        foreach ($scanFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $filePath = $dataDir . $file;
                $files[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'readable' => is_readable($filePath)
                ];
            }
        }
    }
    
    // Ordenar por fecha de modificación (más reciente primero)
    usort($files, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    echo json_encode([
        'success' => true,
        'available_files' => $files,
        'total_files' => count($files),
        'data_directory' => $dataDir
    ]);
    
} catch (Exception $e) {
    error_log("Error en API files_list: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'available_files' => [],
        'total_files' => 0
    ]);
}
?>