<?php
/**
 * OpenRoadCyL - API de Comparación de JSONs JCyL
 * Endpoint para comparar dos archivos JSON y determinar estados automáticamente
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

require_once '../controllers/ImporterJCyL.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Datos JSON requeridos'
            ]);
            exit();
        }
        
        $action = $input['action'] ?? 'compare';
        
        switch ($action) {
            case 'compare':
                handleCompareAction($input);
                break;
                
            case 'upload_and_compare':
                handleUploadAndCompare($input);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Acción no válida'
                ]);
                break;
        }
        
    } elseif ($method === 'GET') {
        // Obtener información sobre archivos disponibles
        handleGetInfo();
        
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en API compare_jcyl: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

/**
 * Manejar comparación de archivos existentes
 */
function handleCompareAction($input) {
    $oldFile = $input['old_file'] ?? null;
    $newFile = $input['new_file'] ?? null;
    
    if (!$oldFile || !$newFile) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requieren los nombres de ambos archivos (old_file y new_file)'
        ]);
        return;
    }
    
    // Construir rutas completas
    $dataDir = dirname(__FILE__) . '/../data/';
    $oldPath = $dataDir . $oldFile;
    $newPath = $dataDir . $newFile;
    
    // Validar que los archivos existen
    if (!file_exists($oldPath)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Archivo anterior no encontrado: $oldFile"
        ]);
        return;
    }
    
    if (!file_exists($newPath)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Archivo nuevo no encontrado: $newFile"
        ]);
        return;
    }
    
    // Ejecutar comparación
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport($oldPath, $newPath);
    
    echo json_encode($result);
}

/**
 * Manejar subida de archivos y comparación
 */
function handleUploadAndCompare($input) {
    // Esta función permitiría subir archivos JSON directamente
    // Por ahora, redirigir a usar archivos existentes
    
    $oldData = $input['old_json_data'] ?? null;
    $newData = $input['new_json_data'] ?? null;
    
    if (!$oldData || !$newData) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requieren los datos de ambos JSONs (old_json_data y new_json_data)'
        ]);
        return;
    }
    
    // Crear archivos temporales
    $tempDir = sys_get_temp_dir();
    $oldTempFile = $tempDir . '/jcyl_old_' . uniqid() . '.json';
    $newTempFile = $tempDir . '/jcyl_new_' . uniqid() . '.json';
    
    try {
        // Escribir archivos temporales
        file_put_contents($oldTempFile, json_encode($oldData));
        file_put_contents($newTempFile, json_encode($newData));
        
        // Ejecutar comparación
        $importer = new ImporterJCyL();
        $result = $importer->compareAndImport($oldTempFile, $newTempFile);
        
        // Limpiar archivos temporales
        unlink($oldTempFile);
        unlink($newTempFile);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        // Limpiar archivos temporales en caso de error
        if (file_exists($oldTempFile)) unlink($oldTempFile);
        if (file_exists($newTempFile)) unlink($newTempFile);
        
        throw $e;
    }
}

/**
 * Obtener información sobre archivos disponibles
 */
function handleGetInfo() {
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
    
    echo json_encode([
        'success' => true,
        'data_directory' => $dataDir,
        'available_files' => $files,
        'total_files' => count($files)
    ]);
}
?>