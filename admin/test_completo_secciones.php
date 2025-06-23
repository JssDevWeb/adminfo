<?php
echo "=== TEST COMPLETO DE TODAS LAS SECCIONES ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    $generator = new ReportePdfGenerator();
    echo "✓ ReportePdfGenerator instanciado correctamente\n\n";
    
    // Test de cada sección individual
    $secciones_test = [
        'estadisticas' => 'Estadísticas generales',
        'graficos' => 'Gráficos y visualizaciones', 
        'comentarios' => 'Comentarios y sugerencias',
        'resumen_general' => 'Resumen general (alias de estadísticas)',
        'seccion_inexistente' => 'Sección que no existe (test de error)'
    ];
    
    foreach ($secciones_test as $seccion => $descripcion) {
        echo "Probando sección '$seccion' ($descripcion)...\n";
        
        try {
            $pdf_content = $generator->generarReporte([$seccion]);
            $tamaño = strlen($pdf_content);
            
            $archivo = __DIR__ . "/pdf/test_seccion_{$seccion}.pdf";
            file_put_contents($archivo, $pdf_content);
            
            echo "   ✓ Generado: $tamaño bytes -> $archivo\n";
            
        } catch (Exception $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
        
        // Crear nueva instancia para evitar conflictos
        $generator = new ReportePdfGenerator();
    }
    
    echo "\n";
      // Test de múltiples secciones combinadas
    echo "Probando combinación de múltiples secciones...\n";
    
    $combinaciones = [
        'solo_estadisticas' => ['estadisticas'],
        'estadisticas_comentarios' => ['estadisticas', 'comentarios'],
        'todas_principales' => ['estadisticas', 'graficos', 'comentarios'],
        'vacio' => []
    ];
    
    $descripciones = [
        'solo_estadisticas' => 'Solo estadísticas',
        'estadisticas_comentarios' => 'Estadísticas + Comentarios', 
        'todas_principales' => 'Todas las secciones principales',
        'vacio' => 'PDF vacío (sin secciones)'
    ];
    
    foreach ($combinaciones as $nombre => $secciones) {
        $descripcion = $descripciones[$nombre];
        echo "  → $descripcion...\n";
        
        try {
            $generator = new ReportePdfGenerator();
            $pdf_content = $generator->generarReporte($secciones);
            $tamaño = strlen($pdf_content);
            
            $archivo = __DIR__ . "/pdf/test_combinacion_{$nombre}.pdf";
            file_put_contents($archivo, $pdf_content);
            
            echo "     ✓ Generado: $tamaño bytes -> $archivo\n";
            
        } catch (Exception $e) {
            echo "     ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Test de datos reales
    echo "Verificando datos reales en la base de datos...\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Obtener estadísticas de la BD
    $queries = [
        'Total encuestas' => "SELECT COUNT(*) as total FROM encuestas",
        'Total respuestas' => "SELECT COUNT(*) as total FROM respuestas", 
        'Total profesores' => "SELECT COUNT(*) as total FROM profesores",
        'Total cursos' => "SELECT COUNT(*) as total FROM cursos",
        'Respuestas con texto' => "SELECT COUNT(*) as total FROM respuestas WHERE valor_text IS NOT NULL AND valor_text != ''"
    ];
    
    foreach ($queries as $descripcion => $query) {
        try {
            $stmt = $pdo->query($query);
            $resultado = $stmt->fetch()['total'];
            echo "   - $descripcion: $resultado\n";
        } catch (Exception $e) {
            echo "   - $descripcion: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Verificar archivos generados
    echo "=== RESUMEN DE ARCHIVOS GENERADOS ===\n";
    
    $directorio_pdf = __DIR__ . '/pdf/';
    $archivos = glob($directorio_pdf . 'test_*.pdf');
    
    if (empty($archivos)) {
        echo "✗ No se encontraron archivos PDF de test\n";
    } else {
        echo "Se generaron " . count($archivos) . " archivos de prueba:\n";
        
        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            $tamaño = filesize($archivo);
            $estado = $tamaño > 0 ? "✓" : "✗";
            echo "  $estado $nombre: $tamaño bytes\n";
        }
    }
    
    echo "\n";
    echo "=== INSTRUCCIONES PARA VERIFICACIÓN MANUAL ===\n";
    echo "1. Abre los archivos PDF generados con un visor de PDF\n";
    echo "2. Verifica que el contenido sea legible y bien formateado\n";
    echo "3. Confirma que las secciones solicitadas aparezcan\n";
    echo "4. Revisa que los datos sean reales de la base de datos\n";
    echo "5. Verifica que las tablas y estilos se muestren correctamente\n";
    
    echo "\nArchivos principales para revisar:\n";
    echo "- test_combinacion_estadisticas_graficos_comentarios.pdf (Reporte completo)\n";
    echo "- test_seccion_estadisticas.pdf (Solo estadísticas)\n";
    echo "- test_seccion_comentarios.pdf (Solo comentarios)\n";
    echo "- test_combinacion_vacio.pdf (PDF básico sin secciones)\n";
    
} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEL TEST COMPLETO ===\n";
?>
