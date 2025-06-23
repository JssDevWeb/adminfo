<?php
/**
 * Script de prueba para verificar la generación de reportes PDF
 */

// Mostrar todos los errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configuración de base de datos
require_once '../config/database.php';

// Incluir la clase de generación de PDF
require_once 'pdf/ReportePdfGenerator.php';

echo "<h1>Prueba de Generación de Reporte PDF</h1>";

try {
    // Crear instancia del generador de PDF
    $reporteGenerator = new ReportePdfGenerator();
    
    // Parámetros de prueba
    $curso_id = 5; // Usar un ID de curso válido
    $fecha = date('Y-m-d'); // Fecha actual
    $secciones = ['resumen_completo', 'graficos_evaluacion', 'estadisticas_detalladas'];
    
    echo "<p>Intentando generar un reporte PDF para:<br>";
    echo "- Curso ID: $curso_id<br>";
    echo "- Fecha: $fecha<br>";
    echo "- Secciones: " . implode(', ', $secciones) . "</p>";
    
    // Generar el reporte PDF
    $resultado = $reporteGenerator->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
    
    if ($resultado) {
        echo "<div style='padding:15px;background:#d4edda;color:#155724;margin:20px 0;border-radius:4px'>";
        echo "<h3>¡Éxito!</h3>";
        echo "<p>El reporte PDF se ha generado correctamente. Puede comprobar el archivo en la ubicación definida en la clase ReportePdfGenerator.</p>";
        echo "</div>";
    } else {
        echo "<div style='padding:15px;background:#f8d7da;color:#721c24;margin:20px 0;border-radius:4px'>";
        echo "<h3>Error</h3>";
        echo "<p>No se pudo generar el reporte PDF.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='padding:15px;background:#f8d7da;color:#721c24;margin:20px 0;border-radius:4px'>";
    echo "<h3>Error</h3>";
    echo "<p>Se produjo una excepción: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Archivo: " . htmlspecialchars($e->getFile()) . " (Línea: " . $e->getLine() . ")</p>";
    echo "</div>";
}

echo "<p><a href='diagnostico_pdf_binary.php' style='padding:10px;background:#007bff;color:white;text-decoration:none;border-radius:4px'>Ejecutar diagnóstico de PDF</a></p>";
?>
