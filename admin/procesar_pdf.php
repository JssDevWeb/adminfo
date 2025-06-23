<?php
/**
 * Procesador de PDF
 * Recibe los datos del formulario y genera el PDF final
 */

// Limpiar cualquier output buffer previo
if (ob_get_level()) {
    ob_end_clean();
}
// Iniciar un nuevo buffer limpio
ob_start();

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
    // Asegurarnos de tener una conexión a la base de datos
    require_once '../config/database.php';
    $db = Database::getInstance()->getConnection();
    
    // Crear generador pasando la conexión explícitamente
    $generator = new ReportePdfGenerator($db);
    
    // Configurar formato si es diferente de A4
    if ($formato !== 'A4' || $orientacion !== 'portrait') {
        // Esta funcionalidad se implementará en la clase si es necesario
        // Por ahora usamos configuración por defecto
    }
    
    // Generar PDF con los parámetros del curso y fecha
    $pdfContent = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, $imagenes_graficos);
    
    // Obtener nombre del curso para el archivo
    $stmt = $db->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch();
    $curso_nombre = $curso ? $curso['nombre'] : 'Curso';
    
    // Limpiar nombre del curso para el archivo
    $curso_filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $curso_nombre);
    $filename = 'reporte_' . $curso_filename . '_' . $fecha . '_' . date('H-i-s') . '.pdf';
    
    // Log de la exportación (opcional)
    error_log("PDF generado: $filename - Curso: $curso_id - Fecha: $fecha - Secciones: " . implode(',', $secciones));
    
    // Limpiar cualquier salida previa y finalizar buffer
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Fecha en el pasado
    
    // Enviar PDF al navegador y finalizar
    echo $pdfContent;
    exit();

} catch (Exception $e) {
    // Limpiar el buffer
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    // Mostrar mensaje de error y redirigir
    $_SESSION['error'] = 'Error al generar el PDF: ' . $e->getMessage();
    error_log("Error generando PDF: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
    
    header('Location: reportes.php');
    exit();
}
?>
