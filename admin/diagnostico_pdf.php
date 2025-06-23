<?php
/**
 * Prueba específica para generar PDF y validar su contenido
 */

// Limpiar buffer de salida
if (ob_get_level()) {
    ob_clean();
}

require_once 'pdf/ReportePdfGenerator.php';

try {
    echo "<h2>🔍 Diagnóstico de PDF</h2>";
    
    // Crear instancia del generador
    $generator = new ReportePdfGenerator();
    echo "<p>✅ Generador creado correctamente</p>";
      // Generar PDF de prueba con datos reales
    $curso_id = 3; // Física General - que tiene datos
    $fecha = '2025-06-20'; // Fecha con datos reales
    $secciones = ['resumen_ejecutivo', 'distribucion_respuestas'];
    
    echo "<p>📊 Generando PDF con:</p>";
    echo "<ul>";
    echo "<li>Curso ID: $curso_id</li>";
    echo "<li>Fecha: $fecha</li>";
    echo "<li>Secciones: " . implode(', ', $secciones) . "</li>";
    echo "</ul>";
    
    $pdfContent = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
    
    echo "<p>✅ PDF generado exitosamente</p>";
    echo "<p>📏 Tamaño del PDF: " . strlen($pdfContent) . " bytes</p>";
    
    // Validar que el contenido sea realmente un PDF
    if (substr($pdfContent, 0, 4) === '%PDF') {
        echo "<p>✅ El contenido tiene el header correcto de PDF</p>";
    } else {
        echo "<p>❌ El contenido NO tiene el header correcto de PDF</p>";
        echo "<p>Primeros 50 caracteres: " . htmlspecialchars(substr($pdfContent, 0, 50)) . "</p>";
    }
    
    // Validar que termine correctamente
    if (substr($pdfContent, -5) === '%%EOF') {
        echo "<p>✅ El PDF termina correctamente</p>";
    } else {
        echo "<p>❌ El PDF NO termina correctamente</p>";
        echo "<p>Últimos 50 caracteres: " . htmlspecialchars(substr($pdfContent, -50)) . "</p>";
    }
    
    // Crear botón para descargar el PDF de prueba
    echo "<div style='margin-top: 20px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc;'>";
    echo "<h3>💾 Descargar PDF de prueba</h3>";
    echo "<form method='post' action='?download=1'>";
    echo "<input type='hidden' name='pdf_content' value='" . base64_encode($pdfContent) . "'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;'>";
    echo "Descargar PDF de prueba";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>📍 Archivo: " . $e->getFile() . "</p>";
    echo "<p>📍 Línea: " . $e->getLine() . "</p>";
    echo "<pre>Traza:\n" . $e->getTraceAsString() . "</pre>";
}

// Manejar descarga del PDF de prueba
if (isset($_GET['download']) && isset($_POST['pdf_content'])) {
    $pdfContent = base64_decode($_POST['pdf_content']);
    
    // Limpiar buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="prueba_pdf_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    echo $pdfContent;
    exit();
}
?>
