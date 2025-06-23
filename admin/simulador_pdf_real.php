<?php
echo "=== SIMULADOR DE GENERACIÓN REAL DE PDF ===\n";
echo "Simulando exactamente lo que hace el sistema cuando generas un PDF\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    // Simular parámetros reales que recibiría procesar_pdf.php
    echo "1. Simulando parámetros del formulario...\n";
    
    // Curso Física General (el que mencionas en tu PDF)
    $curso_id = 3; // ID del curso Física General
    $fecha = '2025-06-20 13:23:10'; // Fecha real de la encuesta
    
    // Secciones que probablemente seleccionaste
    $secciones_posibles = [
        // Opciones que aparecen en el formulario exportar_pdf.php
        'graficos_evaluacion',
        'estadisticas_detalladas', 
        'comentarios_curso',
        'comentarios_profesores',
        'resumen_ejecutivo',
        'preguntas_criticas'
    ];
    
    echo "   - Curso ID: $curso_id (Física General)\n";
    echo "   - Fecha: $fecha\n";
    echo "   - Secciones disponibles: " . implode(', ', $secciones_posibles) . "\n\n";
    
    // Verificar datos del curso
    echo "2. Verificando datos del curso seleccionado...\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch();
    
    if (!$curso) {
        echo "   ✗ Curso no encontrado\n";
        exit;
    }
    
    echo "   ✓ Curso: {$curso['nombre']}\n";
    
    // Verificar encuestas en esa fecha
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM encuestas 
        WHERE curso_id = :curso_id 
        AND DATE(fecha_envio) = DATE(:fecha)
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $encuestas_fecha = $stmt->fetch()['total'];
    
    echo "   - Encuestas en la fecha específica: $encuestas_fecha\n";
    
    // Verificar todas las encuestas del curso
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM encuestas WHERE curso_id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $encuestas_total = $stmt->fetch()['total'];
    
    echo "   - Total encuestas del curso: $encuestas_total\n\n";
    
    // 3. Probar diferentes combinaciones de secciones
    echo "3. Probando diferentes combinaciones de secciones...\n";
    
    $combinaciones_test = [
        'Solo gráficos' => ['graficos_evaluacion'],
        'Solo estadísticas' => ['estadisticas_detalladas'],
        'Solo comentarios curso' => ['comentarios_curso'],
        'Solo comentarios profesores' => ['comentarios_profesores'],
        'Gráficos + Estadísticas' => ['graficos_evaluacion', 'estadisticas_detalladas'],
        'Todo completo' => ['graficos_evaluacion', 'estadisticas_detalladas', 'comentarios_curso', 'comentarios_profesores'],
        'Como tu PDF' => ['estadisticas_detalladas', 'comentarios_curso', 'comentarios_profesores'] // Sin gráficos para simuar tu caso
    ];
    
    foreach ($combinaciones_test as $nombre => $secciones) {
        echo "   → Probando '$nombre'...\n";
        
        try {
            $generator = new ReportePdfGenerator();
            
            // Usar el método exacto que usa procesar_pdf.php
            $pdf_content = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, []);
            
            $tamaño = strlen($pdf_content);
            echo "     ✓ Generado: $tamaño bytes\n";
            
            if ($tamaño > 0) {
                $nombre_archivo = str_replace(' ', '_', strtolower($nombre));
                $archivo = __DIR__ . "/pdf/simulacion_{$nombre_archivo}.pdf";
                file_put_contents($archivo, $pdf_content);
                echo "     ✓ Guardado: $archivo\n";
            } else {
                echo "     ✗ PDF vacío\n";
            }
            
        } catch (Exception $e) {
            echo "     ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n4. Generando PDF idéntico al que deberías obtener...\n";
    
    // Esta es la combinación más probable que usaste
    $secciones_tu_pdf = ['estadisticas_detalladas', 'comentarios_curso', 'comentarios_profesores'];
    
    $generator_final = new ReportePdfGenerator();
    $pdf_final = $generator_final->generarReportePorCursoFecha($curso_id, $fecha, $secciones_tu_pdf, []);
    
    $archivo_final = __DIR__ . "/pdf/SIMULACION_TU_PDF_EXACTO.pdf";
    file_put_contents($archivo_final, $pdf_final);
    
    $tamaño_final = filesize($archivo_final);
    echo "   ✓ PDF simulado generado: $tamaño_final bytes\n";
    echo "   ✓ Guardado en: $archivo_final\n";
    
    echo "\n=== COMPARACIÓN Y DIAGNÓSTICO ===\n";
    
    if ($tamaño_final > 1000) {
        echo "✅ El sistema genera PDFs correctamente con datos del curso Física General\n";
        echo "✅ PDF simulado: $tamaño_final bytes\n";
        echo "\n🔍 POSIBLES CAUSAS DEL PROBLEMA:\n";
        echo "1. Las secciones seleccionadas en el formulario no coinciden\n";
        echo "2. Los gráficos no se están enviando correctamente desde el frontend\n";
        echo "3. Hay un problema en la configuración del navegador para descargas\n";
        echo "4. El PDF se genera pero no se descarga correctamente\n";
        echo "\n📋 PARA DEPURAR:\n";
        echo "1. Abre SIMULACION_TU_PDF_EXACTO.pdf y compáralo con tu PDF original\n";
        echo "2. Verifica qué secciones seleccionas en el formulario de exportación\n";
        echo "3. Revisa los logs del servidor (error_log) cuando generes el PDF\n";
        echo "4. Prueba con diferentes navegadores\n";
    } else {
        echo "❌ Hay un problema en la generación del PDF\n";
        echo "❌ Revisar los archivos individuales de simulación\n";
    }
    
    echo "\n📄 ARCHIVOS GENERADOS PARA REVISIÓN:\n";
    $archivos = glob(__DIR__ . '/pdf/simulacion_*.pdf');
    foreach ($archivos as $archivo) {
        $nombre = basename($archivo);
        $tamaño = filesize($archivo);
        echo "- $nombre: $tamaño bytes\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}

echo "\n=== FIN DE LA SIMULACIÓN ===\n";
?>
