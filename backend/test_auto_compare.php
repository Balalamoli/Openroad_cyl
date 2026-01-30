<?php
/**
 * Script de prueba para la comparación automática
 * Simula lo que hace el botón "Actualizar Estados"
 */

echo "=== TEST COMPARACIÓN AUTOMÁTICA ===\n\n";

// Simular la llamada a files_list.php
echo "1. Obteniendo lista de archivos...\n";
$dataDir = __DIR__ . '/data/';
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

echo "📁 Archivos encontrados:\n";
foreach ($files as $index => $file) {
    echo "  " . ($index + 1) . ". {$file['name']} - {$file['modified']}\n";
}

if (count($files) < 2) {
    echo "\n❌ ERROR: Se necesitan al menos 2 archivos JSON\n";
    exit(1);
}

$archivoNuevo = $files[0]['name'];      // Más reciente
$archivoAnterior = $files[1]['name'];   // Segundo más reciente

echo "\n2. Selección automática:\n";
echo "📁 Archivo ANTERIOR: $archivoAnterior\n";
echo "📁 Archivo NUEVO: $archivoNuevo\n\n";

// Simular la comparación
echo "3. Ejecutando comparación...\n";

require_once __DIR__ . '/controllers/ImporterJCyL.php';

try {
    $importer = new ImporterJCyL();
    $result = $importer->compareAndImport(
        $dataDir . $archivoAnterior,
        $dataDir . $archivoNuevo
    );
    
    if ($result['success']) {
        echo "✅ COMPARACIÓN EXITOSA\n\n";
        echo "📊 RESULTADOS (como los vería el usuario):\n";
        echo "🟢 Nuevas (Activas): {$result['imported']}\n";
        echo "🟡 En Proceso: {$result['updated']}\n";
        echo "🔴 Resueltas: {$result['resolved']}\n";
        echo "📈 Total: {$result['total_processed']}\n\n";
        echo "💬 {$result['message']}\n";
    } else {
        echo "❌ ERROR: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completado\n";
?>