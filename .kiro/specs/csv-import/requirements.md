# Documento de Requisitos

## Introducción

Esta especificación define la funcionalidad de importación de incidencias desde archivos CSV para el proyecto OpenRoadCyL. La nueva funcionalidad permitirá a los usuarios cargar archivos CSV con datos de incidencias y ver automáticamente las nuevas incidencias reflejadas en el mapa y gráficos sin necesidad de recargar la página.

## Glosario

- **Sistema**: El sistema OpenRoadCyL existente
- **CSV_Importer**: Componente que procesa archivos CSV de incidencias
- **File_Uploader**: Interfaz para subir archivos CSV
- **Database_Manager**: Componente que gestiona la inserción de datos en MySQL
- **UI_Updater**: Componente que actualiza la interfaz automáticamente
- **Incidencia**: Registro de incidente de tráfico con campos: id, tipo, descripción, latitud, longitud, fecha, estado
- **Usuario**: Usuario autenticado del sistema OpenRoadCyL

## Requisitos

### Requisito 1: Subida de Archivos CSV

**User Story:** Como usuario del sistema, quiero subir un archivo CSV con incidencias, para poder importar múltiples incidencias de forma masiva.

#### Criterios de Aceptación

1. WHEN un usuario selecciona un archivo CSV válido, THE File_Uploader SHALL aceptar el archivo y mostrarlo como listo para procesar
2. WHEN un usuario intenta subir un archivo que no es CSV, THE File_Uploader SHALL rechazar el archivo y mostrar un mensaje de error descriptivo
3. WHEN un archivo CSV excede 5MB de tamaño, THE File_Uploader SHALL rechazar el archivo y mostrar un mensaje de límite de tamaño
4. THE File_Uploader SHALL proporcionar una interfaz visual clara con botón de selección y área de arrastrar y soltar
5. WHEN un archivo es seleccionado, THE File_Uploader SHALL mostrar el nombre del archivo y su tamaño

### Requisito 2: Validación y Procesamiento de CSV

**User Story:** Como usuario del sistema, quiero que el archivo CSV sea validado y procesado correctamente, para asegurar que solo se importen datos válidos.

#### Criterios de Aceptación

1. WHEN se procesa un archivo CSV, THE CSV_Importer SHALL validar que contenga las columnas requeridas: tipo, descripcion, latitud, longitud, fecha, estado
2. WHEN una fila del CSV tiene datos inválidos, THE CSV_Importer SHALL registrar el error y continuar procesando las filas válidas
3. WHEN todas las filas del CSV son inválidas, THE CSV_Importer SHALL rechazar el archivo completo y mostrar un resumen de errores
4. THE CSV_Importer SHALL soportar archivos CSV con codificación UTF-8
5. WHEN se detectan coordenadas fuera del rango de Castilla y León, THE CSV_Importer SHALL marcar esas filas como inválidas

### Requisito 3: Inserción en Base de Datos

**User Story:** Como administrador del sistema, quiero que las incidencias válidas del CSV se inserten en la base de datos, para mantener la integridad y consistencia de los datos.

#### Criterios de Aceptación

1. WHEN se procesan incidencias válidas del CSV, THE Database_Manager SHALL insertarlas en la tabla incidencias usando transacciones
2. IF ocurre un error durante la inserción, THEN THE Database_Manager SHALL hacer rollback de toda la operación de importación
3. WHEN se completa la inserción exitosamente, THE Database_Manager SHALL retornar el número de incidencias importadas
4. THE Database_Manager SHALL asignar automáticamente IDs únicos a las nuevas incidencias
5. WHEN se detectan incidencias duplicadas basadas en coordenadas y fecha, THE Database_Manager SHALL omitir los duplicados

### Requisito 4: Actualización Automática de la Interfaz

**User Story:** Como usuario del sistema, quiero que el mapa y gráficos se actualicen automáticamente después de importar el CSV, para ver inmediatamente las nuevas incidencias sin recargar la página.

#### Criterios de Aceptación

1. WHEN se completa exitosamente la importación de CSV, THE UI_Updater SHALL actualizar el mapa de Leaflet.js con las nuevas incidencias
2. WHEN se añaden nuevas incidencias, THE UI_Updater SHALL actualizar los gráficos de Chart.js con los nuevos datos estadísticos
3. THE UI_Updater SHALL mostrar una notificación de éxito con el número de incidencias importadas
4. WHEN la importación falla, THE UI_Updater SHALL mostrar un mensaje de error detallado
5. THE UI_Updater SHALL mantener el estado actual del mapa (zoom, centro) durante la actualización

### Requisito 5: Integración con Sistema Existente

**User Story:** Como desarrollador del sistema, quiero que la nueva funcionalidad se integre sin modificar el código existente, para mantener la estabilidad del sistema actual.

#### Criterios de Aceptación

1. THE Sistema SHALL mantener toda la funcionalidad existente sin modificaciones
2. THE CSV_Importer SHALL utilizar la API REST existente para obtener y actualizar incidencias
3. THE Database_Manager SHALL usar las mismas tablas y estructura de base de datos existente
4. THE UI_Updater SHALL reutilizar los componentes existentes de Leaflet.js y Chart.js
5. THE Sistema SHALL mantener el sistema de caché existente para Green Coding

### Requisito 6: Manejo de Errores y Retroalimentación

**User Story:** Como usuario del sistema, quiero recibir retroalimentación clara sobre el proceso de importación, para entender qué incidencias se importaron exitosamente y cuáles tuvieron errores.

#### Criterios de Aceptación

1. WHEN se completa el procesamiento del CSV, THE Sistema SHALL mostrar un resumen con incidencias exitosas, errores y duplicados omitidos
2. WHEN ocurren errores de validación, THE Sistema SHALL mostrar detalles específicos de qué filas y campos tienen problemas
3. THE Sistema SHALL proporcionar un log descargable de errores para archivos con muchos problemas
4. WHEN la importación está en progreso, THE Sistema SHALL mostrar un indicador de progreso
5. THE Sistema SHALL permitir al usuario cancelar la importación en progreso

### Requisito 7: Formato y Estructura del CSV

**User Story:** Como usuario del sistema, quiero conocer el formato exacto requerido para el archivo CSV, para preparar correctamente mis datos de importación.

#### Criterios de Aceptación

1. THE Sistema SHALL proporcionar un archivo CSV de ejemplo descargable con el formato correcto
2. THE Sistema SHALL mostrar documentación clara sobre los campos requeridos y sus formatos
3. WHEN se detecta un formato de fecha inválido, THE CSV_Importer SHALL intentar parsear formatos comunes (DD/MM/YYYY, YYYY-MM-DD)
4. THE CSV_Importer SHALL aceptar valores de estado predefinidos: "abierta", "en_proceso", "cerrada"
5. THE CSV_Importer SHALL validar que las coordenadas estén en formato decimal (ej: 41.6518, -4.7245)