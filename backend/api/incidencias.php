<?php
/**
 * OpenRoadCyL - API REST para Incidencias
 */

$securityLoaded = false;
if (file_exists('../config/security.php')) {
    try {
        require_once '../config/security.php';
        $securityLoaded = true;
    } catch (Exception $e) {
        error_log("No se pudo cargar configuración de seguridad: " . $e->getMessage());
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-XSS-Protection: 1; mode=block');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($securityLoaded && class_exists('SecurityConfig')) {
    try {
        $rateLimitOk = SecurityConfig::checkRateLimit(200, 3600);
        if (!$rateLimitOk) {
            SecurityConfig::logEvent('rate_limit_warning', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        }
    } catch (Exception $e) {
        error_log("Rate limiting error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../controllers/IncidenciaController.php';

try {
    $controller = new IncidenciaController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($controller);
            break;
            
        case 'POST':
            handlePostRequest($controller);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    $logEntry = date('Y-m-d H:i:s') . " - Error en API: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../logs/api_errors.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    error_log("Error en API incidencias: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function handleGetRequest($controller) {
    $action = $_GET['action'] ?? 'list';
    
    global $securityLoaded;
    if ($securityLoaded && class_exists('SecurityConfig')) {
        $action = SecurityConfig::sanitizeInput($action);
    } else {
        $action = htmlspecialchars(trim($action), ENT_QUOTES, 'UTF-8');
    }
    
    switch ($action) {
        case 'list':
            $provincia = $_GET['provincia'] ?? null;
            $tipo = $_GET['tipo'] ?? null;
            $estado = $_GET['estado'] ?? null;
            
            if ($provincia) {
                $provincia = $securityLoaded && class_exists('SecurityConfig') ? 
                    SecurityConfig::sanitizeInput($provincia) : 
                    htmlspecialchars(trim($provincia), ENT_QUOTES, 'UTF-8');
            }
            if ($tipo) {
                $tipo = $securityLoaded && class_exists('SecurityConfig') ? 
                    SecurityConfig::sanitizeInput($tipo) : 
                    htmlspecialchars(trim($tipo), ENT_QUOTES, 'UTF-8');
            }
            if ($estado) {
                $estado = $securityLoaded && class_exists('SecurityConfig') ? 
                    SecurityConfig::sanitizeInput($estado) : 
                    htmlspecialchars(trim($estado), ENT_QUOTES, 'UTF-8');
            }
            
            $result = $controller->listar($provincia, $tipo, $estado);
            break;
            
        case 'detail':
            $id = $_GET['id'] ?? null;
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID numérico requerido'
                ]);
                return;
            }
            $result = $controller->detalle($id);
            break;
            
        case 'stats-provincia':
            $result = $controller->estadisticasProvincia();
            break;
            
        case 'stats-tipo':
            $result = $controller->estadisticasTipo();
            break;
            
        case 'provincias':
            $result = $controller->getProvincias();
            break;
            
        case 'tipos':
            $result = $controller->getTipos();
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            return;
    }
    
    if (function_exists('gzencode') && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
        header('Content-Encoding: gzip');
        echo gzencode(json_encode($result, JSON_UNESCAPED_UNICODE));
    } else {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

function handlePostRequest($controller) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Datos JSON requeridos'
        ]);
        return;
    }
    
    $action = $input['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            $requiredFields = ['tipo', 'descripcion', 'provincia', 'latitud', 'longitud'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Campo requerido: $field"
                    ]);
                    return;
                }
            }
            
            $result = $controller->crear($input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            return;
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
?>