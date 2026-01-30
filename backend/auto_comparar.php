<?php
/**
 * Script de automatización para comparar archivos específicos
 */

require_once __DIR__ . '/controllers/ImporterJCyL.php';

echo "=== COMPARACIÓN AUTOMÁTICA DE JSONS ===\n\n";

$dataDir = __DIR__ . '/data/';

$files = glob($dataDir . '*.json');
if (count($files) < 2) {
    echo "ERROR: Se necesitan al menos 2 archivos JSON para comparar\n";
    exit(1);
}

usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

echo "Archivos JSON encontrados (ordenados por fecha):\n";
foreach ($files as $index => $file) {
    $filename = basename($file);
    $date = date('Y-m-d H:i:s', filemtime($file));
    $size = formatBytes(filesize($file));
    echo "  " . ($index + 1) . ". $filename - $date ($size)\n";
}

$archivoNuevo = $files[0];
$archivoAnterior = $files[1];

echo "\nCOMPARACIÓN AUTOMÁTICA:\n";
echo "Archivo ANTERIOR: " . basename($archivoAnterior) . "\n";
echo "Archivo NUEVO: " . basename($archivoNuevo) . "\n\n";

echo "¿Continuar con la comparación? (y/N): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Operación cancelada\n";
    exit(0);
}

echo "\nEjecutando comparación...\n\n";

try {
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport($archivoAnterior, $archivoNuevo);
    
    if ($result['success']) {
        echo "COMPARACIÓN COMPLETADA\n\n";
        echo "ESTADÍSTICAS:\n";
        echo "Incidencias NUEVAS (Activas): " . $result['imported'] . "\n";
        echo "Incidencias CONTINUAS (En Proceso): " . $result['updated'] . "\n";
        echo "Incidencias RESUELTAS: " . $result['resolved'] . "\n";
        echo "TOTAL PROCESADAS: " . $result['total_processed'] . "\n\n";
        
        $total = $result['total_processed'];
        if ($total > 0) {
            echo "DISTRIBUCIÓN:\n";
            echo sprintf("Nuevas: %.1f%%\n", ($result['imported'] / $total) * 100);
            echo sprintf("Continuas: %.1f%%\n", ($result['updated'] / $total) * 100);
            echo sprintf("Resueltas: %.1f%%\n", ($result['resolved'] / $total) * 100);
        }
        
        echo "\n" . $result['message'] . "\n";
        echo "\nSIGUIENTE PASO:\n";
        echo "Para ver los cambios en la aplicación web, actualiza la página o haz clic en 'Actualizar'\n";
        
    } else {
        echo "ERROR: " . $result['error'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "EXCEPCIÓN: " . $e->getMessage() . "\n";
    exit(1);
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>