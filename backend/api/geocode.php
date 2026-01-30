<?php
/**
 * OpenRoadCyL - API de Geocodificación
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $via = $_GET['via'] ?? null;
        $provincia = $_GET['provincia'] ?? null;
        
        if (!$via || !$provincia) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Via y provincia requeridas']);
            exit();
        }
        
        $sql = "SELECT latitud, longitud FROM carreteras_geocache 
                WHERE via = ? AND provincia = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$via, $provincia]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'cached' => true,
                'lat' => (float)$result['latitud'],
                'lng' => (float)$result['longitud']
            ]);
            exit();
        }
        
        $query = "$via, $provincia, Spain";
        $encodedQuery = urlencode($query);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://nominatim.openstreetmap.org/search?q=$encodedQuery&format=json&limit=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => ['User-Agent: OpenRoadCyL']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (empty($response) || $httpCode !== 200) {
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'message' => 'Geocoding service temporarily unavailable'
            ]);
            exit();
        }
        
        $results = json_decode($response, true);
        
        if (is_array($results) && count($results) > 0) {
            $lat = (float)$results[0]['lat'];
            $lng = (float)$results[0]['lon'];
            
            try {
                $insertSql = "INSERT IGNORE INTO carreteras_geocache (via, provincia, latitud, longitud) 
                              VALUES (?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([$via, $provincia, $lat, $lng]);
            } catch (Exception $e) {
                // No es crítico si falla el cache
            }
            
            echo json_encode([
                'success' => true,
                'cached' => false,
                'lat' => $lat,
                'lng' => $lng
            ]);
        } else {
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'message' => 'No coordinates found'
            ]);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    error_log("Error en API geocode: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
