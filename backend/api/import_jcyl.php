<?php
/**
 * OpenRoadCyL - API de Importación JCyL
 * Endpoint para importar datos abiertos de Junta de Castilla y León
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$result = null;

try {
    // Verificar rutas
    $importerPath = dirname(__FILE__) . '/../controllers/ImporterJCyL.php';
    $dataPath = dirname(__FILE__) . '/../data/incidencias_jcyl.json';
    
    if (!file_exists($importerPath)) {
        throw new Exception("ImporterJCyL.php no encontrado en: $importerPath");
    }
    
    if (!file_exists($dataPath)) {
        throw new Exception("incidencias_jcyl.json no encontrado en: $dataPath");
    }
    
    require_once $importerPath;

    $importer = new ImporterJCyL();
    
    // Importar desde archivo
    $result = $importer->importFromFile($dataPath);

    ob_end_clean();
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'imported' => 0,
        'skipped' => 0
    ]);
}
?>
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'imported' => 0,
        'skipped' => 0
    ]);
}
?>
