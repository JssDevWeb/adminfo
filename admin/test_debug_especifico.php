<?php
echo "=== TEST DE DEPURACIÓN ESPECÍFICA DE ReportePdfGenerator ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    $generator = new ReportePdfGenerator();
    echo "✓ ReportePdfGenerator instanciado\n\n";
    
    // TEST 1: Generar PDF vacío (sin secciones)
    echo "1. Probando PDF vacío (sin secciones)...\n";
    ob_start();
    $pdf_vacio = $generator->generarReporte([]);
    $output_vacio = ob_get_clean();
    
    echo "   - Longitud del contenido: " . strlen($pdf_vacio) . " bytes\n";
    echo "   - Output capturado: " . (empty($output_vacio) ? "vacío" : "tiene contenido") . "\n";
    
    // Guardar para inspección
    $archivo_vacio = __DIR__ . '/pdf/debug_pdf_vacio.pdf';
    file_put_contents($archivo_vacio, $pdf_vacio);
    echo "   - Guardado en: $archivo_vacio\n\n";
    
    // TEST 2: Generar PDF con sección de estadísticas
    echo "2. Probando PDF con sección 'estadisticas'...\n";
    try {
        ob_start();
        $pdf_stats = $generator->generarReporte(['estadisticas']);
        $output_stats = ob_get_clean();
        
        echo "   - Longitud del contenido: " . strlen($pdf_stats) . " bytes\n";
        echo "   - Output capturado: " . (empty($output_stats) ? "vacío" : "tiene contenido") . "\n";
        
        $archivo_stats = __DIR__ . '/pdf/debug_pdf_estadisticas.pdf';
        file_put_contents($archivo_stats, $pdf_stats);
        echo "   - Guardado en: $archivo_stats\n";
        
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // TEST 3: Crear un PDF manual con la misma clase
    echo "3. Creando PDF manual usando los métodos internos...\n";
    
    // Crear una nueva instancia para evitar conflictos
    $generator2 = new ReportePdfGenerator();
    
    // Usar reflexión para acceder a propiedades privadas
    $reflection = new ReflectionClass($generator2);
    $pdf_property = $reflection->getProperty('pdf');
    $pdf_property->setAccessible(true);
    $pdf_interno = $pdf_property->getValue($generator2);
    
    // Añadir contenido manualmente
    $pdf_interno->AddPage();
    $pdf_interno->SetFont('helvetica', 'B', 16);
    $pdf_interno->Cell(0, 10, 'TEST MANUAL CON REPORTEPDFGENERATOR', 0, 1, 'C');
    $pdf_interno->Ln(10);
    
    $pdf_interno->SetFont('helvetica', '', 12);
    $pdf_interno->Cell(0, 10, 'Este PDF fue creado accediendo directamente al objeto TCPDF interno.', 0, 1);
    $pdf_interno->Cell(0, 10, 'Fecha: ' . date('d/m/Y H:i:s'), 0, 1);
    
    $contenido_manual = $pdf_interno->Output('', 'S');
    echo "   - Longitud del contenido manual: " . strlen($contenido_manual) . " bytes\n";
    
    $archivo_manual = __DIR__ . '/pdf/debug_pdf_manual.pdf';
    file_put_contents($archivo_manual, $contenido_manual);
    echo "   - Guardado en: $archivo_manual\n\n";
    
    // TEST 4: Verificar método obtenerEstadisticasGenerales
    echo "4. Probando método obtenerEstadisticasGenerales...\n";
    try {
        $method = $reflection->getMethod('obtenerEstadisticasGenerales');
        $method->setAccessible(true);
        $stats = $method->invoke($generator2);
        
        echo "   ✓ Estadísticas obtenidas:\n";
        foreach ($stats as $key => $value) {
            echo "     - $key: $value\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error obteniendo estadísticas: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // TEST 5: Verificar sección específica
    echo "5. Probando método generarResumenGeneral...\n";
    try {
        $generator3 = new ReportePdfGenerator();
        $reflection3 = new ReflectionClass($generator3);
        $pdf_prop = $reflection3->getProperty('pdf');
        $pdf_prop->setAccessible(true);
        $pdf3 = $pdf_prop->getValue($generator3);
        
        $pdf3->AddPage();
        
        $method = $reflection3->getMethod('generarResumenGeneral');
        $method->setAccessible(true);
        $method->invoke($generator3);
        
        $contenido_resumen = $pdf3->Output('', 'S');
        echo "   - Longitud del resumen: " . strlen($contenido_resumen) . " bytes\n";
        
        $archivo_resumen = __DIR__ . '/pdf/debug_pdf_resumen.pdf';
        file_put_contents($archivo_resumen, $contenido_resumen);
        echo "   - Guardado en: $archivo_resumen\n";
        
    } catch (Exception $e) {
        echo "   ✗ Error en generarResumenGeneral: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== RESUMEN DE ARCHIVOS GENERADOS ===\n";
    $archivos_debug = [
        'debug_pdf_vacio.pdf',
        'debug_pdf_estadisticas.pdf', 
        'debug_pdf_manual.pdf',
        'debug_pdf_resumen.pdf'
    ];
    
    foreach ($archivos_debug as $archivo) {
        $ruta = __DIR__ . '/pdf/' . $archivo;
        if (file_exists($ruta)) {
            $tamaño = filesize($ruta);
            echo "✓ $archivo: $tamaño bytes\n";
        } else {
            echo "✗ $archivo: No generado\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DEL TEST ESPECÍFICO ===\n";
?>
