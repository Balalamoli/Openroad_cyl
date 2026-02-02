<?php
/**
 * Script para debuggear la estructura de datos de la API
 */

echo "=== Debug API Data Structure ===" . PHP_EOL;

$apiUrl = 'https://datosabiertos.jcyl.es/web/jcyl/risp/es/transporte/incidencias_carreteras/1284212099243.json';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: OpenRoadCyL/1.0',
            'Accept: application/json'
        ],
        'timeout' => 30
    ]
]);

$data = file_get_contents($apiUrl, false, $context);
$json = json_decode($data, true);

echo "Estructura completa del JSON:" . PHP_EOL;
print_r($json);

echo PHP_EOL . "=== Análisis de estructura ===" . PHP_EOL;
echo "Tipo de dato raíz: " . gettype($json) . PHP_EOL;

if (is_array($json)) {
    echo "Claves del nivel raíz: " . implode(', ', array_keys($json)) . PHP_EOL;
    
    // Buscar donde están los datos de incidencias
    foreach ($json as $key => $value) {
        echo PHP_EOL . "Clave '$key':" . PHP_EOL;
        echo "  Tipo: " . gettype($value) . PHP_EOL;
        if (is_array($value)) {
            echo "  Elementos: " . count($value) . PHP_EOL;
            if (count($value) > 0) {
                echo "  Primer elemento:" . PHP_EOL;
                print_r(array_slice($value, 0, 1));
            }
        }
    }
}