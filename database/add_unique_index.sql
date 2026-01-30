-- Script para agregar índice único y prevenir duplicados
-- OpenRoadCyL - Prevención de duplicados en incidencias

USE openroadcyl;

-- Agregar columna fuente si no existe
ALTER TABLE incidencias 
ADD COLUMN fuente ENUM('manual', 'jcyl') DEFAULT 'manual' 
AFTER estado;

-- Normalizar datos existentes
UPDATE incidencias SET 
  carretera = UPPER(TRIM(carretera)),
  pk = CASE 
    WHEN pk = '' OR pk = '0' THEN NULL 
    ELSE TRIM(pk) 
  END;

-- Eliminar duplicados existentes (mantener el más reciente)
DELETE i1 FROM incidencias i1
INNER JOIN incidencias i2 
WHERE i1.id < i2.id 
  AND i1.carretera = i2.carretera
  AND i1.provincia = i2.provincia
  AND (
    (i1.pk IS NOT NULL AND i2.pk IS NOT NULL AND i1.pk = i2.pk)
    OR
    (i1.pk IS NULL AND i2.pk IS NULL AND i1.tipo = i2.tipo)
  );

-- Crear columna calculada para índice único
ALTER TABLE incidencias 
ADD COLUMN unique_key VARCHAR(200) GENERATED ALWAYS AS (
  CONCAT(
    carretera, '|',
    provincia, '|',
    COALESCE(pk, CONCAT('NULL_', tipo))
  )
) STORED;

-- Crear índice único
ALTER TABLE incidencias 
ADD UNIQUE INDEX idx_unique_incidencia (unique_key);

-- Verificar resultado
SELECT 'Duplicados restantes:' as resultado, COUNT(*) as cantidad
FROM (
  SELECT unique_key, COUNT(*) as cnt
  FROM incidencias 
  GROUP BY unique_key
  HAVING COUNT(*) > 1
) as duplicados;