<?php
/**
 * Test de generación de PDF mínimo
 * Script para crear un PDF mínimo y verificar que TCPDF funciona correctamente
 */

// Evitar cualquier salida antes de generar el PDF
ob_start();

// Requerir autoload y TCPDF directamente para simplificar
require_once __DIR__ . '/../vendor/autoload.php';

// Capturar errores
try {
    // Crear un nuevo objeto PDF
    $pdf = new TCPDF();

    // Establecer información del documento
    $pdf->SetCreator('PDF Test Script');
    $pdf->SetAuthor('Sistema Evaluación');
    $pdf->SetTitle('PDF de Prueba');
    $pdf->SetSubject('Test PDF Simple');
    
    // Desactivar encabezado y pie de página
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Agregar una página
    $pdf->AddPage();
    
    // Establecer fuente
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Título
    $pdf->Cell(0, 10, 'PDF de Prueba', 0, 1, 'C');
    
    // Fecha y hora actual
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Generado el ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    
    // Agregar más contenido
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 10, 'Este es un PDF de prueba para verificar que la generación de PDFs funciona correctamente. Si puedes ver este archivo, significa que TCPDF está configurado correctamente.', 0, 'L');
    
    // Agregar un rectángulo de color
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Rect(20, 80, 170, 40, 'DF');
    
    // Texto dentro del rectángulo
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(25, 90);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Test de visualización correcta', 0, 1);
    $pdf->SetXY(25, 100);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(160, 10, 'Si puedes ver este texto y el rectángulo azul, el PDF se ha generado correctamente.', 0, 'L');

    // Nombre del archivo
    $archivo = __DIR__ . '/pdf/test_minimo.pdf';
    
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Guardar el PDF en un archivo
    $pdf_content = $pdf->Output('', 'S');
    file_put_contents($archivo, $pdf_content);
    
    // Información de éxito
    echo "PDF creado correctamente: " . $archivo . "<br>";
    echo "Tamaño: " . filesize($archivo) . " bytes<br>";
    
    // Proporcionar enlace para descargar y ver el PDF
    echo "<p><a href='pdf/test_minimo.pdf' target='_blank'>Ver PDF generado</a></p>";
    
    // Mostrar iframe para previsualizar el PDF
    echo "<iframe src='pdf/test_minimo.pdf' width='100%' height='500px'></iframe>";

} catch (Exception $e) {
    // Limpiar el buffer
    ob_end_clean();
    
    // Mostrar el error
    echo "<h2>Error al generar el PDF</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>En: " . $e->getFile() . " (línea " . $e->getLine() . ")</p>";
    
    // Mostrar traza para depuración
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
