<?php
/**
 * Script para generar hashes de contraseñas de prueba
 * Ejecutar una sola vez para obtener los hashes correctos
 */

// Contraseñas de prueba
$passwords = [
    'admin' => 'admin123',      // Contraseña para el usuario admin
    'test' => 'test123'         // Contraseña para el usuario test
];

echo "Hashes de contraseñas generados:\n";
echo "================================\n\n";

foreach ($passwords as $user => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Usuario: $user\n";
    echo "Contraseña: $password\n";
    echo "Hash: $hash\n\n";
}

echo "\nSQL INSERT actualizado:\n";
echo "========================\n\n";
echo "INSERT INTO usuarios (nombre, email, password) VALUES \n";
echo "('Admin', 'admin@openroadcyl.es', '" . password_hash('admin123', PASSWORD_DEFAULT) . "'),\n";
echo "('Usuario Test', 'test@openroadcyl.es', '" . password_hash('test123', PASSWORD_DEFAULT) . "');\n";
?>
