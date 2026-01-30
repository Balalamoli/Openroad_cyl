<?php
/**
 * Script para actualizar el esquema de la base de datos
 * Agrega índice único para prevenir duplicados
 */

require_once __DIR__ . '/../backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Iniciando actualización del esquema...\n";
    
    // 1. Agregar columna fuente si no existe
    echo "1. Agregando columna 'fuente'...\n";
    try {
        $conn->exec("ALTER TABLE incidencias ADD COLUMN fuente ENUM('manual', 'jcyl') DEFAULT 'manual' AFTER estado");
        echo "   ✓ Columna 'fuente' agregada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ✓ Columna 'fuente' ya existe\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Normalizar datos existentes
    echo "2. Normalizando datos existentes...\n";
    $stmt = $conn->prepare("UPDATE incidencias SET 
        carretera = UPPER(TRIM(carretera)),
        pk = CASE 
            WHEN pk = '' OR pk = '0' THEN NULL 
            ELSE TRIM(pk) 
        END");
    $stmt->execute();
    echo "   ✓ Datos normalizados\n";
    
    // 3. Eliminar duplicados existentes
    echo "3. Eliminando duplicados existentes...\n";
    $stmt = $conn->prepare("
        DELETE i1 FROM incidencias i1
        INNER JOIN incidencias i2 
        WHERE i1.id < i2.id 
          AND i1.carretera = i2.carretera
          AND i1.provincia = i2.provincia
          AND (
            (i1.pk IS NOT NULL AND i2.pk IS NOT NULL AND i1.pk = i2.pk)
            OR
            (i1.pk IS NULL AND i2.pk IS NULL AND i1.tipo = i2.tipo)
          )
    ");
    $stmt->execute();
    $duplicados_eliminados = $stmt->rowCount();
    echo "   ✓ $duplicados_eliminados duplicados eliminados\n";
    
    // 4. Crear columna calculada para índice único
    echo "4. Creando columna calculada...\n";
    try {
        $conn->exec("ALTER TABLE incidencias 
            ADD COLUMN unique_key VARCHAR(200) GENERATED ALWAYS AS (
                CONCAT(
                    carretera, '|',
                    provincia, '|',
                    COALESCE(pk, CONCAT('NULL_', tipo))
                )
            ) STORED");
        echo "   ✓ Columna calculada 'unique_key' creada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ✓ Columna 'unique_key' ya existe\n";
        } else {
            throw $e;
        }
    }
    
    // 5. Crear índice único
    echo "5. Creando índice único...\n";
    try {
        $conn->exec("ALTER TABLE incidencias ADD UNIQUE INDEX idx_unique_incidencia (unique_key)");
        echo "   ✓ Índice único creado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "   ✓ Índice único ya existe\n";
        } else {
            throw $e;
        }
    }
    
    // 6. Verificar resultado
    echo "6. Verificando resultado...\n";
    $stmt = $conn->prepare("
        SELECT COUNT(*) as duplicados_restantes
        FROM (
            SELECT unique_key, COUNT(*) as cnt
            FROM incidencias 
            GROUP BY unique_key
            HAVING COUNT(*) > 1
        ) as duplicados
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   ✓ Duplicados restantes: " . $result['duplicados_restantes'] . "\n";
    
    // 7. Mostrar estadísticas finales
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM incidencias");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n=== ACTUALIZACIÓN COMPLETADA ===\n";
    echo "Total de incidencias: $total\n";
    echo "Duplicados eliminados: $duplicados_eliminados\n";
    echo "Duplicados restantes: " . $result['duplicados_restantes'] . "\n";
    echo "Índice único: ACTIVO\n";
    echo "================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>