<?php
/**
 * API que ejecuta la comparación automática sin interacción del usuario
 * Funciona como puente entre el botón web y la lógica de comparación
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../controllers/ImporterJCyL.php';
    
    $dataDir = __DIR__ . '/../data/';
    
    // Buscar archivos JSON
    $files = glob($dataDir . '*.json');
    if (count($files) < 2) {
        throw new Exception("Se necesitan al menos 2 archivos JSON para comparar");
    }
    
    // Ordenar por fecha de modificación (más reciente primero)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Seleccionar automáticamente los dos más recientes
    $archivoNuevo = $files[0];  // Más reciente
    $archivoAnterior = $files[1]; // Segundo más reciente
    
    // Ejecutar comparación
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport($archivoAnterior, $archivoNuevo);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'imported' => $result['imported'],
            'updated' => $result['updated'], 
            'resolved' => $result['resolved'],
            'total_processed' => $result['total_processed'],
            'files_compared' => [
                'anterior' => basename($archivoAnterior),
                'nuevo' => basename($archivoNuevo)
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'imported' => 0,
            'updated' => 0,
            'resolved' => 0
        ]);
    }
    
} catch (Exception $e) {
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