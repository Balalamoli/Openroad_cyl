<?php
/**
 * OpenRoadCyL - Controlador de Usuarios
 * Lógica de negocio para autenticación y gestión de usuarios
 */

require_once '../models/Usuario.php';

class UsuarioController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario();
    }

    /**
     * Registra un nuevo usuario
     * Seguridad: Validación de datos y sesiones seguras
     */
    public function registrar($datos) {
        try {
            // Validaciones básicas
            if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['password'])) {
                return [
                    'success' => false,
                    'message' => 'Todos los campos son obligatorios'
                ];
            }

            if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Email no válido'
                ];
            }

            if (strlen($datos['password']) < 6) {
                return [
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ];
            }

            $this->usuario->nombre = $datos['nombre'];
            $this->usuario->email = $datos['email'];
            $this->usuario->password = $datos['password'];

            if ($this->usuario->register()) {
                // Iniciar sesión automáticamente después del registro
                $this->iniciarSesion($this->usuario->id, $this->usuario->nombre, $this->usuario->email);

                return [
                    'success' => true,
                    'message' => 'Usuario registrado correctamente',
                    'user' => [
                        'id' => $this->usuario->id,
                        'nombre' => $this->usuario->nombre,
                        'email' => $this->usuario->email
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en UsuarioController::registrar: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Autentica usuario
     * Seguridad: Sesiones seguras con regeneración de ID
     */
    public function login($email, $password) {
        try {
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Email y contraseña son obligatorios'
                ];
            }

            if ($this->usuario->login($email, $password)) {
                // Iniciar sesión segura
                $this->iniciarSesion($this->usuario->id, $this->usuario->nombre, $this->usuario->email);

                return [
                    'success' => true,
                    'message' => 'Login exitoso',
                    'user' => [
                        'id' => $this->usuario->id,
                        'nombre' => $this->usuario->nombre,
                        'email' => $this->usuario->email
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en UsuarioController::login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Cierra sesión del usuario
     */
    public function logout() {
        try {
            // Destruir sesión de forma segura
            session_start();
            session_unset();
            session_destroy();
            
            // Regenerar ID de sesión por seguridad
            session_start();
            session_regenerate_id(true);

            return [
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ];

        } catch (Exception $e) {
            error_log("Error en UsuarioController::logout: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cerrar sesión'
            ];
        }
    }

    /**
     * Verifica si el usuario está autenticado
     */
    public function verificarSesion() {
        session_start();
        
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
            return [
                'success' => true,
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'nombre' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email']
                ]
            ];
        } else {
            return [
                'success' => true,
                'authenticated' => false
            ];
        }
    }

    /**
     * Obtiene perfil del usuario
     */
    public function perfil() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'message' => 'Usuario no autenticado'
            ];
        }

        try {
            if ($this->usuario->getById($_SESSION['user_id'])) {
                return [
                    'success' => true,
                    'user' => [
                        'id' => $this->usuario->id,
                        'nombre' => $this->usuario->nombre,
                        'email' => $this->usuario->email,
                        'fecha_registro' => $this->usuario->fecha_registro
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en UsuarioController::perfil: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener perfil'
            ];
        }
    }

    /**
     * Gestiona favoritos del usuario
     */
    public function gestionarFavorito($incidencia_id, $accion) {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'message' => 'Usuario no autenticado'
            ];
        }

        try {
            $this->usuario->id = $_SESSION['user_id'];
            
            if ($accion === 'add') {
                $result = $this->usuario->addFavorito($incidencia_id);
                $message = $result ? 'Agregado a favoritos' : 'Error al agregar favorito';
            } else if ($accion === 'remove') {
                $result = $this->usuario->removeFavorito($incidencia_id);
                $message = $result ? 'Eliminado de favoritos' : 'Error al eliminar favorito';
            } else {
                return [
                    'success' => false,
                    'message' => 'Acción no válida'
                ];
            }

            return [
                'success' => $result,
                'message' => $message
            ];

        } catch (Exception $e) {
            error_log("Error en UsuarioController::gestionarFavorito: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al gestionar favorito'
            ];
        }
    }

    /**
     * Obtiene favoritos del usuario
     */
    public function getFavoritos() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'message' => 'Usuario no autenticado'
            ];
        }

        try {
            $this->usuario->id = $_SESSION['user_id'];
            $favoritos = $this->usuario->getFavoritos();

            return [
                'success' => true,
                'data' => $favoritos
            ];

        } catch (Exception $e) {
            error_log("Error en UsuarioController::getFavoritos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener favoritos',
                'data' => []
            ];
        }
    }

    /**
     * Inicia sesión segura
     * Seguridad: Regeneración de ID de sesión y configuración segura
     */
    private function iniciarSesion($user_id, $nombre, $email) {
        // Configuración de sesión segura
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
        
        session_start();
        session_regenerate_id(true); // Regenerar ID por seguridad
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_email'] = $email;
        $_SESSION['login_time'] = time();
    }
}
?>