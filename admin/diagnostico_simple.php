<?php
/**
 * Diagnóstico simple de PDF
 */

// Limpiar buffer de salida
if (ob_get_level()) {
    ob_clean();
}

echo "<h2>🔍 Diagnóstico Simple de PDF</h2>";

// Paso 1: Verificar autoload y dependencias
try {
    require_once 'pdf/ReportePdfGenerator.php';
    echo "<p>✅ ReportePdfGenerator incluido correctamente</p>";
} catch (Exception $e) {
    echo "<p>❌ Error incluyendo ReportePdfGenerator: " . $e->getMessage() . "</p>";
    exit;
}

// Paso 2: Verificar instanciación
try {
    $generator = new ReportePdfGenerator();
    echo "<p>✅ ReportePdfGenerator instanciado correctamente</p>";
} catch (Exception $e) {
    echo "<p>❌ Error instanciando ReportePdfGenerator: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    exit;
}

// Paso 3: Verificar conexión a base de datos
try {
    require_once '../config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Conexión a base de datos OK</p>";
    
    // Verificar que existe el curso
    $stmt = $db->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => 3]);
    $curso = $stmt->fetch();
    
    if ($curso) {
        echo "<p>✅ Curso encontrado: " . htmlspecialchars($curso['nombre']) . "</p>";
    } else {
        echo "<p>❌ Curso no encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error con base de datos: " . $e->getMessage() . "</p>";
    exit;
}

// Paso 4: Probar generación básica de PDF
try {
    echo "<p>🔄 Intentando generar PDF básico...</p>";
    
    // Crear un PDF mínimo usando TCPDF directamente
    require_once '../vendor/autoload.php';
    
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'PRUEBA DE PDF', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Este es un PDF de prueba generado el ' . date('d/m/Y H:i:s'), 0, 1, 'L');
    
    $pdfContent = $pdf->Output('', 'S');
    
    echo "<p>✅ PDF básico generado exitosamente</p>";
    echo "<p>📏 Tamaño: " . strlen($pdfContent) . " bytes</p>";
    
    // Validar header
    if (substr($pdfContent, 0, 4) === '%PDF') {
        echo "<p>✅ Header de PDF correcto</p>";
    } else {
        echo "<p>❌ Header de PDF incorrecto</p>";
    }
    
    // Crear formulario para descargar
    echo "<form method='post' action='?download_simple=1'>";
    echo "<input type='hidden' name='pdf_content' value='" . base64_encode($pdfContent) . "'>";
    echo "<button type='submit'>Descargar PDF de Prueba</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p>❌ Error generando PDF básico: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}

// Manejar descarga
if (isset($_GET['download_simple']) && isset($_POST['pdf_content'])) {
    $pdfContent = base64_decode($_POST['pdf_content']);
    
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="prueba_simple_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    echo $pdfContent;
    exit();
}
?>
