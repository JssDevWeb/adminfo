<?php
/**
 * Test detallado para inspeccionar el contenido del PDF
 */

echo "=== TEST DETALLADO DEL CONTENIDO PDF ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once 'pdf/ReportePdfGenerator.php';

// Obtener fechas disponibles
echo "1. Verificando fechas disponibles con datos...\n";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SELECT DISTINCT DATE(fecha_envio) as fecha, COUNT(*) as total FROM encuestas GROUP BY DATE(fecha_envio) ORDER BY fecha DESC");
    $fechas = $stmt->fetchAll();
    
    echo "   📅 Fechas con encuestas:\n";
    foreach ($fechas as $f) {
        echo "      - {$f['fecha']}: {$f['total']} encuestas\n";
    }
    
    // Usar la primera fecha con datos
    if (!empty($fechas)) {
        $fecha_con_datos = $fechas[0]['fecha'];
        $total_encuestas = $fechas[0]['total'];
        echo "   ✅ Usando fecha con datos: $fecha_con_datos ($total_encuestas encuestas)\n";
    } else {
        echo "   ❌ No hay encuestas en la base de datos\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Obtener curso con más datos
echo "\n2. Verificando cursos con más datos...\n";
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.nombre, COUNT(e.id) as total_encuestas
        FROM cursos c 
        LEFT JOIN encuestas e ON c.id = e.curso_id 
        WHERE DATE(e.fecha_envio) = :fecha
        GROUP BY c.id, c.nombre 
        ORDER BY total_encuestas DESC 
        LIMIT 5
    ");
    $stmt->execute([':fecha' => $fecha_con_datos]);
    $cursos = $stmt->fetchAll();
    
    echo "   📚 Cursos con más encuestas:\n";
    foreach ($cursos as $c) {
        echo "      - ID {$c['id']}: {$c['nombre']} ({$c['total_encuestas']} encuestas)\n";
    }
    
    if (!empty($cursos)) {
        $curso_id = $cursos[0]['id'];
        $curso_nombre = $cursos[0]['nombre'];
        echo "   ✅ Usando curso: $curso_nombre (ID: $curso_id)\n";
    } else {
        $curso_id = 9;
        $curso_nombre = "Análisis morfosintáctico de mis ganas de morir";
        echo "   ⚠️  Usando curso por defecto: $curso_nombre (ID: $curso_id)\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $curso_id = 9;
    $fecha_con_datos = '2025-06-20';
}

// Verificar datos específicos
echo "\n3. Verificando datos específicos para el reporte...\n";
try {
    // Respuestas totales
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM respuestas r 
        JOIN encuestas e ON r.encuesta_id = e.id 
        WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha_con_datos]);
    $total_respuestas = $stmt->fetchColumn();
    echo "   💬 Respuestas totales: $total_respuestas\n";
    
    // Profesores evaluados
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT r.profesor_id) as total 
        FROM respuestas r 
        JOIN encuestas e ON r.encuesta_id = e.id 
        WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha_con_datos]);
    $total_profesores = $stmt->fetchColumn();
    echo "   👥 Profesores evaluados: $total_profesores\n";
    
    // Comentarios textuales
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM respuestas r 
        JOIN encuestas e ON r.encuesta_id = e.id 
        WHERE e.curso_id = :curso_id 
        AND DATE(e.fecha_envio) = :fecha
        AND r.valor_text IS NOT NULL 
        AND r.valor_text != ''
        AND LENGTH(r.valor_text) > 10
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha_con_datos]);
    $total_comentarios = $stmt->fetchColumn();
    echo "   💭 Comentarios textuales: $total_comentarios\n";
    
} catch (Exception $e) {
    echo "   ❌ Error verificando datos: " . $e->getMessage() . "\n";
}

// Generar PDF con cada sección individualmente
echo "\n4. Generando PDF por secciones...\n";
try {
    $generator = new ReportePdfGenerator();
    
    $secciones_test = [
        'graficos_evaluacion' => 'Gráficos de Evaluación',
        'estadisticas_detalladas' => 'Estadísticas Detalladas',
        'comentarios_curso' => 'Comentarios del Curso',
        'comentarios_profesores' => 'Comentarios de Profesores'
    ];
    
    foreach ($secciones_test as $seccion => $nombre) {
        echo "   🔄 Probando sección: $nombre...\n";
        
        try {
            $pdf_result = $generator->generarReportePorCursoFecha(
                $curso_id, 
                $fecha_con_datos, 
                [$seccion], 
                []
            );
            
            if ($pdf_result && strlen($pdf_result) > 1000) {
                echo "      ✅ $nombre generada (" . number_format(strlen($pdf_result)) . " bytes)\n";
                
                // Guardar cada sección por separado
                $filename = "test_" . $seccion . ".pdf";
                file_put_contents($filename, $pdf_result);
                echo "      💾 Guardado como: $filename\n";
                
                // Verificar si contiene texto esperado
                $texto_buscar = [
                    'graficos_evaluacion' => 'GRÁFICOS',
                    'estadisticas_detalladas' => 'ESTADÍSTICAS',
                    'comentarios_curso' => 'COMENTARIOS DEL CURSO',
                    'comentarios_profesores' => 'COMENTARIOS DE PROFESORES'
                ];
                
                if (isset($texto_buscar[$seccion])) {
                    $contiene_texto = strpos($pdf_result, $texto_buscar[$seccion]) !== false;
                    echo "      " . ($contiene_texto ? "✅" : "❌") . " Contiene texto esperado: " . $texto_buscar[$seccion] . "\n";
                }
                
            } else {
                echo "      ❌ $nombre falló o muy pequeña\n";
                echo "      🔍 Tamaño: " . strlen($pdf_result ?? '') . " bytes\n";
            }
            
        } catch (Exception $e) {
            echo "      ❌ Error en $nombre: " . $e->getMessage() . "\n";
            echo "      🔍 Línea: " . $e->getLine() . "\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error general: " . $e->getMessage() . "\n";
}

// Test de PDF completo
echo "\n5. Generando PDF completo...\n";
try {
    $todas_secciones = array_keys($secciones_test);
    $pdf_completo = $generator->generarReportePorCursoFecha(
        $curso_id, 
        $fecha_con_datos, 
        $todas_secciones, 
        []
    );
    
    if ($pdf_completo && strlen($pdf_completo) > 1000) {
        echo "   ✅ PDF completo generado (" . number_format(strlen($pdf_completo)) . " bytes)\n";
        
        file_put_contents('test_completo.pdf', $pdf_completo);
        echo "   💾 Guardado como: test_completo.pdf\n";
        
        // Análisis del contenido
        echo "\n   🔍 Análisis del contenido:\n";
        $textos_esperados = [
            'REPORTE DE ENCUESTAS ACADÉMICAS' => 'Título principal',
            'GRÁFICOS DE EVALUACIÓN' => 'Sección gráficos',
            'ESTADÍSTICAS DETALLADAS' => 'Sección estadísticas',
            'COMENTARIOS DEL CURSO' => 'Sección comentarios curso',
            'COMENTARIOS DE PROFESORES' => 'Sección comentarios profesores'
        ];
        
        foreach ($textos_esperados as $texto => $descripcion) {
            $encontrado = strpos($pdf_completo, $texto) !== false;
            echo "      " . ($encontrado ? "✅" : "❌") . " $descripcion: $texto\n";
        }
        
    } else {
        echo "   ❌ PDF completo falló\n";
        echo "   🔍 Tamaño: " . strlen($pdf_completo ?? '') . " bytes\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error PDF completo: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL TEST DETALLADO ===\n";
echo "\n💡 Revisa los archivos PDF generados:\n";
echo "   - test_graficos_evaluacion.pdf\n";
echo "   - test_estadisticas_detalladas.pdf\n";
echo "   - test_comentarios_curso.pdf\n";
echo "   - test_comentarios_profesores.pdf\n";
echo "   - test_completo.pdf\n";
?>
