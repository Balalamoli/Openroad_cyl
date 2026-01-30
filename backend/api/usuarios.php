<?php
/**
 * OpenRoadCyL - API REST para Usuarios
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../controllers/UsuarioController.php';

try {
    $controller = new UsuarioController();
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
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Error en API usuarios: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

function handleGetRequest($controller) {
    $action = $_GET['action'] ?? 'session';
    
    switch ($action) {
        case 'session':
            $result = $controller->verificarSesion();
            break;
            
        case 'profile':
            $result = $controller->perfil();
            break;
            
        case 'favoritos':
            $result = $controller->getFavoritos();
            break;
            
        case 'logout':
            $result = $controller->logout();
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
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'register':
            $result = $controller->registrar($input);
            break;
            
        case 'login':
            $result = $controller->login($input['email'] ?? '', $input['password'] ?? '');
            break;
            
        case 'favorito':
            $incidencia_id = $input['incidencia_id'] ?? null;
            $accion = $input['accion'] ?? null;
            
            if (!$incidencia_id || !$accion) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'incidencia_id y accion son requeridos'
                ]);
                return;
            }
            
            $result = $controller->gestionarFavorito($incidencia_id, $accion);
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