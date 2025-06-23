<?php
echo "=== TEST ESPECÍFICO DE obtenerEstadisticasRealesParaTabla ===\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    echo "1. Verificando datos disponibles en la base de datos...\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Verificar datos específicos para el curso "Física General" (ID=2) en la fecha 2024-12-15
    $stmt = $pdo->query("
        SELECT 
            e.id, e.curso_id, e.fecha_envio,
            COUNT(r.id) as respuestas,
            c.nombre as curso_nombre
        FROM encuestas e 
        LEFT JOIN respuestas r ON e.id = r.encuesta_id 
        LEFT JOIN cursos c ON e.curso_id = c.id
        GROUP BY e.id, e.curso_id, e.fecha_envio, c.nombre
        ORDER BY e.fecha_envio DESC
    ");
    
    $encuestas = $stmt->fetchAll();
    echo "   Encuestas disponibles:\n";
    foreach ($encuestas as $enc) {
        echo "   - ID {$enc['id']}: {$enc['curso_nombre']} ({$enc['fecha_envio']}) - {$enc['respuestas']} respuestas\n";
    }
    
    echo "\n2. Probando obtenerEstadisticasRealesParaTabla() con diferentes fechas...\n";
    
    $generator = new ReportePdfGenerator();
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('obtenerEstadisticasRealesParaTabla');
    $method->setAccessible(true);
    
    // Probar con diferentes combinaciones
    $tests = [
        ['curso_id' => 2, 'fecha' => '2024-12-15'],
        ['curso_id' => 2, 'fecha' => '2024-12-16'],  
        ['curso_id' => 1, 'fecha' => '2024-12-15'],
        ['curso_id' => 3, 'fecha' => '2024-12-15']
    ];
    
    foreach ($tests as $test) {
        echo "   → Curso {$test['curso_id']}, Fecha {$test['fecha']}:\n";
        
        $stats = $method->invoke($generator, $test['curso_id'], $test['fecha']);
        echo "     Resultados: " . count($stats) . " estadísticas\n";
        
        if (!empty($stats)) {
            foreach ($stats as $stat) {
                echo "     - {$stat['tipo']}: {$stat['nombre']} (E:{$stat['encuestas']}, P:{$stat['preguntas']}, Prom:" . number_format($stat['puntuacion'], 2) . ")\n";
            }
        }
    }
    
    echo "\n3. Verificando consulta SQL directa...\n";
    
    // Probar la consulta SQL directamente
    $stmt = $pdo->prepare("
        SELECT 
            c.nombre,
            COUNT(DISTINCT e.id) as total_encuestas,
            COUNT(DISTINCT r.pregunta_id) as total_preguntas,
            AVG(r.valor_int) as promedio
        FROM cursos c
        JOIN encuestas e ON c.id = e.curso_id
        JOIN respuestas r ON e.id = r.encuesta_id
        JOIN preguntas p ON r.pregunta_id = p.id
        WHERE c.id = :curso_id
        AND DATE(e.fecha_envio) = :fecha
        AND p.tipo = 'escala'
        AND p.seccion = 'curso'
        GROUP BY c.id, c.nombre
    ");
    
    $stmt->execute([':curso_id' => 2, ':fecha' => '2024-12-15']);
    $curso_stats = $stmt->fetch();
    
    if ($curso_stats) {
        echo "   ✓ Consulta de curso funciona:\n";
        echo "     - Nombre: {$curso_stats['nombre']}\n";
        echo "     - Encuestas: {$curso_stats['total_encuestas']}\n";
        echo "     - Preguntas: {$curso_stats['total_preguntas']}\n";
        echo "     - Promedio: " . number_format($curso_stats['promedio'], 2) . "\n";
    } else {
        echo "   ✗ La consulta de curso no devuelve resultados\n";
        
        // Verificar si hay datos sin filtro de sección
        $stmt2 = $pdo->prepare("
            SELECT 
                c.nombre,
                COUNT(DISTINCT e.id) as total_encuestas,
                COUNT(DISTINCT r.pregunta_id) as total_preguntas,
                AVG(r.valor_int) as promedio
            FROM cursos c
            JOIN encuestas e ON c.id = e.curso_id
            JOIN respuestas r ON e.id = r.encuesta_id
            JOIN preguntas p ON r.pregunta_id = p.id
            WHERE c.id = :curso_id
            AND DATE(e.fecha_envio) = :fecha
            AND p.tipo = 'escala'
            GROUP BY c.id, c.nombre
        ");
        
        $stmt2->execute([':curso_id' => 2, ':fecha' => '2024-12-15']);
        $curso_stats2 = $stmt2->fetch();
        
        if ($curso_stats2) {
            echo "   → Sin filtro de sección SÍ hay datos:\n";
            echo "     - Nombre: {$curso_stats2['nombre']}\n";
            echo "     - Encuestas: {$curso_stats2['total_encuestas']}\n";
            echo "     - Preguntas: {$curso_stats2['total_preguntas']}\n";
            echo "     - Promedio: " . number_format($curso_stats2['promedio'], 2) . "\n";
            echo "   ℹ️ El problema puede ser el filtro p.seccion = 'curso'\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DEL TEST ESPECÍFICO ===\n";
?>
