<?php
/**
 * TEST DE CONSOLA CORREGIDO PARA PDFs
 * 
 * Este script utiliza la estructura real de la base de datos para generar
 * PDFs de prueba y depurar los problemas de generación.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

echo "=== TEST DE CONSOLA PARA PDFs (ESTRUCTURA REAL) ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. VERIFICAR CONEXIÓN A BASE DE DATOS
    echo "1. Verificando conexión a base de datos...\n";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "   ✓ Conexión establecida correctamente\n\n";
    
    // 2. VERIFICAR ESTRUCTURA DE TABLAS
    echo "2. Verificando estructura de tablas principales...\n";
    
    $tablas = ['respuestas', 'encuestas', 'profesores', 'cursos', 'preguntas'];
    foreach ($tablas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
            $count = $stmt->fetch()['total'];
            echo "   ✓ Tabla '$tabla': $count registros\n";
        } catch (Exception $e) {
            echo "   ✗ Error en tabla '$tabla': " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // 3. VERIFICAR TCPDF BÁSICO
    echo "3. Verificando funcionamiento básico de TCPDF...\n";
    
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'TEST TCPDF - ' . date('H:i:s'), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Este es un test básico de TCPDF funcionando correctamente.', 0, 1);
    $pdf->Cell(0, 10, 'Si puedes leer este texto, la librería funciona.', 0, 1);
    
    // Agregar una tabla simple
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(50, 8, 'Columna 1', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Columna 2', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Columna 3', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 10);
    for ($i = 1; $i <= 3; $i++) {
        $pdf->Cell(50, 8, "Dato $i-A", 1, 0);
        $pdf->Cell(50, 8, "Dato $i-B", 1, 0);
        $pdf->Cell(50, 8, "Dato $i-C", 1, 1);
    }
    
    $archivo_tcpdf = __DIR__ . '/pdf/test_tcpdf_basico.pdf';
    $pdf->Output($archivo_tcpdf, 'F');
    echo "   ✓ PDF básico guardado en: $archivo_tcpdf\n\n";
    
    // 4. OBTENER DATOS REALES PARA PRUEBAS
    echo "4. Obteniendo datos reales de la base de datos...\n";
    
    // Obtener estadísticas generales
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT e.id) as total_encuestas,
            COUNT(DISTINCT r.id) as total_respuestas,
            COUNT(DISTINCT p.id) as total_profesores,
            COUNT(DISTINCT c.id) as total_cursos
        FROM encuestas e
        LEFT JOIN respuestas r ON e.id = r.encuesta_id
        LEFT JOIN profesores p ON r.profesor_id = p.id
        LEFT JOIN cursos c ON e.curso_id = c.id
    ");
    $stats = $stmt->fetch();
    
    echo "   - Encuestas: {$stats['total_encuestas']}\n";
    echo "   - Respuestas: {$stats['total_respuestas']}\n";
    echo "   - Profesores: {$stats['total_profesores']}\n";
    echo "   - Cursos: {$stats['total_cursos']}\n\n";
    
    // 5. GENERAR PDF CON DATOS REALES
    echo "5. Generando PDF con datos reales...\n";
    
    $pdf2 = new TCPDF();
    $pdf2->SetCreator('Sistema de Encuestas');
    $pdf2->SetTitle('Reporte de Datos Reales');
    $pdf2->AddPage();
    
    // Título
    $pdf2->SetFont('helvetica', 'B', 18);
    $pdf2->SetTextColor(0, 100, 0);
    $pdf2->Cell(0, 15, 'REPORTE DE ENCUESTAS ACADÉMICAS', 0, 1, 'C');
    $pdf2->Ln(10);
    
    // Información general
    $pdf2->SetFont('helvetica', '', 11);
    $pdf2->SetTextColor(0, 0, 0);
    $pdf2->Cell(0, 8, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1);
    $pdf2->Ln(5);
    
    // Estadísticas generales
    $pdf2->SetFont('helvetica', 'B', 12);
    $pdf2->Cell(0, 10, 'ESTADÍSTICAS GENERALES:', 0, 1);
    $pdf2->SetFont('helvetica', '', 10);
    
    $estadisticas = [
        "Total de encuestas procesadas: {$stats['total_encuestas']}",
        "Total de respuestas registradas: {$stats['total_respuestas']}",
        "Total de profesores evaluados: {$stats['total_profesores']}",
        "Total de cursos con evaluaciones: {$stats['total_cursos']}"
    ];
    
    foreach ($estadisticas as $stat) {
        $pdf2->Cell(0, 8, '• ' . $stat, 0, 1);
    }
    $pdf2->Ln(10);
    
    // Obtener datos específicos de encuestas
    $stmt = $pdo->query("
        SELECT 
            e.id,
            e.fecha_envio,
            c.nombre as curso_nombre,
            COUNT(r.id) as total_respuestas
        FROM encuestas e
        LEFT JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN respuestas r ON e.id = r.encuesta_id
        GROUP BY e.id, e.fecha_envio, c.nombre
        ORDER BY e.fecha_envio DESC
        LIMIT 10
    ");
    
    $pdf2->SetFont('helvetica', 'B', 12);
    $pdf2->Cell(0, 10, 'ÚLTIMAS 10 ENCUESTAS:', 0, 1);
    $pdf2->Ln(5);
    
    // Encabezados de tabla
    $pdf2->SetFont('helvetica', 'B', 9);
    $pdf2->SetFillColor(230, 230, 230);
    $pdf2->Cell(20, 8, 'ID', 1, 0, 'C', true);
    $pdf2->Cell(80, 8, 'CURSO', 1, 0, 'C', true);
    $pdf2->Cell(40, 8, 'FECHA ENVÍO', 1, 0, 'C', true);
    $pdf2->Cell(30, 8, 'RESPUESTAS', 1, 1, 'C', true);
    
    // Datos de la tabla
    $pdf2->SetFont('helvetica', '', 9);
    while ($row = $stmt->fetch()) {
        $fecha = $row['fecha_envio'] ? date('d/m/Y', strtotime($row['fecha_envio'])) : 'Sin fecha';
        $pdf2->Cell(20, 8, $row['id'], 1, 0, 'C');
        $pdf2->Cell(80, 8, substr($row['curso_nombre'] ?? 'Sin curso', 0, 30), 1, 0);
        $pdf2->Cell(40, 8, $fecha, 1, 0, 'C');
        $pdf2->Cell(30, 8, $row['total_respuestas'], 1, 1, 'C');
    }
    
    $archivo_datos = __DIR__ . '/pdf/test_datos_reales.pdf';
    $pdf2->Output($archivo_datos, 'F');
    echo "   ✓ PDF con datos reales guardado en: $archivo_datos\n\n";
    
    // 6. PROBAR ReportePdfGenerator
    echo "6. Probando la clase ReportePdfGenerator...\n";
    
    try {
        require_once __DIR__ . '/pdf/ReportePdfGenerator.php';
        
        $generator = new ReportePdfGenerator();
        echo "   ✓ ReportePdfGenerator instanciado correctamente\n";
        
        // Intentar generar un reporte básico
        echo "   → Generando reporte básico...\n";
        $pdf_content = $generator->generarReporte(['estadisticas']);
        
        $archivo_reporte = __DIR__ . '/pdf/test_reporte_generator.pdf';
        file_put_contents($archivo_reporte, $pdf_content);
        echo "   ✓ Reporte básico guardado en: $archivo_reporte\n";
        
        // Intentar generar un reporte completo
        echo "   → Generando reporte completo...\n";
        $pdf_completo = $generator->generarReporte(['estadisticas', 'graficos', 'comentarios']);
        
        $archivo_completo = __DIR__ . '/pdf/test_reporte_completo.pdf';
        file_put_contents($archivo_completo, $pdf_completo);
        echo "   ✓ Reporte completo guardado en: $archivo_completo\n";
        
    } catch (Exception $e) {
        echo "   ✗ Error con ReportePdfGenerator: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n";
        echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
    }
    
    echo "\n";
    
    // 7. VERIFICAR ARCHIVOS GENERADOS
    echo "7. Verificando archivos PDF generados...\n";
    
    $archivos_esperados = [
        'test_tcpdf_basico.pdf' => 'Test básico de TCPDF',
        'test_datos_reales.pdf' => 'PDF con datos reales de BD',
        'test_reporte_generator.pdf' => 'Reporte básico con ReportePdfGenerator',
        'test_reporte_completo.pdf' => 'Reporte completo con ReportePdfGenerator'
    ];
    
    foreach ($archivos_esperados as $archivo => $descripcion) {
        $ruta_completa = __DIR__ . '/pdf/' . $archivo;
        if (file_exists($ruta_completa)) {
            $tamaño = filesize($ruta_completa);
            echo "   ✓ $archivo ($tamaño bytes) - $descripcion\n";
        } else {
            echo "   ✗ $archivo - NO GENERADO - $descripcion\n";
        }
    }
    
    echo "\n=== RESUMEN DEL TEST ===\n";
    echo "Test completado. Revisa manualmente los archivos PDF generados.\n";
    echo "Si los PDFs están en blanco o corruptos, hay un problema con la generación.\n";
    echo "Si los PDFs se ven correctos, el sistema funciona bien.\n\n";
    
    echo "PASOS PARA VERIFICAR:\n";
    echo "1. Abre cada archivo PDF con un visor\n";
    echo "2. Verifica que el contenido sea legible\n";
    echo "3. Confirma que las tablas se muestren bien\n";
    echo "4. Reporta cualquier PDF vacío o corrupto\n";
    
} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>
