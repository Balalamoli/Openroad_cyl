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

// Test 2: Descargar datos
echo PHP_EOL . "Descargando datos..." . PHP_EOL;

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
    exit(1);
}

echo "✅ Datos descargados: " . strlen($data) . " bytes" . PHP_EOL;

// Test 3: Verificar JSON
$json = json_decode($data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON inválido: " . json_last_error_msg() . PHP_EOL;
    exit(1);
}

// Test 4: Verificar estructura
if (isset($json['incidencias'])) {
    echo "✅ Estructura correcta con " . count($json['incidencias']) . " incidencias" . PHP_EOL;
    echo "Título: " . ($json['titulo'] ?? 'N/A') . PHP_EOL;
    echo "Fecha: " . ($json['fecha'] ?? 'N/A') . PHP_EOL;
} else {
    echo "❌ Estructura incorrecta" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== Test completado exitosamente ===" . PHP_EOL;