<?php
/**
 * Script simple para probar la clase ReportePdfGenerator con las mejoras
 */

// Configurar para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

// Crear una instancia del generador de PDF
try {
    $generator = new ReportePdfGenerator();
    
    // Seleccionar un curso y fecha (reemplazar con ID y fecha reales de la base de datos)
    $curso_id = 1;  // ID de un curso que tenga datos
    $fecha = '2024-06-20';  // Una fecha que tenga encuestas
    
    // Generar el PDF
    $secciones = [
        'resumen_ejecutivo',
        'graficos_evaluacion', 
        'estadisticas_detalladas',
        'preguntas_criticas',
        'comentarios_curso'
    ];
    
    $pdf_content = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
    
    // Guardar el PDF en un archivo
    $output_file = 'test_mejoras_graficos_' . date('Y-m-d_H-i-s') . '.pdf';
    file_put_contents($output_file, $pdf_content);
    
    echo "PDF generado con éxito: $output_file\n";
    echo "Tamaño: " . round(filesize($output_file) / 1024, 2) . " KB\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Traza:\n" . $e->getTraceAsString() . "\n";
}
