<?php
/**
 * Script de prueba para verificar la conectividad con la API
 */

echo "=== Test de Conectividad API ===" . PHP_EOL;

$apiUrl = 'https://datosabiertos.jcyl.es/web/jcyl/risp/es/transporte/incidencias_carreteras/1284212099243.json';

echo "URL: " . $apiUrl . PHP_EOL;
echo "Probando conectividad..." . PHP_EOL;

// Test 1: Verificar que la URL responde
$context = stream_context_create([
    'http' => [
        'method' => 'HEAD',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$headers = @get_headers($apiUrl, 1, $context);
if ($headers) {
    echo "✅ URL accesible: " . $headers[0] . PHP_EOL;
} else {
    echo "❌ URL no accesible" . PHP_EOL;
    exit(1);
}

// Test 2: Descargar una muestra pequeña
echo PHP_EOL . "Descargando muestra de datos..." . PHP_EOL;

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: OpenRoadCyL/1.0',
            'Accept: application/json'
        ],
        'timeout' => 30,
        'ignore_errors' => true
    ]
]);

$data = @file_get_contents($apiUrl, false, $context);

if ($data === false) {
    echo "❌ Error descargando datos" . PHP_EOL;
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Desconocido') . PHP_EOL;
    exit(1);
}

echo "✅ Datos descargados: " . strlen($data) . " bytes" . PHP_EOL;

// Test 3: Verificar que es JSON válido
$json = json_decode($data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON inválido: " . json_last_error_msg() . PHP_EOL;
    echo "Primeros 200 caracteres:" . PHP_EOL;
    echo substr($data, 0, 200) . PHP_EOL;
    exit(1);
}

echo "✅ JSON válido con " . count($json) . " registros" . PHP_EOL;

// Test 4: Verificar estructura de datos
if (isset($json[0]['fields'])) {
    echo "✅ Estructura de datos correcta" . PHP_EOL;
    echo "Campos disponibles en primer registro:" . PHP_EOL;
    foreach (array_keys($json[0]['fields']) as $field) {
        echo "  - $field" . PHP_EOL;
    }
} else {
    echo "⚠️  Estructura de datos diferente a la esperada" . PHP_EOL;
    echo "Estructura del primer registro:" . PHP_EOL;
    print_r(array_keys($json[0] ?? []));
}

echo PHP_EOL . "=== Test completado ===" . PHP_EOL;