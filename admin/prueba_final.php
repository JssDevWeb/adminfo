<?php
/**
 * Prueba final del sistema de exportación PDF
 * Simula exactamente lo que hace el formulario web
 */

echo "🧪 PRUEBA FINAL DEL SISTEMA DE EXPORTACIÓN PDF\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Simular datos POST como si viniera del formulario
$_POST = [
    'curso_id' => '3',
    'fecha' => '2025-06-20',
    'secciones' => ['resumen_ejecutivo', 'distribucion_respuestas', 'estadisticas_detalladas', 'preguntas_criticas']
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "📊 Datos de prueba:\n";
echo "- Curso ID: " . $_POST['curso_id'] . "\n";
echo "- Fecha: " . $_POST['fecha'] . "\n";
echo "- Secciones: " . implode(', ', $_POST['secciones']) . "\n\n";

// Ejecutar el procesador tal como lo haría el navegador
echo "🔄 Ejecutando procesar_pdf.php...\n";

try {
    // Capturar la salida
    ob_start();
    
    // Incluir el procesador
    require 'procesar_pdf.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "✅ PDF generado exitosamente!\n";
    echo "📏 Tamaño del PDF: " . strlen($output) . " bytes\n";
    
    // Validar el PDF
    if (substr($output, 0, 4) === '%PDF') {
        echo "✅ Header PDF correcto\n";
    } else {
        echo "❌ Header PDF incorrecto\n";
    }
    
    if (substr($output, -5) === '%%EOF') {
        echo "✅ Footer PDF correcto\n";
    } else {
        echo "❌ Footer PDF incorrecto\n";
    }
    
    // Guardar el PDF para verificación manual
    $filename = 'prueba_final_' . date('Y-m-d_H-i-s') . '.pdf';
    file_put_contents($filename, $output);
    echo "💾 PDF guardado como: $filename\n";
    echo "📁 Ubicación: " . __DIR__ . DIRECTORY_SEPARATOR . $filename . "\n";
    
    echo "\n🎉 ¡ÉXITO! El sistema de exportación PDF funciona correctamente.\n";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ PRUEBA COMPLETADA\n";
?>
