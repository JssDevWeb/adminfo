<?php
echo "=== GENERACIÓN FINAL DE PDF CON DATOS CORRECTOS ===\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf/ReportePdfGenerator.php';

try {
    $generator = new ReportePdfGenerator();
    
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
        'comentarios_profesores'
    ];
    
    echo "→ Curso ID: $curso_id\n";
    echo "→ Fecha: $fecha\n";
    echo "→ Secciones: " . implode(', ', $secciones) . "\n\n";
    
    $pdf_content = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, []);
    
    $archivo_final = __DIR__ . '/pdf/REPORTE_FINAL_CORREGIDO.pdf';
    file_put_contents($archivo_final, $pdf_content);
    
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
    $contenido_pdf = file_get_contents($archivo_final);
    
    $checks = [
        'Header PDF' => strpos($contenido_pdf, '%PDF-') === 0,
        'Footer PDF' => strpos($contenido_pdf, '%%EOF') !== false,
        'Título presente' => strpos($contenido_pdf, '/Title') !== false,
        'Contenido TCPDF' => strpos($contenido_pdf, '/Creator (TCPDF') !== false
    ];
    
    foreach ($checks as $check => $result) {
        echo ($result ? "✓" : "✗") . " $check\n";
    }
    
    echo "\n🎯 PASOS PARA VERIFICACIÓN MANUAL:\n";
    echo "1. Abre el archivo: $archivo_final\n";
    echo "2. Verifica que aparezcan las siguientes secciones:\n";
    echo "   - 📊 Resumen Ejecutivo con estadísticas\n";
    echo "   - 📈 Gráficos de Evaluación\n";
    echo "   - 📋 Estadísticas Detalladas con tabla\n";
    echo "   - 💬 Comentarios del Curso\n";
    echo "   - 👥 Comentarios de Profesores\n";
    echo "3. Confirma que los datos sean coherentes:\n";
    echo "   - Curso: Física General\n";
    echo "   - Fecha: 20-06-2025\n";
    echo "   - Profesores: Dra. Ana Martínez Pérez, Dr. Elena Moreno Castro\n";
    echo "   - Promedios: Entre 5.50 y 7.00\n";
    
    echo "\n🚀 Si todo se ve correcto, ¡el sistema está COMPLETAMENTE FUNCIONAL!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA GENERACIÓN FINAL ===\n";
?>
