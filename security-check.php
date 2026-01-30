<?php
/**
 * OpenRoadCyL - Verificación de Seguridad
 * Script para verificar que la seguridad está funcionando correctamente
 */

echo "=== VERIFICACIÓN DE SEGURIDAD OPENROADCYL ===\n\n";

$checks = [];
$warnings = [];

// 1. Verificar archivos de seguridad
echo "1. Verificando archivos de seguridad...\n";

if (file_exists('backend/config/security.php')) {
    $checks[] = "✅ Archivo de configuración de seguridad presente";
} else {
    $warnings[] = "⚠️ Archivo de configuración de seguridad no encontrado";
}

if (file_exists('.htaccess')) {
    $checks[] = "✅ Archivo .htaccess presente";
} else {
    $warnings[] = "⚠️ Archivo .htaccess no encontrado";
}

if (is_dir('backend/logs')) {
    $checks[] = "✅ Directorio de logs presente";
} else {
    $warnings[] = "⚠️ Directorio de logs no encontrado";
}

// 2. Verificar permisos básicos
echo "\n2. Verificando permisos...\n";

if (is_writable('backend/logs')) {
    $checks[] = "✅ Directorio de logs es escribible";
} else {
    $warnings[] = "⚠️ Directorio de logs no es escribible";
}

// 3. Verificar configuración PHP básica
echo "\n3. Verificando configuración PHP...\n";

if (!ini_get('display_errors')) {
    $checks[] = "✅ display_errors está deshabilitado";
} else {
    $warnings[] = "⚠️ display_errors está habilitado (recomendado desactivar en producción)";
}

// 4. Probar funciones de seguridad
echo "\n4. Probando funciones de seguridad...\n";

if (file_exists('backend/config/security.php')) {
    require_once 'backend/config/security.php';
    
    if (class_exists('SecurityConfig')) {
        $testInput = "<script>alert('test')</script>";
        $sanitized = SecurityConfig::sanitizeInput($testInput);
        
        if ($sanitized !== $testInput) {
            $checks[] = "✅ Sanitización funcionando correctamente";
        } else {
            $warnings[] = "⚠️ Sanitización no está funcionando";
        }
        
        // Probar logging
        try {
            SecurityConfig::logEvent('test_event', ['test' => true]);
            $checks[] = "✅ Sistema de logging funcionando";
        } catch (Exception $e) {
            $warnings[] = "⚠️ Error en sistema de logging: " . $e->getMessage();
        }
    }
}

// Mostrar resultados
echo "\n=== RESULTADOS ===\n";

if (!empty($checks)) {
    echo "\n✅ CONFIGURACIONES CORRECTAS:\n";
    foreach ($checks as $check) {
        echo "$check\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️ ADVERTENCIAS:\n";
    foreach ($warnings as $warning) {
        echo "$warning\n";
    }
}

// Calcular puntuación
$total = count($checks) + count($warnings);
$score = $total > 0 ? round((count($checks) / $total) * 10) : 0;

echo "\n=== PUNTUACIÓN DE SEGURIDAD ===\n";
echo "Puntuación: $score/10\n";

if ($score >= 8) {
    echo "🟢 EXCELENTE: Configuración de seguridad sólida\n";
} elseif ($score >= 6) {
    echo "🟡 BUENO: Configuración aceptable\n";
} else {
    echo "🟠 MEJORABLE: Revisar configuraciones\n";
}

echo "\n=== RECOMENDACIONES ===\n";
echo "1. Usar HTTPS en producción\n";
echo "2. Configurar copias de seguridad regulares\n";
echo "3. Mantener el software actualizado\n";
echo "4. Revisar logs periódicamente\n";

?>