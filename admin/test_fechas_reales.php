<?php
echo "=== TEST CON FECHAS REALES ===\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    $generator = new ReportePdfGenerator();
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('obtenerEstadisticasRealesParaTabla');
    $method->setAccessible(true);
    
    echo "Probando con fechas reales de los datos:\n";
    
    // Usar fechas reales
    $tests = [
        ['curso_id' => 2, 'fecha' => '2025-06-20'], // Física General
        ['curso_id' => 3, 'fecha' => '2025-06-20'], // Estadística Aplicada  
        ['curso_id' => 4, 'fecha' => '2025-06-23']  // Historia Contemporánea
    ];
    
    foreach ($tests as $test) {
        echo "\n→ Curso {$test['curso_id']}, Fecha {$test['fecha']}:\n";
        
        $stats = $method->invoke($generator, $test['curso_id'], $test['fecha']);
        echo "  Resultados: " . count($stats) . " estadísticas\n";
        
        if (!empty($stats)) {
            foreach ($stats as $stat) {
                echo "  - {$stat['tipo']}: {$stat['nombre']} (E:{$stat['encuestas']}, P:{$stat['preguntas']}, Prom:" . number_format($stat['puntuacion'], 2) . ")\n";
            }
        }
    }
    
    echo "\nProbando PDF con fecha real:\n";
    $pdf_content = $generator->generarReportePorCursoFecha(2, '2025-06-20', ['resumen_ejecutivo', 'estadisticas_detalladas'], []);
    
    $archivo_real = __DIR__ . '/pdf/test_fecha_real.pdf';
    file_put_contents($archivo_real, $pdf_content);
    
    $tamaño = filesize($archivo_real);
    echo "✓ PDF generado: $tamaño bytes\n";
    echo "✓ Guardado en: $archivo_real\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>
