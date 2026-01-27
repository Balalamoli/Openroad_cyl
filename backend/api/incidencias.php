<?php
/**
 * OpenRoadCyL - API REST para Incidencias
 * Endpoint principal para obtener datos de incidencias
 * Green Coding: Respuestas optimizadas y caché de headers
 */

// Configurar para que los errores no rompan el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Green Coding: Headers de caché para reducir peticiones
header('Cache-Control: public, max-age=300'); // 5 minutos de caché
header('ETag: ' . md5(filemtime(__FILE__)));

// Manejar preflight OPTIONS
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
    error_log("Error en API incidencias: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Maneja peticiones GET
 * Green Coding: Parámetros opcionales para filtrar datos
 */
function handleGetRequest($controller) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Filtros opcionales (Green Coding: solo datos necesarios)
            $provincia = $_GET['provincia'] ?? null;
            $tipo = $_GET['tipo'] ?? null;
            $estado = $_GET['estado'] ?? null;
            
            $result = $controller->listar($provincia, $tipo, $estado);
            break;
            
        case 'detail':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID requerido'
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
    
    // Green Coding: Respuesta comprimida si es posible
    if (function_exists('gzencode') && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
        header('Content-Encoding: gzip');
        echo gzencode(json_encode($result, JSON_UNESCAPED_UNICODE));
    } else {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Maneja peticiones POST
 */
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