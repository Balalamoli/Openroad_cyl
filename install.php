<?php
/**
 * OpenRoadCyL - Script de Instalación y Configuración Segura
 * Ejecutar una sola vez para configurar la aplicación
 */

echo "=== INSTALACIÓN OPENROADCYL ===\n\n";

// 1. Verificar requisitos
echo "1. Verificando requisitos del sistema...\n";

$requirements = [
    'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'OpenSSL' => extension_loaded('openssl'),
    'JSON' => extension_loaded('json'),
];

$all_ok = true;
foreach ($requirements as $req => $status) {
    if ($status) {
        echo "✅ $req\n";
    } else {
        echo "❌ $req - REQUERIDO\n";
        $all_ok = false;
    }
}

if (!$all_ok) {
    die("\n❌ Algunos requisitos no se cumplen. Instala las extensiones faltantes.\n");
}

// 2. Verificar archivo .env
echo "\n2. Verificando configuración...\n";

if (!file_exists('.env')) {
    echo "❌ Archivo .env no encontrado.\n";
    echo "📋 Copiando .en