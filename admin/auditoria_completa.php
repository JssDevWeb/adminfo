<?php
echo "=== AUDITORÍA COMPLETA DEL SISTEMA PDF ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar archivos críticos
echo "1. VERIFICANDO ARCHIVOS CRÍTICOS...\n";

$archivos_criticos = [
    'ReportePdfGenerator.php' => __DIR__ . '/pdf/ReportePdfGenerator.php',
    'exportar_pdf.php' => __DIR__ . '/exportar_pdf.php',
    'reportes.php' => __DIR__ . '/reportes.php',
    'database.php' => __DIR__ . '/../config/database.php'
];

foreach ($archivos_criticos as $nombre => $ruta) {
    if (file_exists($ruta)) {
        $tamaño = filesize($ruta);
        $modificacion = date('Y-m-d H:i:s', filemtime($ruta));
        echo "   ✓ $nombre: $tamaño bytes (modificado: $modificacion)\n";
    } else {
        echo "   ✗ $nombre: NO EXISTE en $ruta\n";
    }
}

echo "\n2. VERIFICANDO ESTRUCTURA DE BASE DE DATOS...\n";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/database.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Verificar tablas principales
    $tablas = ['encuestas', 'respuestas', 'profesores', 'cursos', 'preguntas'];
    foreach ($tablas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
            $count = $stmt->fetch()['total'];
            echo "   ✓ Tabla '$tabla': $count registros\n";
        } catch (Exception $e) {
            echo "   ✗ Error en tabla '$tabla': " . $e->getMessage() . "\n";
        }
    }
    
    // Verificar datos específicos que deberían aparecer en el PDF
    echo "\n   Datos específicos para PDF:\n";
    
    // Curso específico del PDF que generaste
    $stmt = $pdo->query("SELECT * FROM cursos WHERE nombre LIKE '%Física%' OR nombre LIKE '%General%'");
    $cursos_fisica = $stmt->fetchAll();
    
    foreach ($cursos_fisica as $curso) {
        echo "   → Curso encontrado: ID={$curso['id']}, Nombre='{$curso['nombre']}'\n";
        
        // Encuestas para este curso
        $stmt2 = $pdo->query("SELECT COUNT(*) as total FROM encuestas WHERE curso_id = {$curso['id']}");
        $encuestas_curso = $stmt2->fetch()['total'];
        echo "     - Encuestas: $encuestas_curso\n";
        
        // Respuestas para este curso
        $stmt3 = $pdo->query("
            SELECT COUNT(*) as total 
            FROM respuestas r 
            JOIN encuestas e ON r.encuesta_id = e.id 
            WHERE e.curso_id = {$curso['id']}
        ");
        $respuestas_curso = $stmt3->fetch()['total'];
        echo "     - Respuestas: $respuestas_curso\n";
        
        // Profesores evaluados en este curso
        $stmt4 = $pdo->query("
            SELECT DISTINCT p.nombre 
            FROM profesores p 
            JOIN respuestas r ON p.id = r.profesor_id 
            JOIN encuestas e ON r.encuesta_id = e.id 
            WHERE e.curso_id = {$curso['id']}
        ");
        $profesores_curso = $stmt4->fetchAll();
        echo "     - Profesores evaluados: " . count($profesores_curso) . "\n";
        foreach ($profesores_curso as $prof) {
            echo "       * {$prof['nombre']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Error BD: " . $e->getMessage() . "\n";
}

echo "\n3. ANALIZANDO ReportePdfGenerator.php...\n";

if (file_exists(__DIR__ . '/pdf/ReportePdfGenerator.php')) {
    $contenido = file_get_contents(__DIR__ . '/pdf/ReportePdfGenerator.php');
    
    // Buscar métodos críticos
    $metodos_buscar = [
        'generarReporte' => 'Método principal',
        'obtenerEstadisticasGenerales' => 'Estadísticas generales',
        'generarResumenGeneral' => 'Sección de estadísticas',
        'generarGraficos' => 'Sección de gráficos',  
        'generarComentarios' => 'Sección de comentarios',
        'generarReportePorCurso' => 'Reporte por curso específico'
    ];
    
    foreach ($metodos_buscar as $metodo => $descripcion) {
        if (strpos($contenido, "function $metodo") !== false) {
            echo "   ✓ $metodo encontrado ($descripcion)\n";
        } else {
            echo "   ✗ $metodo NO ENCONTRADO ($descripcion)\n";
        }
    }
    
    // Verificar consultas SQL problemáticas
    $problemas_sql = [
        'valor_texts' => 'Tabla inexistente (debería ser respuestas)',
        'curso_id' => 'Campo que podría no existir en respuestas',
        'FROM valor_text' => 'Consulta a tabla inexistente'
    ];
    
    echo "\n   Buscando problemas SQL conocidos:\n";
    foreach ($problemas_sql as $problema => $descripcion) {
        if (strpos($contenido, $problema) !== false) {
            echo "   ⚠ PROBLEMA ENCONTRADO: '$problema' - $descripción\n";
        } else {
            echo "   ✓ '$problema' - No encontrado (bien)\n";
        }
    }
}

echo "\n4. VERIFICANDO EL ARCHIVO exportar_pdf.php...\n";

$archivo_exportar = __DIR__ . '/exportar_pdf.php';
if (file_exists($archivo_exportar)) {
    $contenido_exportar = file_get_contents($archivo_exportar);
    
    echo "   ✓ Archivo existe\n";
    
    // Verificar que use ReportePdfGenerator correctamente
    if (strpos($contenido_exportar, 'ReportePdfGenerator') !== false) {
        echo "   ✓ Usa ReportePdfGenerator\n";
    } else {
        echo "   ✗ NO usa ReportePdfGenerator\n";
    }
    
    // Verificar parámetros esperados
    $parametros = ['curso_id', 'fecha', 'secciones'];
    echo "   Parámetros verificados:\n";
    foreach ($parametros as $param) {
        if (strpos($contenido_exportar, $param) !== false) {
            echo "     ✓ '$param' encontrado\n";
        } else {
            echo "     ✗ '$param' NO encontrado\n";
        }
    }
    
} else {
    echo "   ✗ exportar_pdf.php NO EXISTE\n";
}

echo "\n5. PROBANDO GENERACIÓN REAL...\n";

try {
    require_once __DIR__ . '/pdf/ReportePdfGenerator.php';
    
    // Simular la llamada real que hace el sistema
    echo "   → Instanciando ReportePdfGenerator...\n";
    $generator = new ReportePdfGenerator();
    
    // Probar diferentes combinaciones
    $pruebas = [
        'PDF vacío' => [],
        'Solo estadísticas' => ['estadisticas'],
        'Estadísticas + comentarios' => ['estadisticas', 'comentarios'],
        'Completo' => ['estadisticas', 'graficos', 'comentarios']
    ];
    
    foreach ($pruebas as $nombre => $secciones) {
        try {
            echo "   → Probando '$nombre'...\n";
            $pdf_content = $generator->generarReporte($secciones);
            $tamaño = strlen($pdf_content);
            
            if ($tamaño > 0) {
                echo "     ✓ Generado: $tamaño bytes\n";
                
                // Guardar para análisis
                $archivo_prueba = __DIR__ . "/pdf/auditoria_" . str_replace(' ', '_', strtolower($nombre)) . ".pdf";
                file_put_contents($archivo_prueba, $pdf_content);
                echo "     ✓ Guardado: $archivo_prueba\n";
            } else {
                echo "     ✗ PDF vacío (0 bytes)\n";
            }
            
        } catch (Exception $e) {
            echo "     ✗ Error: " . $e->getMessage() . "\n";
        }
        
        // Nueva instancia para evitar conflictos
        $generator = new ReportePdfGenerator();
    }
    
} catch (Exception $e) {
    echo "   ✗ Error fatal: " . $e->getMessage() . "\n";
}

echo "\n6. VERIFICANDO PROBLEMA ESPECÍFICO CON CURSO...\n";

// El usuario menciona que seleccionó un curso pero no aparece la info
// Vamos a buscar métodos específicos para cursos

try {
    $reflection = new ReflectionClass('ReportePdfGenerator');
    $metodos = $reflection->getMethods();
    
    echo "   Métodos relacionados con cursos:\n";
    foreach ($metodos as $metodo) {
        $nombre = $metodo->getName();
        if (strpos(strtolower($nombre), 'curso') !== false) {
            $visibilidad = $metodo->isPublic() ? 'público' : ($metodo->isPrivate() ? 'privado' : 'protegido');
            echo "     → $nombre ($visibilidad)\n";
        }
    }
    
    // Verificar si existe método para generar reporte por curso específico
    if ($reflection->hasMethod('generarReportePorCurso')) {
        echo "   ✓ Método generarReportePorCurso existe\n";
    } else {
        echo "   ⚠ Método generarReportePorCurso NO EXISTE\n";
        echo "     Esto podría ser el problema: el sistema no filtra por curso\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error analizando métodos: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNÓSTICO Y RECOMENDACIONES ===\n";

echo "PROBLEMAS IDENTIFICADOS:\n";
echo "1. Si el método generarReportePorCurso no existe, el PDF no filtra por curso\n";
echo "2. Las estadísticas podrían ser generales en lugar de específicas del curso\n";
echo "3. Los comentarios podrían no estar filtrados por el curso seleccionado\n";

echo "\nSOLUCIONES NECESARIAS:\n";
echo "1. Crear método generarReportePorCurso(\$curso_id, \$fecha)\n";
echo "2. Modificar obtenerEstadisticasGenerales() para filtrar por curso\n";
echo "3. Actualizar generarComentarios() para mostrar solo comentarios del curso\n";
echo "4. Verificar que exportar_pdf.php pase los parámetros correctos\n";

echo "\nARCHIVOS GENERADOS PARA ANÁLISIS:\n";
$archivos_auditoria = glob(__DIR__ . '/pdf/auditoria_*.pdf');
foreach ($archivos_auditoria as $archivo) {
    $nombre = basename($archivo);
    $tamaño = filesize($archivo);
    echo "- $nombre: $tamaño bytes\n";
}

echo "\n=== FIN DE LA AUDITORÍA ===\n";
?>
