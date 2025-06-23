# Funcionalidad de Exportación a PDF

## Descripción
El sistema de encuestas académicas ahora incluye la capacidad de exportar reportes específicos a formato PDF con múltiples opciones de personalización.

## Cómo usar la funcionalidad

### 1. Acceso a la exportación
1. Ir a `admin/reportes.php`
2. Seleccionar un **Curso** específico
3. Seleccionar una **Fecha de Encuesta** específica
4. Hacer clic en **"Generar Reportes Específicos"**
5. Una vez generados los reportes, aparecerá el botón **"PDF"** de color verde

### 2. Configuración del PDF
Al hacer clic en el botón "PDF" se abrirá un modal con las siguientes opciones:

#### Secciones disponibles para exportar:
- **✅ Resumen Ejecutivo** (recomendado)
  - Métricas generales del curso y fecha
  - Distribución de respuestas por categoría
  - Estadísticas básicas de participación

- **✅ Distribución de Respuestas** (recomendado)
  - Análisis detallado por sección de la encuesta
  - Distribución de respuestas por pregunta
  - Gráficos y tablas de distribución

- **✅ Estadísticas Detalladas**
  - Promedios por sección y pregunta
  - Desviaciones estándar
  - Análisis estadístico avanzado

- **✅ Preguntas Críticas** (recomendado)
  - Preguntas con menores calificaciones
  - Identificación de áreas de mejora
  - Análisis de respuestas problemáticas

### 3. Controles del modal
- **Seleccionar/Deseleccionar todas**: Checkbox para marcar/desmarcar todas las secciones
- **Checkboxes individuales**: Para seleccionar secciones específicas
- **Generar y Descargar PDF**: Botón que inicia la generación (se desactiva si no hay secciones seleccionadas)

### 4. Proceso de generación
1. Se validan los parámetros seleccionados
2. Se consulta la base de datos para obtener los datos específicos del curso y fecha
3. Se genera el PDF con las secciones seleccionadas
4. Se descarga automáticamente el archivo PDF

## Características técnicas

### Archivo generado
- **Formato**: PDF optimizado para impresión
- **Nombre**: `reporte_[nombre_curso]_[fecha]_[hora].pdf`
- **Tamaño**: A4 vertical
- **Codificación**: UTF-8 (soporte completo para caracteres especiales)

### Contenido del PDF
- **Encabezado**: Logo institucional, título del reporte, información del curso
- **Secciones**: Según selección del usuario
- **Pie de página**: Fecha de generación, número de página
- **Formato**: Profesional con tablas, gráficos y estadísticas

### Manejo de errores
- Si no hay datos para la fecha/curso: Se muestra página de error explicativa
- Si falla la generación: Se registra en logs y se muestra error amigable
- Si no se seleccionan secciones: Se desactiva el botón de generar

## Archivos involucrados

### Frontend
- `admin/reportes.php` - Página principal con el botón y modal de exportación
- Modal Bootstrap para selección de secciones
- JavaScript para validaciones y experiencia de usuario

### Backend
- `admin/procesar_pdf.php` - Procesador principal que recibe la solicitud
- `admin/pdf/ReportePdfGenerator.php` - Clase que genera el PDF
- Librería TCPDF (instalada vía Composer)

### Base de datos
Se consultan las siguientes tablas:
- `encuestas` - Datos principales de las encuestas
- `respuestas` - Respuestas específicas
- `preguntas` - Texto y configuración de preguntas
- `cursos` - Información de cursos
- `profesores` - Datos de profesores evaluados

## Requisitos técnicos
- PHP 7.4+
- Composer instalado
- Librería TCPDF
- Conexión a base de datos MySQL/MariaDB
- Navegador moderno con soporte para JavaScript

## Log y depuración
- Los errores se registran en los logs de PHP
- En modo DEBUG se muestran trazas detalladas de errores
- Se registra cada exportación exitosa con timestamp

## Próximas mejoras (opcionales)
- [ ] Opción de orientación horizontal/vertical
- [ ] Inclusión de gráficos Chart.js en el PDF
- [ ] Plantillas personalizables por institución
- [ ] Exportación programada/automática
- [ ] Envío por email del PDF generado

---
**Nota**: Esta funcionalidad está completamente integrada y lista para usar en producción.
