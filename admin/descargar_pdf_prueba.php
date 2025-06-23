<?php
/**
 * Script para descargar el PDF de prueba
 */
session_start();

if (!isset($_SESSION['pdf_prueba'])) {
    die('No hay PDF de prueba disponible');
}

$pdfContent = base64_decode($_SESSION['pdf_prueba']);
unset($_SESSION['pdf_prueba']); // Limpiar después de usar

// Headers para descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="prueba_pdf_imagenes_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfContent;
exit;
?>
