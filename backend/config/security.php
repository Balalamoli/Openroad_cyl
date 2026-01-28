<?php
/**
 * OpenRoadCyL - Configuración de Seguridad Básica
 * Funciones simples de seguridad sin afectar funcionalidad
 */

class SecurityConfig {
    
    /**
     * Sanitiza entrada de datos de forma básica
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        // Limpieza básica
        $data = trim($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Rate limiting muy básico y permisivo
     */
    public static function checkRateLimit($maxRequests = 200, $timeWindow = 3600) {
        // Solo aplicar si las sesiones están disponibles
        if (session_status() == PHP_SESSION_NONE) {
            @session_start();
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . md5($ip);
        
        $now = time();
        $requests = $_SESSION[$key] ?? [];
        
        // Limpiar requests antiguos
        $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Verificar límite (muy permisivo)
        if (count($requests) >= $maxRequests) {
            self::logEvent('rate_limit_exceeded', ['ip' => $ip, 'requests' => count($requests)]);
            return false;
        }
        
        // Agregar request actual
        $requests[] = $now;
        $_SESSION[$key] = $requests;
        
        return true;
    }
    public static function logEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>