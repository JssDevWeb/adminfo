<?php
/**
 * Test de las mejoras visuales del PDF
 * 
 * Este script genera un PDF con las mejoras visuales implementadas:
 * - Mejor uso del espacio en la primera página
 * - Resumen ejecutivo integrado con tabla de aprovechamiento
 * - Gráficos con mejor disposición visual
 */

require_once 'pdf/ReportePdfGenerator.php';
require_once '../config/database.php';

try {
    // Obtener la conexión a la base de datos
    $db = Database::getInstance()->getConnection();
    
    // Crear el generador de PDF con la conexión
    $generator = new ReportePdfGenerator($db);
    
    // Parámetros de prueba - usar curso y fecha con datos
    $curso_id = 5; // Usar el ID del curso que tiene datos
    $fecha = '2025-06-23'; // Usar la fecha actual o una con datos
    
    echo "<h1>Test de Generación de PDF con Mejoras Visuales</h1>";
    
    echo "<p><strong>Curso ID:</strong> {$curso_id}<br>";
    echo "<strong>Fecha:</strong> {$fecha}</p>";
    
    echo "<h2>Generando PDF con nuevo diseño visual...</h2>";
    
    // Definir las secciones a incluir
    $secciones = [
        'resumen_ejecutivo',     // Ahora incluye la tabla de aprovechamiento
        'graficos_evaluacion',   // Mejorada la disposición de gráficos
        'estadisticas_detalladas',
        'comentarios_curso',
        'preguntas_criticas'
    ];
    
    // Generar el PDF con las secciones especificadas
    $pdfContent = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
    
    // Guardar el PDF generado
    $filename = "reporte_visual_mejorado_" . date('Ymd_His') . ".pdf";
    file_put_contents($filename, $pdfContent);
    
    // Mostrar información sobre el archivo generado
    if (file_exists($filename)) {
        $filesize = filesize($filename);
        echo "<div style='padding: 15px; background-color: #d4edda; border-radius: 5px; margin-top: 20px;'>";
        echo "<h3 style='color: #155724;'>✅ PDF generado correctamente</h3>";
        echo "<p><strong>Archivo:</strong> {$filename}<br>";
        echo "<strong>Tamaño:</strong> " . round($filesize / 1024, 2) . " KB</p>";
        
        echo "<p><a href='{$filename}' download style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>Descargar PDF</a></p>";
        
        echo "<p>El PDF generado implementa las siguientes mejoras visuales:</p>";
        echo "<ul>";
        echo "<li>Mejor aprovechamiento del espacio en la primera página</li>";
        echo "<li>Integración del resumen ejecutivo con la tabla de aprovechamiento</li>";
        echo "<li>Diseño más compacto del encabezado principal</li>";
        echo "<li>Distribución optimizada de gráficos</li>";
        echo "<li>Tablas con estilo visual moderno similar a la web</li>";
        echo "</ul>";
        echo "</div>";
        
        // Mostrar el PDF incrustado
        echo "<h3>Previsualización del PDF:</h3>";
        echo "<iframe src='{$filename}' width='100%' height='600px' style='border: 1px solid #ddd;'></iframe>";
    } else {
        echo "<p style='color: red;'>Error: No se pudo generar el archivo PDF.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 15px; background-color: #f8d7da; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #721c24;'>❌ Error al generar el PDF</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . " (línea " . $e->getLine() . ")</p>";
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 4px;'>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
