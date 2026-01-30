<?php
/**
 * Test de la API auto_compare.php
 * Simula exactamente lo que hace el botón web
 */

echo "=== TEST API AUTO COMPARE ===\n\n";

// Simular llamada HTTP POST a la API
$url = 'http://localhost/backend/api/auto_compare.php';

// Usar cURL para simular la llamada del frontend
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: Test-Script'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "🌐 Llamando a: $url\n";
echo "📡 Método: POST\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error cURL: $error\n";
    exit(1);
}

echo "📊 Código HTTP: $httpCode\n";
echo "📄 Respuesta:\n";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($data['success']) {
            echo "\n\n✅ API FUNCIONA CORRECTAMENTE\n";
            echo "🟢 Nuevas: {$data['imported']}\n";
            echo "🟡 En Proceso: {$data['updated']}\n";
            echo "🔴 Resueltas: {$data['resolved']}\n";
            echo "📊 Total: {$data['total_processed']}\n";
            
            if (isset($data['files_compared'])) {
                echo "\n📁 Archivos comparados:\n";
                echo "  Anterior: {$data['files_compared']['anterior']}\n";
                echo "  Nuevo: {$data['files_compared']['nuevo']}\n";
            }
        } else {
            echo "\n\n❌ API REPORTA ERROR\n";
            echo "Error: {$data['error']}\n";
        }
    } else {
        echo "❌ Respuesta no es JSON válido:\n";
        echo $response . "\n";
    }
} else {
    echo "❌ Sin respuesta del servidor\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "ALTERNATIVA: Ejecutar directamente el script PHP\n";
echo "cd backend && php auto_comparar.php\n";
?>