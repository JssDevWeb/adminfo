<?php
echo "=== GENERACIÓN FINAL DE PDF CON DATOS CORRECTOS ===\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    // Obtener conexión a la base de datos
    $db = Database::getInstance()->getConnection();
    
    // Crear generador con la conexión
    $generator = new ReportePdfGenerator($db);
    
    echo "Generando PDF final con todos los datos corregidos...\n";
    
    // Usar datos reales: Curso 3 (Física General), fecha 2025-06-20
    $curso_id = 3;
    $fecha = '2025-06-20';
    
    // Todas las secciones
    $secciones = [
        'resumen_ejecutivo',
        'graficos_evaluacion', 
        'estadisticas_detalladas',
        'comentarios_curso',
        'preguntas_criticas'
    ];
    
    echo "→ Curso ID: $curso_id\n";
    echo "→ Fecha: $fecha\n";
    echo "→ Secciones: " . implode(', ', $secciones) . "\n\n";
    
    // Generar el contenido del PDF
    $pdf_content = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, []);
    
    if (!$pdf_content) {
        throw new Exception("Error: No se generó contenido para el PDF");
    }
    
    // Guardar el PDF en un archivo
    $archivo_final = __DIR__ . '/pdf/REPORTE_FINAL_CORREGIDO.pdf';
    
    if (file_put_contents($archivo_final, $pdf_content) === false) {
        throw new Exception("Error al guardar el PDF en: $archivo_final");
    }
    
    $tamaño = filesize($archivo_final);
    echo "✅ PDF FINAL GENERADO EXITOSAMENTE\n";
    echo "   - Tamaño: $tamaño bytes\n";
    echo "   - Archivo: $archivo_final\n\n";
    
    // Verificar contenido específico del PDF generado
    echo "📋 VERIFICACIÓN DEL CONTENIDO:\n";
    
    if ($tamaño > 10000) {
        echo "✓ El PDF tiene un tamaño adecuado (>10KB), indica contenido sustancial\n";
    } else {
        echo "⚠ El PDF es pequeño (<10KB), puede faltar contenido\n";
    }
    
    // Verificar estructura básica del PDF
    $primeros_bytes = file_get_contents($archivo_final, false, null, 0, 10);
    
    if (substr($primeros_bytes, 0, 4) === '%PDF') {
        echo "✓ El archivo comienza con la firma PDF correcta (%PDF)\n";
    } else {
        echo "✗ El archivo NO comienza con la firma PDF correcta. Primeros bytes: " . bin2hex(substr($primeros_bytes, 0, 10)) . "\n";
    }
    
    echo "\n📊 ESTADÍSTICAS ADICIONALES:\n";
    echo "   - MD5: " . md5_file($archivo_final) . "\n";
    echo "   - Fecha generación: " . date('Y-m-d H:i:s') . "\n";
    
    echo "\nSe recomienda abrir el PDF para verificar su contenido visual.\n";
    echo "Ubicación: " . realpath($archivo_final) . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . " (Línea " . $e->getLine() . ")\n";
    
    // Mostrar trace para depuración
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
