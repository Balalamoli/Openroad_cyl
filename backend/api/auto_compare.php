<?php
/**
 * OpenRoadCyL - API para ejecutar comparación automática
 * Ejecuta la comparación automática como lo haría el comando PHP
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../controllers/ImporterJCyL.php';

try {
    $dataDir = dirname(__FILE__) . '/../data/';
    
    // Buscar archivos JSON
    $files = [];
    if (is_dir($dataDir)) {
        $scanFiles = scandir($dataDir);
        foreach ($scanFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $filePath = $dataDir . $file;
                $files[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'modified' => filemtime($filePath),
                    'size' => filesize($filePath)
                ];
            }
        }
    }
    
    if (count($files) < 2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se necesitan al menos 2 archivos JSON para comparar',
            'files_found' => count($files)
        ]);
        exit();
    }
    
    // Ordenar por fecha de modificación (más reciente primero)
    usort($files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    $archivoNuevo = $files[0]['path'];      // Más reciente
    $archivoAnterior = $files[1]['path'];   // Segundo más reciente
    
    $nombreNuevo = $files[0]['name'];
    $nombreAnterior = $files[1]['name'];
    
    // Ejecutar comparación
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport($archivoAnterior, $archivoNuevo);
    
    if ($result['success']) {
        // Agregar información adicional para el frontend
        $result['files_compared'] = [
            'anterior' => $nombreAnterior,
            'nuevo' => $nombreNuevo,
            'anterior_date' => date('Y-m-d H:i:s', $files[1]['modified']),
            'nuevo_date' => date('Y-m-d H:i:s', $files[0]['modified'])
        ];
        
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Error en auto_compare API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage(),
        'imported' => 0,
        'updated' => 0,
        'resolved' => 0
    ]);
}
?>