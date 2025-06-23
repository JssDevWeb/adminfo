<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 DEBUG - Test PDF Simple</h2>";

try {
    require_once '../config/database.php';
    require_once 'pdf/ReportePdfGenerator.php';
    
    echo "<p>✅ Archivos cargados correctamente</p>";
    
    // Crear instancia
    $generator = new ReportePdfGenerator();
    echo "<p>✅ Generator creado</p>";
    
    // Parámetros simples
    $curso_id = 3; // Física General
    $fecha = '2025-06-20';
    $secciones = ['estadisticas_detalladas'];
    
    echo "<p>📊 Probando con:</p>";
    echo "<ul>";
    echo "<li>Curso ID: $curso_id</li>";
    echo "<li>Fecha: $fecha</li>";
    echo "<li>Secciones: " . implode(', ', $secciones) . "</li>";
    echo "</ul>";
    
    // Verificar conexión a BD
    echo "<h3>🗄️ Verificando Base de Datos</h3>";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT id, nombre FROM cursos WHERE id = ?");
    $stmt->execute([$curso_id]);
    $curso = $stmt->fetch();
    
    if ($curso) {
        echo "<p>✅ Curso encontrado: " . htmlspecialchars($curso['nombre']) . "</p>";
    } else {
        echo "<p>❌ Curso no encontrado</p>";
        exit;
    }
    
    // Verificar encuestas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM encuestas WHERE curso_id = ? AND DATE(fecha_envio) = ?");
    $stmt->execute([$curso_id, $fecha]);
    $total = $stmt->fetchColumn();
    echo "<p>📋 Encuestas encontradas: $total</p>";
    
    if ($total == 0) {
        echo "<p>⚠️ No hay encuestas, pero continuamos...</p>";
    }
    
    echo "<h3>🔄 Generando PDF...</h3>";
    
    // Generar PDF con manejo de errores detallado
    ob_start();
    $pdf_content = $generator->generarReportePorCursoFecha($curso_id, $fecha, $secciones, []);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>⚠️ Salida capturada durante la generación:</h4>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    }
    
    if ($pdf_content && strlen($pdf_content) > 1000) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✅ PDF Generado Exitosamente</h4>";
        echo "<p><strong>Tamaño:</strong> " . number_format(strlen($pdf_content)) . " bytes</p>";
        echo "<p><strong>Tipo:</strong> " . (strpos($pdf_content, '%PDF') === 0 ? 'PDF válido' : 'Contenido inválido') . "</p>";
        echo "</div>";
        
        // Guardar temporalmente para descarga
        $temp_file = 'temp_pdf_debug.pdf';
        file_put_contents($temp_file, $pdf_content);
        
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='$temp_file' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
        echo "📥 Ver PDF Generado";
        echo "</a>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>❌ Error en la Generación</h4>";
        if ($pdf_content) {
            echo "<p><strong>Tamaño:</strong> " . strlen($pdf_content) . " bytes (muy pequeño)</p>";
            echo "<p><strong>Contenido:</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($pdf_content, 0, 500)) . "</pre>";
        } else {
            echo "<p>El PDF está vacío o es null</p>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ Excepción Capturada</h4>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}
h2, h3, h4 {
    color: #333;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
    max-height: 300px;
    overflow-y: auto;
}
</style>
