<?php
/**
 * Script CLI para actualizar datos de incidencias
 * Uso: php update_data.php
 */

// Cambiar al directorio del script
chdir(__DIR__);

// Incluir el servicio de actualización
require_once '../services/DataUpdater.php';

echo "=== OpenRoadCyL - Actualizador de Datos ===" . PHP_EOL;
echo "Iniciando actualización automática..." . PHP_EOL;
echo "Timestamp: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "----------------------------------------" . PHP_EOL;

// Crear instancia del actualizador
$updater = new DataUpdater();

// Ejecutar actualización
$result = $updater->updateData();

// Mostrar resultado
if ($result['success']) {
    echo "✅ ÉXITO: " . $result['message'] . PHP_EOL;
    echo "📊 Registros procesados: " . $result['records'] . PHP_EOL;
} else {
    echo "❌ ERROR: " . $result['error'] . PHP_EOL;
    exit(1); // Código de error para cron
}

echo "----------------------------------------" . PHP_EOL;
echo "Actualización completada: " . $result['timestamp'] . PHP_EOL;
echo "=== Fin del proceso ===" . PHP_EOL;