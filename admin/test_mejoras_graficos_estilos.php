<?php
/**
 * Script de prueba para verificar las mejoras en los gráficos y estilos
 * Este script genera un PDF de reporte con las mejoras implementadas
 */

require_once 'pdf/ReportePdfGenerator.php';
require_once '../config/database.php';

// Configuración inicial
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

echo "==================================================\n";
echo "TEST DE MEJORAS EN GRÁFICOS Y ESTILOS DE REPORTES\n";
echo "==================================================\n\n";

// No necesitamos crear una conexión PDO manualmente ya que
// la clase ReportePdfGenerator se encarga de esto usando Database::getInstance()

// Obtener un curso y fecha para prueba
function obtenerCursoParaPrueba() {
    try {
        // Usar la instancia de base de datos desde la clase Database
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->query("
            SELECT c.id AS curso_id, c.nombre AS curso_nombre, 
                   DATE(e.fecha_envio) AS fecha,
                   COUNT(DISTINCT e.id) AS num_encuestas
            FROM cursos c
            JOIN encuestas e ON c.id = e.curso_id
            JOIN respuestas r ON e.id = r.encuesta_id
            GROUP BY c.id, c.nombre, DATE(e.fecha_envio)
            HAVING num_encuestas > 0
            ORDER BY num_encuestas DESC
            LIMIT 1
        ");
        
        $curso = $stmt->fetch();
        if (!$curso) {
            die("No se encontraron cursos con encuestas para la prueba.\n");
        }
        
        return $curso;
    } catch (PDOException $e) {
        die("Error al obtener curso de prueba: " . $e->getMessage() . "\n");
    }
}

// Ejecutar la prueba
function ejecutarPrueba() {
    $curso = obtenerCursoParaPrueba();
    
    echo "Curso seleccionado: {$curso['curso_nombre']} (ID: {$curso['curso_id']})\n";
    echo "Fecha: {$curso['fecha']}\n";
    echo "Número de encuestas: {$curso['num_encuestas']}\n\n";
    
    echo "Generando PDF con mejoras en gráficos y estilos...\n";
    
    $outputFile = 'test_mejoras_graficos_' . date('Y-m-d_H-i-s') . '.pdf';
    
    try {
        // Crear instancia del generador de PDF sin pasar PDO (el constructor lo maneja)
        $pdfGenerator = new ReportePdfGenerator();
        
        // Generar el PDF usando el método correcto: generarReportePorCursoFecha        $outputFile = 'test_mejoras_graficos_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Generar y guardar el PDF
        $pdfContent = $pdfGenerator->generarReportePorCursoFecha(
            $curso['curso_id'],
            $curso['fecha'],
            ['resumen_ejecutivo', 'graficos_evaluacion', 'estadisticas_detalladas', 'preguntas_criticas', 'comentarios_curso'] // Incluir todas las secciones
        );
        
        // Guardar el contenido del PDF en un archivo
        file_put_contents($outputFile, $pdfContent);
        
        echo "PDF generado exitosamente: $outputFile\n";
        echo "Ruta completa: " . __DIR__ . '/' . $outputFile . "\n\n";
        
        // Verificar si se ha generado el archivo
        if (file_exists($outputFile)) {
            $filesize = filesize($outputFile);
            echo "Tamaño del archivo: " . round($filesize / 1024, 2) . " KB\n";
            
            if ($filesize > 0) {
                echo "Estado: ✅ CORRECTO\n";
            } else {
                echo "Estado: ❌ ERROR - El archivo está vacío\n";
            }
        } else {
            echo "Estado: ❌ ERROR - No se generó el archivo\n";
        }
        
    } catch (Exception $e) {
        echo "Error durante la generación del PDF: " . $e->getMessage() . "\n";
        echo "Traza:\n" . $e->getTraceAsString() . "\n";
    }
    
    echo "\n==================================================\n";
    echo "Fin de la prueba\n";
    echo "==================================================\n";
}

// Ejecutar la prueba
ejecutarPrueba();
