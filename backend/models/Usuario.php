<?php
/**
 * OpenRoadCyL - Modelo Usuario
 * Manejo de autenticación y gestión de usuarios
 */

require_once '../config/database.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $fecha_registro;
    public $activo;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Registra un nuevo usuario
     * Seguridad: password_hash para encriptar contraseñas
     */
    public function register() {
        // Verificar si el email ya existe
        if ($this->emailExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " (nombre, email, password) VALUES (:nombre, :email, :password)";
        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Encriptar contraseña (Seguridad)
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Autentica usuario
     * Seguridad: password_verify para verificar contraseñas hasheadas
     */
    public function login($email, $password) {
        $query = "SELECT id, nombre, email, password FROM " . $this->table_name . " WHERE email = :email AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña (Seguridad)
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->nombre = $row['nombre'];
                $this->email = $row['email'];
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el email ya existe
     */
    private function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene datos del usuario por ID
     */
    public function getById($id) {
        $query = "SELECT id, nombre, email, fecha_registro FROM " . $this->table_name . " WHERE id = :id AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->fecha_registro = $row['fecha_registro'];
            return true;
        }

        return false;
    }

    /**
     * Agrega incidencia a favoritos
     */
    public function addFavorito($incidencia_id) {
        $query = "INSERT IGNORE INTO favoritos (usuario_id, incidencia_id) VALUES (:usuario_id, :incidencia_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $this->id);
        $stmt->bindParam(':incidencia_id', $incidencia_id);
        
        return $stmt->execute();
    }

    /**
     * Elimina incidencia de favoritos
     */
    public function removeFavorito($incidencia_id) {
        $query = "DELETE FROM favoritos WHERE usuario_id = :usuario_id AND incidencia_id = :incidencia_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $this->id);
        $stmt->bindParam(':incidencia_id', $incidencia_id);
        
        return $stmt->execute();
    }

    /**
     * Obtiene favoritos del usuario
     */
    public function getFavoritos() {
        $query = "SELECT i.* FROM incidencias i 
                  INNER JOIN favoritos f ON i.id = f.incidencia_id 
                  WHERE f.usuario_id = :usuario_id 
                  ORDER BY f.fecha_agregado DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $this->id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>