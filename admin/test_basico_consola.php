<?php
/**
 * Test básico de TCPDF y ReportePdfGenerator desde consola
 */

echo "=== TEST BÁSICO DE PDF - CONSOLA ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Verificar que TCPDF se puede cargar
echo "1. Verificando TCPDF...\n";
try {
    require_once '../vendor/autoload.php';
    $tcpdf = new TCPDF();
    echo "   ✅ TCPDF cargado correctamente\n";
} catch (Exception $e) {
    echo "   ❌ Error cargando TCPDF: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar conexión a base de datos
echo "\n2. Verificando conexión a base de datos...\n";
try {
    require_once '../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "   ✅ Conexión a BD establecida\n";
} catch (Exception $e) {
    echo "   ❌ Error conectando a BD: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar datos de prueba
echo "\n3. Verificando datos de prueba...\n";
try {
    $curso_id = 9;
    $fecha = '2025-06-20';
    
    // Verificar curso
    $stmt = $pdo->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch();
    
    if ($curso) {
        echo "   ✅ Curso encontrado: " . $curso['nombre'] . "\n";
    } else {
        echo "   ❌ Curso no encontrado (ID: $curso_id)\n";
        exit(1);
    }
    
    // Verificar encuestas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM encuestas WHERE curso_id = :curso_id AND DATE(fecha_envio) = :fecha");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $total_encuestas = $stmt->fetchColumn();
    
    echo "   📊 Encuestas encontradas: $total_encuestas\n";
    
    if ($total_encuestas == 0) {
        echo "   ⚠️  No hay encuestas para esta fecha, pero continuamos...\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error verificando datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Crear PDF básico con TCPDF directo
echo "\n4. Creando PDF básico con TCPDF...\n";
try {
    $pdf = new TCPDF();
    $pdf->SetCreator('Test Sistema');
    $pdf->SetAuthor('Test');
    $pdf->SetTitle('Test PDF');
    
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'TEST PDF BÁSICO', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Este es un test básico de TCPDF', 0, 1, 'L');
    $pdf->Cell(0, 10, 'Fecha: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Curso: ' . $curso['nombre'], 0, 1, 'L');
    
    // Generar PDF
    $pdf_content = $pdf->Output('', 'S');
    
    if ($pdf_content && strlen($pdf_content) > 1000) {
        echo "   ✅ PDF básico generado (" . number_format(strlen($pdf_content)) . " bytes)\n";
    } else {
        echo "   ❌ PDF básico falló o muy pequeño\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "   ❌ Error creando PDF básico: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Cargar ReportePdfGenerator
echo "\n5. Cargando ReportePdfGenerator...\n";
try {
    require_once 'pdf/ReportePdfGenerator.php';
    $generator = new ReportePdfGenerator();
    echo "   ✅ ReportePdfGenerator cargado\n";
} catch (Exception $e) {
    echo "   ❌ Error cargando ReportePdfGenerator: " . $e->getMessage() . "\n";
    echo "   🔍 Línea: " . $e->getLine() . "\n";
    echo "   🔍 Archivo: " . $e->getFile() . "\n";
    exit(1);
}

// Test 6: Intentar generar reporte
echo "\n6. Intentando generar reporte...\n";
try {
    $secciones = ['graficos_evaluacion'];
    $imagenes = [];
    
    echo "   🔄 Llamando a generarReportePorCursoFecha()...\n";
    $pdf_result = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, $imagenes);
    
    if ($pdf_result && strlen($pdf_result) > 1000) {
        echo "   ✅ Reporte generado exitosamente (" . number_format(strlen($pdf_result)) . " bytes)\n";
        
        // Guardar para inspección
        file_put_contents('test_output.pdf', $pdf_result);
        echo "   💾 PDF guardado como: test_output.pdf\n";
        
    } else {
        echo "   ❌ Reporte falló o muy pequeño\n";
        echo "   🔍 Tamaño resultado: " . strlen($pdf_result ?? '') . " bytes\n";
        echo "   🔍 Tipo resultado: " . gettype($pdf_result) . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error generando reporte: " . $e->getMessage() . "\n";
    echo "   🔍 Línea: " . $e->getLine() . "\n";
    echo "   🔍 Archivo: " . $e->getFile() . "\n";
    
    // Mostrar stack trace para debug
    echo "\n   📋 Stack Trace:\n";
    foreach ($e->getTrace() as $i => $trace) {
        echo "      $i. " . ($trace['file'] ?? 'N/A') . ":" . ($trace['line'] ?? 'N/A') . " " . ($trace['function'] ?? 'N/A') . "()\n";
    }
}

echo "\n=== FIN DEL TEST ===\n";
?>
