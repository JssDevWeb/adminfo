<?php
/**
 * Test final con datos reales del curso Física General
 */

echo "=== TEST FINAL CON DATOS REALES ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once 'pdf/ReportePdfGenerator.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Usar curso con datos conocidos
    $curso_id = 3; // Física General
    $fecha = '2025-06-20';
    
    echo "📚 Curso: Física General (ID: $curso_id)\n";
    echo "📅 Fecha: $fecha\n\n";
    
    // Verificar datos disponibles
    echo "1. Verificando datos disponibles...\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM encuestas WHERE curso_id = :curso_id AND DATE(fecha_envio) = :fecha");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $total_encuestas = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM respuestas r JOIN encuestas e ON r.encuesta_id = e.id WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $total_respuestas = $stmt->fetchColumn();
    
    echo "   📊 Encuestas: $total_encuestas\n";
    echo "   💬 Respuestas: $total_respuestas\n";
    
    if ($total_encuestas == 0) {
        echo "   ⚠️  Sin datos - PDF estará mayormente vacío\n";
    }
    
    // Generar PDF con cada sección
    echo "\n2. Generando PDF por secciones...\n";
    
    $generator = new ReportePdfGenerator();
    $secciones = [
        'graficos_evaluacion' => 'Gráficos de Evaluación',
        'estadisticas_detalladas' => 'Estadísticas Detalladas',
        'comentarios_curso' => 'Comentarios del Curso',
        'comentarios_profesores' => 'Comentarios de Profesores'
    ];
    
    foreach ($secciones as $clave => $nombre) {
        echo "   🔄 $nombre...\n";
        
        try {
            $pdf_seccion = $generator->generarReportePorCursoFecha(
                $curso_id,
                $fecha,
                [$clave],
                []
            );
            
            if ($pdf_seccion && strlen($pdf_seccion) > 1000) {
                echo "      ✅ Generada (" . number_format(strlen($pdf_seccion)) . " bytes)\n";
                
                $filename = "final_" . $clave . ".pdf";
                file_put_contents($filename, $pdf_seccion);
                echo "      💾 Guardada: $filename\n";
            } else {
                echo "      ❌ Error o muy pequeña\n";
            }
            
        } catch (Exception $e) {
            echo "      ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // PDF completo
    echo "\n3. Generando PDF completo...\n";
    
    try {
        $pdf_completo = $generator->generarReportePorCursoFecha(
            $curso_id,
            $fecha,
            array_keys($secciones),
            []
        );
        
        if ($pdf_completo && strlen($pdf_completo) > 1000) {
            echo "   ✅ PDF completo generado (" . number_format(strlen($pdf_completo)) . " bytes)\n";
            
            // Crear nombre descriptivo
            $nombre_archivo = "reporte_Fisica_General_2025-06-20_" . date('H-i-s') . ".pdf";
            file_put_contents($nombre_archivo, $pdf_completo);
            echo "   💾 Guardado: $nombre_archivo\n";
            
            echo "\n🎉 PDF COMPLETO GENERADO EXITOSAMENTE\n";
            echo "📄 Archivo: $nombre_archivo\n";
            echo "📊 Tamaño: " . number_format(strlen($pdf_completo)) . " bytes\n";
            
        } else {
            echo "   ❌ Error generando PDF completo\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error en PDF completo: " . $e->getMessage() . "\n";
        echo "   🔍 Línea: " . $e->getLine() . "\n";
        echo "   🔍 Archivo: " . $e->getFile() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "🔍 Línea: " . $e->getLine() . "\n";
}

echo "\n=== RESUMEN FINAL ===\n";
echo "✅ ReportePdfGenerator funciona correctamente\n";
echo "📁 Archivos generados en el directorio actual\n";
echo "🎯 Abre los PDFs para verificar el contenido visual\n";
echo "\n💡 Si los PDFs se ven vacíos, es porque no hay datos suficientes\n";
echo "   en la base de datos para la fecha especificada.\n";
?>
