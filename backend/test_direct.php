<?php
/**
 * Test directo de la funcionalidad de comparación
 * Sin depender de HTTP, ejecuta directamente la lógica
 */

echo "=== TEST DIRECTO DE COMPARACIÓN ===\n\n";

// Simular variables de entorno HTTP para la API
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capturar la salida de la API
ob_start();

try {
    // Incluir y ejecutar la API directamente
    include __DIR__ . '/api/auto_compare.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    ob_end_clean();
}

echo "📄 Salida de la API:\n";
echo $output . "\n\n";

// Intentar decodificar como JSON
$data = json_decode($output, true);
if ($data) {
    echo "📊 RESULTADO PARSEADO:\n";
    if ($data['success']) {
        echo "✅ Éxito: {$data['message']}\n";
        echo "🟢 Nuevas: {$data['imported']}\n";
        echo "🟡 En Proceso: {$data['updated']}\n";
        echo "🔴 Resueltas: {$data['resolved']}\n";
        echo "📊 Total: {$data['total_processed']}\n";
        
        if (isset($data['files_compared'])) {
            echo "\n📁 Archivos:\n";
            echo "  Anterior: {$data['files_compared']['anterior']}\n";
            echo "  Nuevo: {$data['files_compared']['nuevo']}\n";
        }
    } else {
        echo "❌ Error: {$data['error']}\n";
    }
} else {
    echo "❌ La salida no es JSON válido\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?>