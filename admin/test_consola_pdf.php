<?php
/**
 * Script de prueba para verificar la generación de reportes PDF
 * Versión consola para diagnóstico
 */

// Mostrar todos los errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== PRUEBA DE GENERACIÓN DE REPORTE PDF ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Incluir configuración y clases
require_once '../config/database.php';
require_once 'pdf/ReportePdfGenerator.php';

try {
    echo "1. Estableciendo conexión a la base de datos...\n";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Curso para prueba
    $curso_id = 5; // Usar un ID de curso válido
    $fecha = date('Y-m-d'); // Fecha actual
    
    echo "2. Creando instancia del generador de PDF...\n";
    $reporteGenerator = new ReportePdfGenerator($pdo);
    
    echo "3. Generando reporte para Curso ID: $curso_id, Fecha: $fecha\n";
    
    // Opciones de secciones para el reporte
    $secciones = ['resumen_completo', 'graficos_evaluacion', 'estadisticas_detalladas'];
    echo "   Secciones: " . implode(', ', $secciones) . "\n";
    
    // Intentar generar el reporte
    echo "4. Iniciando generación del PDF...\n";
    $resultadoPdf = $reporteGenerator->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
    
    if ($resultadoPdf) {
        echo "5. ✅ ÉXITO: El reporte PDF se ha generado correctamente.\n";
        
        // Guardar el PDF para prueba
        $ruta_archivo = 'test_generado_' . date('Ymd_His') . '.pdf';
        file_put_contents($ruta_archivo, $resultadoPdf);
        echo "6. 📄 PDF guardado en: $ruta_archivo\n";
    } else {
        echo "5. ❌ ERROR: No se recibió contenido del PDF.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "En archivo: " . $e->getFile() . " (línea: " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
