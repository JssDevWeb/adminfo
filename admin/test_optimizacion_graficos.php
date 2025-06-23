<?php
/**
 * Script de prueba para verificar mejoras en la disposición de los gráficos y tablas en PDF
 */

require_once 'pdf/ReportePdfGenerator.php';

// Configuración de la conexión a la base de datos
require_once '../config/database.php';

// Parámetros de prueba
$curso_id = 1; // Asumimos que existe un curso con ID = 1
$fecha = date('Y-m-d'); // Fecha actual

try {
    $db = Database::connect();
    
    // Verificar si el curso existe
    $stmt = $db->prepare("SELECT id, nombre FROM cursos WHERE id = ?");
    $stmt->execute([$curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$curso) {
        echo "<p style='color:red'>Error: El curso con ID $curso_id no existe. Buscando un curso válido...</p>";
        
        // Buscar el primer curso disponible
        $stmt = $db->query("SELECT id, nombre FROM cursos LIMIT 1");
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$curso) {
            echo "<p style='color:red'>No se encontraron cursos en la base de datos.</p>";
            exit;
        }
        
        $curso_id = $curso['id'];
        echo "<p>Se utilizará el curso: {$curso['nombre']} (ID: {$curso['id']})</p>";
    }
    
    // Buscar fechas con datos
    $stmt = $db->prepare("
        SELECT DISTINCT DATE(fecha_envio) as fecha
        FROM encuestas 
        WHERE curso_id = ?
        ORDER BY fecha_envio DESC
        LIMIT 1
    ");
    $stmt->execute([$curso_id]);
    $fecha_resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($fecha_resultado) {
        $fecha = $fecha_resultado['fecha'];
        echo "<p>Se utilizará la fecha: {$fecha}</p>";
    } else {
        echo "<p style='color:orange'>Advertencia: No se encontraron encuestas para este curso, se usará la fecha actual.</p>";
    }
      echo "<h2>Generando PDF con disposición optimizada...</h2>";
    
    // Generar el PDF
    $generator = new ReportePdfGenerator();
    $generator->setConnection($db); // Asumimos que existe este método
    $archivo_pdf = "final_graficos_evaluacion_optimizado.pdf";
    $generator->generarReporteEvaluacion($curso_id, $fecha, $archivo_pdf);
    
    echo "<h3>PDF generado correctamente: <a href='$archivo_pdf' target='_blank'>Ver PDF</a></h3>";
    echo "<p>Ubicación: " . realpath($archivo_pdf) . "</p>";
    
    // Mostrar el PDF incrustado en la página
    echo "<iframe src='$archivo_pdf' width='100%' height='500px'></iframe>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error al generar el PDF</h2>";
    echo "<p>Mensaje: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")</p>";
}
?>
