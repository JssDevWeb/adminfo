<?php
echo "=== TEST SIMPLE DE DEPURACIÓN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar inclusión de archivos
echo "1. Verificando archivos necesarios...\n";

$archivos_requeridos = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../config/database.php',
    __DIR__ . '/pdf/ReportePdfGenerator.php'
];

foreach ($archivos_requeridos as $archivo) {
    if (file_exists($archivo)) {
        echo "   ✓ Existe: $archivo\n";
    } else {
        echo "   ✗ Falta: $archivo\n";
    }
}

// 2. Probar inclusión
echo "\n2. Incluyendo archivos...\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "   ✓ autoload.php incluido\n";
} catch (Exception $e) {
    echo "   ✗ Error en autoload: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    echo "   ✓ database.php incluido\n";
} catch (Exception $e) {
    echo "   ✗ Error en database: " . $e->getMessage() . "\n";
    exit;
}

// 3. Probar TCPDF básico
echo "\n3. Probando TCPDF básico...\n";
try {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'TEST SIMPLE - ' . date('H:i:s'), 0, 1, 'C');
    
    $archivo = __DIR__ . '/pdf/test_simple_debug.pdf';
    $pdf->Output($archivo, 'F');
    
    if (file_exists($archivo)) {
        $tamaño = filesize($archivo);
        echo "   ✓ PDF generado: $archivo ($tamaño bytes)\n";
    } else {
        echo "   ✗ PDF no se generó\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error TCPDF: " . $e->getMessage() . "\n";
}

// 4. Probar conexión BD
echo "\n4. Probando conexión a base de datos...\n";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "   ✓ Conexión establecida\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respuestas");
    $count = $stmt->fetch()['total'];
    echo "   ✓ Respuestas en BD: $count\n";
} catch (Exception $e) {
    echo "   ✗ Error BD: " . $e->getMessage() . "\n";
}

// 5. Probar ReportePdfGenerator
echo "\n5. Probando ReportePdfGenerator...\n";
try {
    require_once __DIR__ . '/pdf/ReportePdfGenerator.php';
    echo "   ✓ Archivo incluido\n";
    
    $generator = new ReportePdfGenerator();
    echo "   ✓ Instancia creada\n";
    
    // Método simple
    $pdf_content = $generator->generarReporte([]);
    echo "   ✓ Método generarReporte() ejecutado\n";
    
    $archivo_gen = __DIR__ . '/pdf/test_generator_debug.pdf';
    file_put_contents($archivo_gen, $pdf_content);
    
    if (file_exists($archivo_gen)) {
        $tamaño = filesize($archivo_gen);
        echo "   ✓ PDF del generator guardado: ($tamaño bytes)\n";
    } else {
        echo "   ✗ PDF del generator no se guardó\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error ReportePdfGenerator: " . $e->getMessage() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
}

echo "\n=== FIN DEL TEST SIMPLE ===\n";
?>
