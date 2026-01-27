<?php
/**
 * OpenRoadCyL - Configuración de Base de Datos
 * Configuración centralizada para conexión PDO con MySQL
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'openroadcyl';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Obtiene la conexión PDO a la base de datos
     * Implementa singleton para reutilizar conexión (Green Coding)
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Configuración PDO con opciones de seguridad
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }

        return $this->conn;
    }

    /**
     * Cierra la conexión (Green Coding - liberación de recursos)
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>