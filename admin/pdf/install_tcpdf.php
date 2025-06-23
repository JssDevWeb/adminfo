<?php
/**
 * Script de instalación de TCPDF
 * Descarga e instala TCPDF automáticamente
 */

echo "<h2>🔧 INSTALANDO TCPDF</h2>\n";

$tcpdf_dir = __DIR__ . '/tcpdf';
$tcpdf_url = 'https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip';
$zip_file = __DIR__ . '/tcpdf.zip';

try {
    // Verificar si ya está instalado
    if (is_dir($tcpdf_dir) && file_exists($tcpdf_dir . '/tcpdf.php')) {
        echo "✅ TCPDF ya está instalado en: $tcpdf_dir<br>\n";
        echo "<p><a href='test_pdf.php'>🧪 Hacer prueba de PDF</a></p>\n";
        exit();
    }
    
    echo "📥 Descargando TCPDF desde GitHub...<br>\n";
    
    // Descargar usando file_get_contents (método simple)
    $zip_content = file_get_contents($tcpdf_url);
    
    if ($zip_content === false) {
        throw new Exception("No se pudo descargar TCPDF desde: $tcpdf_url");
    }
    
    echo "💾 Guardando archivo ZIP...<br>\n";
    file_put_contents($zip_file, $zip_content);
    
    echo "📦 Extrayendo archivos...<br>\n";
    
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        // Extraer a directorio temporal
        $temp_dir = __DIR__ . '/temp_tcpdf';
        $zip->extractTo($temp_dir);
        $zip->close();
        
        // Mover archivos a directorio final
        $extracted_dir = $temp_dir . '/TCPDF-main';
        if (is_dir($extracted_dir)) {
            rename($extracted_dir, $tcpdf_dir);
            
            // Limpiar archivos temporales
            unlink($zip_file);
            rmdir($temp_dir);
            
            echo "✅ TCPDF instalado exitosamente en: $tcpdf_dir<br>\n";
            echo "📁 Archivos principales encontrados:<br>\n";
            
            $files_to_check = ['tcpdf.php', 'config/tcpdf_config.php', 'fonts/'];
            foreach ($files_to_check as $file) {
                $path = $tcpdf_dir . '/' . $file;
                if (file_exists($path)) {
                    echo "  ✅ $file<br>\n";
                } else {
                    echo "  ❌ $file (no encontrado)<br>\n";
                }
            }
            
            echo "<h3>🎉 Instalación completada</h3>\n";
            echo "<p><a href='test_pdf.php'>🧪 Hacer prueba de PDF</a></p>\n";
            
        } else {
            throw new Exception("No se encontró el directorio extraído: $extracted_dir");
        }
    } else {
        throw new Exception("No se pudo abrir el archivo ZIP");
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error en la instalación</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    
    echo "<h4>📝 Instalación manual alternativa:</h4>\n";
    echo "<ol>\n";
    echo "<li>Descarga TCPDF desde: <a href='https://tcpdf.org/' target='_blank'>https://tcpdf.org/</a></li>\n";
    echo "<li>Extrae los archivos en: <code>" . __DIR__ . "/tcpdf/</code></li>\n";
    echo "<li>Asegúrate de que existe el archivo: <code>" . __DIR__ . "/tcpdf/tcpdf.php</code></li>\n";
    echo "<li>Vuelve a cargar esta página</li>\n";
    echo "</ol>\n";
}
?>
