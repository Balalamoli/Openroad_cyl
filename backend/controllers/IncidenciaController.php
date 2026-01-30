<?php
/**
 * OpenRoadCyL - Controlador de Incidencias
 * Lógica de negocio para gestión de incidencias
 */

require_once '../models/Incidencia.php';

class IncidenciaController {
    private $incidencia;

    public function __construct() {
        $this->incidencia = new Incidencia();
    }

    /**
     * Lista todas las incidencias con filtros opcionales
     * Green Coding: Respuesta JSON minificada
     */
    public function listar($provincia = null, $tipo = null, $estado = null) {
        try {
            $incidencias = $this->incidencia->getAll($provincia, $tipo, $estado);
            
            // Green Coding: Optimizar respuesta, solo campos necesarios para el mapa
            $response = array_map(function($inc) {
                return [
                    'id' => (int)$inc['id'],
                    'tipo' => $inc['tipo'],
                    'descripcion' => $inc['descripcion'],
                    'provincia' => $inc['provincia'],
                    'carretera' => $inc['carretera'],
                    'pk' => $inc['pk'],
                    'estado' => $inc['estado'],
                    'lat' => (float)$inc['latitud'],
                    'lng' => (float)$inc['longitud'],
                    'fecha' => date('Y-m-d H:i', strtotime($inc['fecha_actualizacion']))
                ];
            }, $incidencias);

            return [
                'success' => true,
                'data' => $response,
                'total' => count($response)
            ];

        } catch (Exception $e) {
            error_log("Error en IncidenciaController::listar: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener incidencias',
                'data' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas por provincia
     * Green Coding: Datos agregados para reducir transferencia
     */
    public function estadisticasProvincia() {
        try {
            $stats = $this->incidencia->getStatsByProvincia();
            
            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("Error en IncidenciaController::estadisticasProvincia: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'data' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas por tipo
     */
    public function estadisticasTipo() {
        try {
            $stats = $this->incidencia->getStatsByTipo();
            
            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("Error en IncidenciaController::estadisticasTipo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas por tipo',
                'data' => []
            ];
        }
    }

    /**
     * Obtiene detalle de una incidencia
     */
    public function detalle($id) {
        try {
            $incidencia = $this->incidencia->getById($id);
            
            if ($incidencia) {
                return [
                    'success' => true,
                    'data' => $incidencia
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Incidencia no encontrada'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en IncidenciaController::detalle: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener detalle de incidencia'
            ];
        }
    }

    /**
     * Crea nueva incidencia
     */
    public function crear($datos) {
        try {
            $this->incidencia->tipo = $datos['tipo'] ?? '';
            $this->incidencia->descripcion = $datos['descripcion'] ?? '';
            $this->incidencia->provincia = $datos['provincia'] ?? '';
            $this->incidencia->carretera = $datos['carretera'] ?? '';
            $this->incidencia->pk = $datos['pk'] ?? null;
            $this->incidencia->latitud = $datos['latitud'] ?? null;
            $this->incidencia->longitud = $datos['longitud'] ?? null;
            $this->incidencia->estado = $datos['estado'] ?? 'activa';
            $this->incidencia->fuente = $datos['fuente'] ?? 'manual'; // Agregar fuente

            if ($this->incidencia->create()) {
                return [
                    'success' => true,
                    'message' => 'Incidencia creada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Incidencia duplicada o error al crear'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en IncidenciaController::crear: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear incidencia'
            ];
        }
    }

    /**
     * Obtiene las provincias disponibles
     * Green Coding: Lista única para filtros
     */
    public function getProvincias() {
        try {
            $provincias = $this->incidencia->getProvincias();

            return [
                'success' => true,
                'data' => $provincias
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener provincias',
                'data' => []
            ];
        }
    }

    /**
     * Obtiene los tipos disponibles
     */
    public function getTipos() {
        try {
            $tipos = $this->incidencia->getTipos();

            return [
                'success' => true,
                'data' => $tipos
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener tipos',
                'data' => []
            ];
        }
    }
}
?>