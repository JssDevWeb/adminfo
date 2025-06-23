<?php
/**
 * Test de generación de PDF en línea de comandos
 * 
 * Este script debe ejecutarse desde línea de comandos con:
 * php test_pdf_cli.php
 */

// Verificar que se está ejecutando desde CLI
if (php_sapi_name() !== 'cli') {
    echo "Este script debe ejecutarse desde línea de comandos.\n";
    exit(1);
}

// Requerir autoload y TCPDF directamente
require_once __DIR__ . '/../vendor/autoload.php';

echo "=== TEST DE GENERACIÓN DE PDF EN LÍNEA DE COMANDOS ===\n\n";
echo "Iniciando test de TCPDF...\n";

try {
    echo "Creando instancia de TCPDF...\n";
    $pdf = new TCPDF();
    
    echo "Configurando documento...\n";
    $pdf->SetCreator('PDF CLI Test');
    $pdf->SetAuthor('Sistema Evaluación');
    $pdf->SetTitle('PDF CLI Test');
    
    echo "Añadiendo página...\n";
    $pdf->AddPage();
    
    echo "Añadiendo contenido...\n";
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'PDF generado desde línea de comandos', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Fecha: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    
    echo "Generando PDF...\n";
    $archivo = __DIR__ . '/pdf/test_cli.pdf';
    
    echo "Guardando como string binario...\n";
    $pdf_content = $pdf->Output('', 'S');
    
    echo "Guardando en archivo: $archivo\n";
    $resultado = file_put_contents($archivo, $pdf_content);
    
    if ($resultado === false) {
        throw new Exception("Error al escribir el archivo");
    }
    
    echo "Verificando el archivo...\n";
    if (!file_exists($archivo)) {
        throw new Exception("El archivo no se creó correctamente");
    }
    
    $tamaño = filesize($archivo);
    if ($tamaño < 1000) {
        echo "⚠ ADVERTENCIA: El archivo es muy pequeño ($tamaño bytes)\n";
    } else {
        echo "Tamaño del archivo: $tamaño bytes\n";
    }
    
    // Verificar la firma PDF
    $contenido = file_get_contents($archivo, false, null, 0, 10);
    if (substr($contenido, 0, 4) === '%PDF') {
        echo "✓ El archivo tiene la firma PDF correcta\n";
    } else {
        echo "✗ ERROR: El archivo no tiene la firma PDF correcta\n";
        echo "  Primeros bytes: " . bin2hex(substr($contenido, 0, 10)) . "\n";
    }
    
    echo "\n✅ PROCESO COMPLETADO EXITOSAMENTE\n";
    echo "PDF guardado en: " . realpath($archivo) . "\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "  En: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
    echo "\nTraza del error:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
