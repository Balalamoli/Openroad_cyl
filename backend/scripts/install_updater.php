<?php
/**
 * Script de instalación para el sistema de actualización automática
 * Configura permisos, directorios y realiza la primera actualización
 */

echo "=== OpenRoadCyL - Instalador del Sistema de Actualización ===" . PHP_EOL;
echo "Configurando sistema de actualización automática..." . PHP_EOL;
echo "============================================================" . PHP_EOL;

// Cambiar al directorio del script
chdir(__DIR__);

// Directorios necesarios
$directories = [
    '../logs',
    '../data'
];

// Crear directorios si no existen
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Directorio creado: $dir" . PHP_EOL;
        } else {
            echo "❌ Error creando directorio: $dir" . PHP_EOL;
        }
    } else {
        echo "📁 Directorio ya existe: $dir" . PHP_EOL;
    }
}

// Verificar permisos de escritura
$writableCheck = [
    '../logs' => 'Logs del sistema',
    '../data' => 'Archivos de datos JSON'
];

echo PHP_EOL . "Verificando permisos de escritura..." . PHP_EOL;
foreach ($writableCheck as $path => $description) {
    if (is_writable($path)) {
        echo "✅ $description: $path" . PHP_EOL;
    } else {
        echo "❌ Sin permisos de escritura: $path ($description)" . PHP_EOL;
        echo "   Ejecutar: chmod 755 $path" . PHP_EOL;
    }
}

// Verificar conectividad con la API
echo PHP_EOL . "Verificando conectividad con la API..." . PHP_EOL;
$apiUrl = 'https://analisis.datosabiertos.jcyl.es/api/explore/v2.1/catalog/datasets/incidencias-en-la-red-de-carreteras-titularidad-de-la-junta-de-castilla-y-leon/exports/json?lang=es&timezone=Europe%2FMadrid';

$context = stream_context_create([
    'http' => [
        'method' => 'HEAD',
        'timeout' => 10
    ]
]);

$headers = @get_headers($apiUrl, 1, $context);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "✅ API accesible: Junta de Castilla y León" . PHP_EOL;
} else {
    echo "❌ No se puede acceder a la API" . PHP_EOL;
    echo "   URL: $apiUrl" . PHP_EOL;
}

// Ejecutar primera actualización
echo PHP_EOL . "Ejecutando primera actualización de datos..." . PHP_EOL;
echo "----------------------------------------------------" . PHP_EOL;

require_once '../services/DataUpdater.php';

$updater = new DataUpdater();
$result = $updater->updateData();

if ($result['success']) {
    echo "✅ Primera actualización exitosa!" . PHP_EOL;
    echo "📊 Registros descargados: " . $result['records'] . PHP_EOL;
} else {
    echo "❌ Error en primera actualización: " . $result['error'] . PHP_EOL;
}

// Mostrar información de configuración de cron
echo PHP_EOL . "============================================================" . PHP_EOL;
echo "🔧 CONFIGURACIÓN DE CRON JOB" . PHP_EOL;
echo "============================================================" . PHP_EOL;
echo "Para automatizar las actualizaciones, configura un cron job:" . PHP_EOL;
echo PHP_EOL;

$scriptPath = realpath(__DIR__ . '/update_data.php');
$logPath = realpath(__DIR__ . '/../logs') . '/cron.log';

echo "Comando para cron (cada 15 minutos):" . PHP_EOL;
echo "*/15 * * * * /usr/bin/php $scriptPath >> $logPath 2>&1" . PHP_EOL;
echo PHP_EOL;

echo "Para configurar:" . PHP_EOL;
echo "1. Ejecutar: crontab -e" . PHP_EOL;
echo "2. Agregar la línea de arriba" . PHP_EOL;
echo "3. Guardar y salir" . PHP_EOL;
echo PHP_EOL;

echo "Ver configuración completa en: backend/config/cron_setup.txt" . PHP_EOL;

// Mostrar estado final
echo PHP_EOL . "============================================================" . PHP_EOL;
echo "📋 ESTADO FINAL" . PHP_EOL;
echo "============================================================" . PHP_EOL;

$status = $updater->getLastUpdateStatus();
echo "Archivo actual: " . ($status['current_file_exists'] ? '✅ Existe' : '❌ No existe') . PHP_EOL;
echo "Archivo anterior: " . ($status['previous_file_exists'] ? '✅ Existe' : '❌ No existe') . PHP_EOL;
echo "Última modificación: " . ($status['current_file_modified'] ?? 'N/A') . PHP_EOL;
echo "Tamaño archivo actual: " . number_format($status['current_file_size']) . " bytes" . PHP_EOL;

echo PHP_EOL . "=== Instalación completada ===" . PHP_EOL;
echo "El sistema está listo para funcionar automáticamente." . PHP_EOL;