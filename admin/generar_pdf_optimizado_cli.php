<?php
/**
 * Script alternativo para generar el PDF optimizado directamente
 * en modo línea de comandos para facilitar la depuración
 */

require_once 'pdf/ReportePdfGenerator.php';
require_once '../config/database.php';

// Configuración para obtener errores detallados
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Parámetros para el reporte
$curso_id = 1;

// Conectar a la BD
echo "Conectando a la base de datos...\n";
try {
    $db = Database::connect();
    echo "Conexión exitosa.\n";
} catch (Exception $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Buscar un curso válido si el curso_id no existe
try {
    $stmt = $db->prepare("SELECT id, nombre FROM cursos WHERE id = ?");
    $stmt->execute([$curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$curso) {
        echo "Curso ID $curso_id no encontrado. Buscando alternativa...\n";
        $stmt = $db->query("SELECT id, nombre FROM cursos LIMIT 1");
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$curso) {
            echo "No hay cursos en la base de datos.\n";
            exit(1);
        }
        
        $curso_id = $curso['id'];
    }
    
    echo "Usando curso: {$curso['nombre']} (ID: {$curso['id']})\n";
} catch (Exception $e) {
    echo "Error al buscar curso: " . $e->getMessage() . "\n";
    exit(1);
}

// Buscar una fecha válida
try {
    $stmt = $db->prepare("
        SELECT DISTINCT DATE(fecha_envio) as fecha
        FROM encuestas 
        WHERE curso_id = ?
        ORDER BY fecha_envio DESC
        LIMIT 1
    ");
    $stmt->execute([$curso_id]);
    $fecha_res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($fecha_res) {
        $fecha = $fecha_res['fecha'];
        echo "Usando fecha: $fecha\n";
    } else {
        $fecha = date('Y-m-d');
        echo "No se encontraron encuestas. Usando fecha actual: $fecha\n";
    }
} catch (Exception $e) {
    echo "Error al buscar fecha: " . $e->getMessage() . "\n";
    exit(1);
}

// Generar el PDF
echo "Generando PDF optimizado...\n";
$output_file = "final_graficos_evaluacion_optimizado.pdf";

try {
    $generator = new ReportePdfGenerator($db);
    
    // Definir secciones a incluir (debe coincidir con generarReporteEvaluacion)
    $secciones = ['resumen_ejecutivo', 'graficos_evaluacion', 'estadisticas_detalladas', 'preguntas_criticas', 'comentarios_curso'];
    
    // Generar el reporte directamente sin usar generarReporteEvaluacion
    $pdf_content = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
    
    if (!$pdf_content) {
        echo "Error: No se generó contenido PDF.\n";
        exit(1);
    }
    
    // Guardar en archivo
    if (file_put_contents($output_file, $pdf_content)) {
        echo "PDF generado correctamente: $output_file\n";
        echo "Tamaño: " . round(filesize($output_file) / 1024, 2) . " KB\n";
    } else {
        echo "Error al guardar el archivo PDF.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Error al generar PDF: " . $e->getMessage() . "\n";
    echo "En archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
    echo "Traza:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Proceso completado.\n";
?>
