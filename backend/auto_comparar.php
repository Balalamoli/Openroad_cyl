<?php
/**
 * Script de automatización para comparar archivos específicos
 * Detecta automáticamente el archivo más reciente como "nuevo"
 */

require_once __DIR__ . '/controllers/ImporterJCyL.php';

echo "=== COMPARACIÓN AUTOMÁTICA DE JSONS ===\n\n";

$dataDir = __DIR__ . '/data/';

// Buscar archivos JSON
$files = glob($dataDir . '*.json');
if (count($files) < 2) {
    echo "❌ ERROR: Se necesitan al menos 2 archivos JSON para comparar\n";
    exit(1);
}

// Ordenar por fecha de modificación (más reciente primero)
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

echo "📁 Archivos JSON encontrados (ordenados por fecha):\n";
foreach ($files as $index => $file) {
    $filename = basename($file);
    $date = date('Y-m-d H:i:s', filemtime($file));
    $size = formatBytes(filesize($file));
    echo "  " . ($index + 1) . ". $filename - $date ($size)\n";
}

// Seleccionar automáticamente los dos más recientes
$archivoNuevo = $files[0];  // Más reciente
$archivoAnterior = $files[1]; // Segundo más reciente

echo "\n🔄 COMPARACIÓN AUTOMÁTICA:\n";
echo "📁 Archivo ANTERIOR: " . basename($archivoAnterior) . "\n";
echo "📁 Archivo NUEVO: " . basename($archivoNuevo) . "\n\n";

// Confirmar antes de proceder
echo "¿Continuar con la comparación? (y/N): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "❌ Operación cancelada\n";
    exit(0);
}

echo "\n🚀 Ejecutando comparación...\n\n";

try {
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport($archivoAnterior, $archivoNuevo);
    
    if ($result['success']) {
        echo "✅ COMPARACIÓN COMPLETADA\n\n";
        echo "📊 ESTADÍSTICAS:\n";
        echo "🟢 Incidencias NUEVAS (Activas): " . $result['imported'] . "\n";
        echo "🟡 Incidencias CONTINUAS (En Proceso): " . $result['updated'] . "\n";
        echo "🔴 Incidencias RESUELTAS: " . $result['resolved'] . "\n";
        echo "📈 TOTAL PROCESADAS: " . $result['total_processed'] . "\n\n";
        
        // Mostrar porcentajes
        $total = $result['total_processed'];
        if ($total > 0) {
            echo "📊 DISTRIBUCIÓN:\n";
            echo sprintf("🟢 Nuevas: %.1f%%\n", ($result['imported'] / $total) * 100);
            echo sprintf("🟡 Continuas: %.1f%%\n", ($result['updated'] / $total) * 100);
            echo sprintf("🔴 Resueltas: %.1f%%\n", ($result['resolved'] / $total) * 100);
        }
        
        echo "\n💬 " . $result['message'] . "\n";
        
        // Sugerir siguiente paso
        echo "\n🔄 SIGUIENTE PASO:\n";
        echo "Para ver los cambios en la aplicación web, actualiza la página o haz clic en 'Actualizar'\n";
        
    } else {
        echo "❌ ERROR: " . $result['error'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    exit(1);
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>