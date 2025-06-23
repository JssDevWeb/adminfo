<?php
echo "=== TEST DE VERIFICACIÓN DE CORRECCIONES ===\n";
echo "Verificando que las correcciones de valor_texts → respuestas funcionen...\n\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    $generator = new ReportePdfGenerator();
    $reflection = new ReflectionClass($generator);
    
    echo "1. Probando obtenerEstadisticasGenerales()...\n";
    $method = $reflection->getMethod('obtenerEstadisticasGenerales');
    $method->setAccessible(true);
    $stats = $method->invoke($generator);
    
    echo "   Resultados:\n";
    foreach ($stats as $key => $value) {
        echo "   - $key: $value\n";
    }
    
    echo "\n2. Probando obtenerEstadisticasRealesParaTabla()...\n";
    $method2 = $reflection->getMethod('obtenerEstadisticasRealesParaTabla');
    $method2->setAccessible(true);
    $stats_tabla = $method2->invoke($generator, 2, '2024-12-15'); // Usar curso Física General
    
    echo "   Estadísticas encontradas: " . count($stats_tabla) . "\n";
    if (!empty($stats_tabla)) {
        foreach ($stats_tabla as $stat) {
            echo "   - {$stat['tipo']}: {$stat['nombre']}\n";
        }
    }
    
    echo "\n3. Generando PDF de prueba...\n";
    $pdf_content = $generator->generarReportePorCursoFecha(2, '2024-12-15', ['resumen_ejecutivo', 'estadisticas_detalladas'], []);
    
    $archivo_test = __DIR__ . '/pdf/test_correcciones.pdf';
    file_put_contents($archivo_test, $pdf_content);
    
    $tamaño = filesize($archivo_test);
    echo "   ✓ PDF generado: $tamaño bytes\n";
    echo "   ✓ Guardado en: $archivo_test\n";
    
    if ($tamaño > 5000) {
        echo "\n✅ LAS CORRECCIONES PARECEN FUNCIONAR CORRECTAMENTE\n";
        echo "El PDF tiene un tamaño razonable, lo que indica que contiene datos.\n";
    } else {
        echo "\n⚠️ El PDF es muy pequeño, puede que aún falten correcciones.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>
