# Sistema de Comparación de JSONs JCyL

## Descripción

Este sistema permite comparar dos archivos JSON de incidencias de la Junta de Castilla y León para determinar automáticamente el estado de las incidencias basándose en su presencia en ambos archivos.

## Lógica de Estados

El sistema asigna estados automáticamente según la presencia de las incidencias en los archivos:

### 🟢 **ACTIVA**
- **Condición**: La incidencia aparece **SOLO en el archivo NUEVO**
- **Significado**: Es una incidencia nueva que acaba de aparecer
- **Estado en BD**: `activa`

### 🟡 **EN PROCESO** 
- **Condición**: La incidencia aparece **en AMBOS archivos** (anterior y nuevo)
- **Significado**: Es una incidencia que continúa sin resolverse
- **Estado en BD**: `en_proceso`

### 🔴 **RESUELTA**
- **Condición**: La incidencia aparece **SOLO en el archivo ANTERIOR**
- **Significado**: La incidencia ya no existe, se ha resuelto
- **Estado en BD**: `resuelta`

## Identificación de Incidencias

Para determinar si una incidencia es la "misma" entre archivos, el sistema genera una clave única basada en:

- **Provincia**: Ej: "Ávila", "León"
- **Via**: Ej: "CL-605", "AV-501"
- **PKInicio**: Punto kilométrico de inicio
- **PKFin**: Punto kilométrico de fin
- **Tipo**: Ej: "Precaución", "Cadenas"
- **Causa**: Ej: "Obras", "Nieve"

## Uso del Sistema

### 1. Preparar Archivos

Coloca los archivos JSON en la carpeta `backend/data/`:
- `incidencias_anterior.json` - Datos del período anterior
- `incidencias_nuevo.json` - Datos actuales

### 2. Usar la Interfaz Web

1. **Abrir Modal**: Clic en "🔄 Comparar JSONs"
2. **Cargar Archivos**: Clic en "🔄 Cargar Lista de Archivos"
3. **Seleccionar Archivos**: 
   - Archivo Anterior: Seleccionar el JSON más antiguo
   - Archivo Nuevo: Seleccionar el JSON más reciente
4. **Previsualizar**: Clic en "👁️ Previsualizar" (opcional)
5. **Ejecutar**: Clic en "🚀 Ejecutar Comparación"

### 3. Usar la API Directamente

```bash
# Comparar archivos
curl -X POST http://localhost/backend/api/compare_jcyl.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "compare",
    "old_file": "incidencias_anterior.json",
    "new_file": "incidencias_nuevo.json"
  }'
```

## Estructura de Respuesta

```json
{
  "success": true,
  "message": "Comparación completada: 15 nuevas, 23 en proceso, 8 resueltas",
  "imported": 15,    // Incidencias nuevas (activas)
  "updated": 23,     // Incidencias continuas (en proceso)
  "resolved": 8,     // Incidencias resueltas
  "total_processed": 46
}
```

## Características Técnicas

### Geocodificación Inteligente
- **Cache Local**: Coordenadas se almacenan en `carreteras_geocache`
- **Timeout**: 2 segundos máximo por geocodificación
- **Fallback**: Coordenadas del centro de provincia si falla

### Optimizaciones
- **Transacciones**: Rollback automático en caso de error
- **Índices Únicos**: Evita duplicados en la base de datos
- **Procesamiento Batch**: Maneja grandes volúmenes de datos

### Mapeo de Tipos
El sistema convierte los tipos de JCyL a nuestros tipos estándar:

| JCyL | Nuestro Sistema |
|------|----------------|
| Obras/Causa: obra | Obras |
| Nieve/Hielo/Cadenas | Meteorológica |
| Desprendimientos | Meteorológica |
| Cortada/Cerrada | Retención |
| Accidente | Accidente |
| Otros | Retención |

## Archivos del Sistema

### Backend
- `backend/controllers/ImporterJCyL.php` - Lógica principal de comparación
- `backend/api/compare_jcyl.php` - API REST para comparación
- `backend/api/import_jcyl.php` - API de importación (actualizada)

### Frontend
- `frontend/js/main.js` - Funcionalidad JavaScript
- `frontend/css/styles.css` - Estilos del modal
- `frontend/index.html` - Modal de comparación

### Base de Datos
- `carreteras_geocache` - Cache de coordenadas
- `incidencias` - Tabla principal con campo `estado`

## Ejemplo de Uso

### Escenario
- **Archivo Anterior**: 50 incidencias del lunes
- **Archivo Nuevo**: 45 incidencias del martes

### Resultado Esperado
- **10 Nuevas** → Estado: `activa` (aparecen solo el martes)
- **35 Continuas** → Estado: `en_proceso` (aparecen ambos días)
- **15 Resueltas** → Estado: `resuelta` (solo aparecían el lunes)

### Verificación
```sql
-- Ver distribución de estados después de la comparación
SELECT estado, COUNT(*) as total 
FROM incidencias 
WHERE fuente LIKE 'jcyl%' 
GROUP BY estado;
```

## Solución de Problemas

### Error: "Archivo no encontrado"
- Verificar que los archivos estén en `backend/data/`
- Comprobar permisos de lectura

### Error: "Formato JSON inválido"
- Validar estructura del JSON
- Verificar que tenga el campo `incidencias`

### Geocodificación lenta
- El sistema tiene timeout de 2 segundos
- Usa fallback a coordenadas de provincia
- Cache evita repetir geocodificaciones

### Estados incorrectos
- Verificar que los archivos sean del período correcto
- Comprobar que las incidencias tengan campos únicos consistentes

## Mantenimiento

### Limpiar Cache de Geocodificación
```sql
DELETE FROM carreteras_geocache WHERE fecha_geocodificacion < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Backup antes de Comparación
```bash
mysqldump -u usuario -p openroadcyl incidencias > backup_incidencias.sql
```

### Logs de Error
- `backend/logs/api_errors.log` - Errores de API
- Error log de PHP para errores de geocodificación