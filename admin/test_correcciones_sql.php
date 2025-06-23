<?php
/**
 * Test para verificar las correcciones SQL realizadas en ReportePdfGenerator
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

// Configurar para mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DE CORRECCIONES SQL EN REPORTEPDFGENERATOR ===\n\n";

try {
    // Conectar a la base de datos
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "✅ Conexión a la base de datos: OK\n\n";
    
    // Instanciar el generador
    $generator = new ReportePdfGenerator();
    echo "✅ Instancia del generador: OK\n\n";
    
    // Test 1: Obtener curso y fecha de prueba
    echo "--- TEST 1: Obteniendo datos de prueba ---\n";
    $stmt = $pdo->query("
        SELECT DISTINCT 
            c.id as curso_id, 
            c.nombre as curso_nombre,
            DATE(e.fecha_envio) as fecha
        FROM cursos c
        JOIN encuestas e ON c.id = e.curso_id
        JOIN respuestas r ON e.id = r.encuesta_id
        LIMIT 1
    ");
    $datos_prueba = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($datos_prueba) {
        echo "Curso ID: {$datos_prueba['curso_id']}\n";
        echo "Curso: {$datos_prueba['curso_nombre']}\n";
        echo "Fecha: {$datos_prueba['fecha']}\n\n";
    } else {
        echo "❌ No se encontraron datos de prueba\n";
        exit;
    }
    
    // Test 2: Verificar métodos corregidos con reflexión
    echo "--- TEST 2: Verificando métodos corregidos ---\n";
    $reflection = new ReflectionClass($generator);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE | ReflectionMethod::IS_PROTECTED);
    
    $metodos_obtener = [];
    foreach ($methods as $method) {
        if (strpos($method->getName(), 'obtener') === 0) {
            $metodos_obtener[] = $method->getName();
        }
    }
    
    echo "Métodos 'obtener' encontrados: " . count($metodos_obtener) . "\n";
    foreach ($metodos_obtener as $metodo) {
        echo "  - $metodo\n";
    }
    echo "\n";
    
    // Test 3: Probar algunos métodos específicos usando reflexión
    echo "--- TEST 3: Probando métodos específicos ---\n";
    
    // Hacer métodos accesibles para testing
    $metodos_a_probar = [
        'obtenerEstadisticasGenerales',
        'obtenerEstadisticasProfesores', 
        'obtenerAnalisisPreguntas',
        'obtenerComentariosCurso',
        'obtenerEstadisticasPorProfesor',
        'obtenerEstadisticasPorCategoria'
    ];
    
    foreach ($metodos_a_probar as $nombre_metodo) {
        try {
            $method = $reflection->getMethod($nombre_metodo);
            $method->setAccessible(true);
            
            echo "Probando $nombre_metodo...\n";
            
            if ($method->getNumberOfParameters() == 0) {
                // Sin parámetros
                $resultado = $method->invoke($generator);
            } elseif ($method->getNumberOfParameters() == 2) {
                // Con curso_id y fecha
                $resultado = $method->invoke($generator, $datos_prueba['curso_id'], $datos_prueba['fecha']);
            } else {
                echo "  ⚠️ Método con parámetros no esperados, saltando...\n";
                continue;
            }
            
            if (is_array($resultado)) {
                $count = count($resultado);
                echo "  ✅ Resultado: Array con $count elementos\n";
                if ($count > 0) {
                    echo "  📊 Primer elemento: " . json_encode(array_slice($resultado, 0, 1), JSON_UNESCAPED_UNICODE) . "\n";
                }
            } else {
                echo "  ✅ Resultado: " . gettype($resultado) . "\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error en $nombre_metodo: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    // Test 4: Verificar estructura de tablas relevantes
    echo "--- TEST 4: Verificando estructura de tablas ---\n";
    $tablas = ['respuestas', 'preguntas', 'profesores', 'cursos', 'encuestas'];
    
    foreach ($tablas as $tabla) {
        try {
            $stmt = $pdo->query("DESCRIBE $tabla");
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "✅ Tabla '$tabla': " . implode(', ', $columnas) . "\n";
        } catch (Exception $e) {
            echo "❌ Error al describir tabla '$tabla': " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Test 5: Verificar datos en tabla respuestas
    echo "--- TEST 5: Verificando datos en respuestas ---\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_respuestas,
            COUNT(CASE WHEN valor_int IS NOT NULL THEN 1 END) as respuestas_numericas,
            COUNT(CASE WHEN valor_text IS NOT NULL AND valor_text != '' THEN 1 END) as respuestas_texto,
            MIN(valor_int) as min_valor, 
            MAX(valor_int) as max_valor,
            AVG(valor_int) as promedio_valor
        FROM respuestas
    ");
    $stats_respuestas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total respuestas: {$stats_respuestas['total_respuestas']}\n";
    echo "Respuestas numéricas: {$stats_respuestas['respuestas_numericas']}\n";
    echo "Respuestas texto: {$stats_respuestas['respuestas_texto']}\n";
    echo "Rango valores: {$stats_respuestas['min_valor']} - {$stats_respuestas['max_valor']}\n";
    echo "Promedio: " . round($stats_respuestas['promedio_valor'], 2) . "\n\n";
    
    echo "=== TEST COMPLETADO ===\n";
    echo "Las correcciones SQL han sido verificadas.\n";
    echo "Revise los errores (❌) si los hay para correcciones adicionales.\n";
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
