<?php
/**
 * Procesador de PDF
 * Recibe los datos del formulario y genera el PDF final
 */

// Limpiar cualquier output buffer previo
if (ob_get_level()) {
    ob_clean();
}

require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

// Definir constante DEBUG si no existe
if (!defined('DEBUG')) {
    define('DEBUG', false);
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reportes.php');
    exit();
}

// Obtener parámetros
$curso_id = $_POST['curso_id'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$secciones = $_POST['secciones'] ?? [];
$formato = $_POST['formato'] ?? 'A4';
$orientacion = $_POST['orientacion'] ?? 'portrait';

// DEBUG: Log de parámetros recibidos
error_log("=== PDF DEBUG ===");
error_log("Curso ID: " . $curso_id);
error_log("Fecha: " . $fecha);
error_log("Secciones: " . print_r($secciones, true));
error_log("Formato: " . $formato);
error_log("Orientación: " . $orientacion);
error_log("POST completo: " . print_r($_POST, true));
error_log("=================");

// Decodificar imágenes de gráficos si están presentes
$imagenes_graficos = [];
if (!empty($_POST['imagenes_graficos'])) {
    $imagenes_json = $_POST['imagenes_graficos'];
    $imagenes_decoded = json_decode($imagenes_json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($imagenes_decoded)) {
        $imagenes_graficos = $imagenes_decoded;
    }
}

// Validar parámetros
if (empty($curso_id) || empty($fecha) || empty($secciones)) {
    $_SESSION['error'] = 'Parámetros incompletos para generar el PDF.';
    header('Location: reportes.php');
    exit();
}

try {
    // Crear generador 
    $generator = new ReportePdfGenerator();
    
    // Configurar formato si es diferente de A4
    if ($formato !== 'A4' || $orientacion !== 'portrait') {
        // Esta funcionalidad se implementará en la clase si es necesario
        // Por ahora usamos configuración por defecto
    }      // Generar PDF con los parámetros del curso y fecha
    $pdfContent = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, $imagenes_graficos);
    
    // Obtener nombre del curso para el archivo
    require_once '../config/database.php';
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch();
    $curso_nombre = $curso ? $curso['nombre'] : 'Curso';
    
    // Limpiar nombre del curso para el archivo
    $curso_filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $curso_nombre);
    $filename = 'reporte_' . $curso_filename . '_' . $fecha . '_' . date('H-i-s') . '.pdf';
      // Log de la exportación (opcional)
    error_log("PDF generado: $filename - Curso: $curso_id - Fecha: $fecha - Secciones: " . implode(',', $secciones));
    
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Enviar PDF al navegador
    echo $pdfContent;
    exit(); // Importante: terminar la ejecución aquí
    
} catch (Exception $e) {
    // Log del error
    error_log("Error generando PDF: " . $e->getMessage());
    
    // Mostrar página de error
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error al generar PDF</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-exclamation-triangle"></i>
                                Error al generar PDF
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                            </div>
                            
                            <h6>Posibles causas:</h6>
                            <ul>
                                <li>No hay datos disponibles para la fecha y curso seleccionados</li>
                                <li>Error en la conexión a la base de datos</li>
                                <li>Problema con la librería TCPDF</li>
                                <li>Permisos insuficientes en el servidor</li>
                            </ul>

                            <div class="mt-4">
                                <a href="exportar_pdf.php?curso_id=<?php echo urlencode($curso_id); ?>&fecha=<?php echo urlencode($fecha); ?>" 
                                   class="btn btn-primary">
                                    <i class="bi bi-arrow-left"></i> 
                                    Intentar de nuevo
                                </a>
                                <a href="reportes.php" class="btn btn-outline-secondary ms-2">
                                    <i class="bi bi-house"></i> 
                                    Volver a Reportes
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (DEBUG): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Información de depuración</h6>
                        </div>
                        <div class="card-body">
                            <small>
                                <strong>Archivo:</strong> <?php echo $e->getFile(); ?><br>
                                <strong>Línea:</strong> <?php echo $e->getLine(); ?><br>
                                <strong>Traza:</strong><br>
                                <pre><?php echo $e->getTraceAsString(); ?></pre>
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
