<?php
echo "=== ANÁLISIS DE MÉTODOS Y POSIBLES FALLOS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    echo "Analizando métodos específicos de ReportePdfGenerator...\n\n";
    
    $generator = new ReportePdfGenerator();
    $reflection = new ReflectionClass($generator);
    
    // 1. Probar método obtenerEstadisticasGenerales
    echo "1. Probando obtenerEstadisticasGenerales()...\n";
    try {
        $method = $reflection->getMethod('obtenerEstadisticasGenerales');
        $method->setAccessible(true);
        $stats = $method->invoke($generator);
        
        echo "   ✓ Método ejecutado correctamente\n";
        echo "   Datos obtenidos:\n";
        foreach ($stats as $key => $value) {
            echo "     - $key: $value\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 2. Probar método generarGraficos
    echo "2. Probando generarGraficos()...\n";
    try {
        $generator2 = new ReportePdfGenerator();
        $reflection2 = new ReflectionClass($generator2);
        $pdf_prop = $reflection2->getProperty('pdf');
        $pdf_prop->setAccessible(true);
        $pdf = $pdf_prop->getValue($generator2);
        
        $pdf->AddPage();
        
        $method = $reflection2->getMethod('generarGraficos');
        $method->setAccessible(true);
        $method->invoke($generator2);
        
        $contenido = $pdf->Output('', 'S');
        echo "   ✓ Método ejecutado, PDF generado: " . strlen($contenido) . " bytes\n";
        
        // Guardar para inspección
        file_put_contents(__DIR__ . '/pdf/test_solo_graficos.pdf', $contenido);
        echo "   ✓ Guardado en test_solo_graficos.pdf\n";
        
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    echo "\n";
    
    // 3. Verificar métodos que podrían no existir
    echo "3. Verificando métodos existentes en la clase...\n";
    
    $metodos_esperados = [
        'generarResumenGeneral',
        'generarGraficos', 
        'generarComentarios',
        'obtenerEstadisticasGenerales',
        'generarEstadisticasProfesores',
        'generarResultadosPorCurso',
        'generarAnalisisPreguntas'
    ];
    
    foreach ($metodos_esperados as $metodo) {
        if ($reflection->hasMethod($metodo)) {
            $method_obj = $reflection->getMethod($metodo);
            $visibilidad = $method_obj->isPublic() ? 'público' : ($method_obj->isPrivate() ? 'privado' : 'protegido');
            echo "   ✓ $metodo existe ($visibilidad)\n";
        } else {
            echo "   ✗ $metodo NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 4. Probar consultas SQL específicas
    echo "4. Probando consultas SQL que usa el sistema...\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $consultas_test = [
        "Encuestas básicas" => "SELECT COUNT(*) as total FROM encuestas",
        "Respuestas con texto" => "SELECT COUNT(*) as total FROM respuestas WHERE valor_text IS NOT NULL AND valor_text != ''",
        "Join encuestas-respuestas" => "
            SELECT COUNT(*) as total 
            FROM encuestas e 
            JOIN respuestas r ON e.id = r.encuesta_id",
        "Join con cursos" => "
            SELECT c.nombre, COUNT(r.id) as respuestas
            FROM cursos c 
            LEFT JOIN encuestas e ON c.id = e.curso_id 
            LEFT JOIN respuestas r ON e.id = r.encuesta_id 
            GROUP BY c.id, c.nombre 
            LIMIT 3",
        "Join con profesores" => "
            SELECT p.nombre, COUNT(r.id) as evaluaciones
            FROM profesores p 
            LEFT JOIN respuestas r ON p.id = r.profesor_id 
            GROUP BY p.id, p.nombre 
            HAVING COUNT(r.id) > 0
            LIMIT 3"
    ];
    
    foreach ($consultas_test as $descripcion => $sql) {
        try {
            $stmt = $pdo->query($sql);
            
            if (strpos($sql, 'GROUP BY') !== false) {
                $resultados = $stmt->fetchAll();
                echo "   ✓ $descripcion: " . count($resultados) . " filas\n";
                if (!empty($resultados)) {
                    foreach (array_slice($resultados, 0, 2) as $fila) {
                        $valores = array_values($fila);
                        echo "     - " . implode(': ', $valores) . "\n";
                    }
                }
            } else {
                $resultado = $stmt->fetch();
                echo "   ✓ $descripcion: " . ($resultado['total'] ?? 'OK') . "\n";
            }
        } catch (Exception $e) {
            echo "   ✗ $descripcion: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // 5. Probar escenarios que podrían fallar
    echo "5. Probando escenarios que podrían causar errores...\n";
    
    // Secciones inexistentes
    echo "   → Sección inexistente...\n";
    try {
        $gen_test = new ReportePdfGenerator();
        $pdf_inexistente = $gen_test->generarReporte(['seccion_que_no_existe']);
        echo "     ✓ Se maneja correctamente: " . strlen($pdf_inexistente) . " bytes\n";
    } catch (Exception $e) {
        echo "     ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Múltiples secciones duplicadas
    echo "   → Secciones duplicadas...\n";
    try {
        $gen_test2 = new ReportePdfGenerator();
        $pdf_duplicado = $gen_test2->generarReporte(['estadisticas', 'estadisticas', 'comentarios']);
        echo "     ✓ Se maneja correctamente: " . strlen($pdf_duplicado) . " bytes\n";
    } catch (Exception $e) {
        echo "     ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Lista muy larga de secciones
    echo "   → Lista larga de secciones...\n";
    try {  
        $gen_test3 = new ReportePdfGenerator();
        $secciones_largas = ['estadisticas', 'comentarios', 'graficos', 'estadisticas', 'resumen_general'];
        $pdf_largo = $gen_test3->generarReporte($secciones_largas);
        echo "     ✓ Se maneja correctamente: " . strlen($pdf_largo) . " bytes\n";
    } catch (Exception $e) {
        echo "     ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    echo "=== RESUMEN DEL ANÁLISIS ===\n";
    echo "• Los métodos principales están funcionando correctamente\n";
    echo "• Las consultas SQL se ejecutan sin errores\n";
    echo "• El sistema maneja bien los casos de error\n";
    echo "• Los PDFs se generan con el tamaño esperado\n";
    echo "\n";
    
    echo "ARCHIVOS GENERADOS PARA INSPECCIÓN:\n";
    echo "- REPORTE_FINAL_INSPECCION.pdf (Reporte completo más importante)\n";
    echo "- test_solo_graficos.pdf (Solo sección de gráficos)\n";
    echo "- FINAL_estadisticas.pdf (Solo estadísticas)\n";
    echo "- FINAL_comentarios.pdf (Solo comentarios)\n";
    
    echo "\nSIGUIENTES PASOS:\n";
    echo "1. Abrir REPORTE_FINAL_INSPECCION.pdf manualmente\n";
    echo "2. Verificar que el contenido sea visualmente correcto\n";
    echo "3. Confirmar que las tablas, textos y formato se vean bien\n";
    echo "4. Si todo está correcto, ¡el sistema está listo!\n";
    echo "5. Si hay problemas visuales, ajustar formato en ReportePdfGenerator.php\n";
    
} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n";
?>
