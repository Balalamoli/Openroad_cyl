<?php
/**
 * Script para ejecutar comparación de JSONs desde línea de comandos
 * Uso: php ejecutar_comparacion.php archivo_anterior.json archivo_nuevo.json
 */

require_once __DIR__ . '/controllers/ImporterJCyL.php';

echo "=== SISTEMA DE COMPARACIÓN DE JSONS JCYL ===\n\n";

// Verificar argumentos
if ($argc < 3) {
    echo "❌ ERROR: Se requieren 2 argumentos\n";
    echo "Uso: php ejecutar_comparacion.php archivo_anterior.json archivo_nuevo.json\n\n";
    echo "Ejemplos:\n";
    echo "  php ejecutar_comparacion.php incidencias_jcyl_anterior.json incidencias_jcyl.json\n";
    echo "  php ejecutar_comparacion.php \"incidencias (1).json\" \"incidencias (2).json\"\n\n";
    
    // Mostrar archivos disponibles
    $dataDir = __DIR__ . '/data/';
    $files = glob($dataDir . '*.json');
    if ($files) {
        echo "Archivos JSON disponibles:\n";
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $date = date('Y-m-d H:i:s', filemtime($file));
            echo "  - $filename (" . formatBytes($size) . ") - $date\n";
        }
    }
    exit(1);
}

$archivoAnterior = $argv[1];
$archivoNuevo = $argv[2];

// Construir rutas completas
$dataDir = __DIR__ . '/data/';
$oldPath = $dataDir . $archivoAnterior;
$newPath = $dataDir . $archivoNuevo;

echo "Archivos a comparar:\n";
echo "📁 Anterior: $archivoAnterior\n";
echo "📁 Nuevo: $archivoNuevo\n\n";

// Verificar que los archivos existen
if (!file_exists($oldPath)) {
    echo "❌ ERROR: Archivo anterior no encontrado: $oldPath\n";
    exit(1);
}

if (!file_exists($newPath)) {
    echo "❌ ERROR: Archivo nuevo no encontrado: $newPath\n";
    exit(1);
}

echo "✅ Archivos encontrados\n";
echo "📊 Tamaño anterior: " . formatBytes(filesize($oldPath)) . "\n";
echo "📊 Tamaño nuevo: " . formatBytes(filesize($newPath)) . "\n\n";

// Ejecutar comparación
echo "🔄 Ejecutando comparación...\n\n";

try {
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport($oldPath, $newPath);
    
    if ($result['success']) {
        echo "✅ COMPARACIÓN EXITOSA\n\n";
        echo "📈 RESULTADOS:\n";
        echo "🟢 Nuevas (ACTIVAS): " . $result['imported'] . "\n";
        echo "🟡 Continuas (EN PROCESO): " . $result['updated'] . "\n";
        echo "🔴 Resueltas: " . $result['resolved'] . "\n";
        echo "📊 Total procesadas: " . $result['total_processed'] . "\n\n";
        echo "💬 " . $result['message'] . "\n";
    } else {
        echo "❌ ERROR EN COMPARACIÓN\n";
        echo "💬 " . $result['error'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Proceso completado exitosamente\n";

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>