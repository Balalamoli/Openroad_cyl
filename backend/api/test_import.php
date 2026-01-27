<?php
// Test simple para import_jcyl.php
ob_start();
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'test' => 'endpoint is working',
    'message' => 'Si ves esto, el endpoint está funcionando'
]);
ob_end_flush();
?>
