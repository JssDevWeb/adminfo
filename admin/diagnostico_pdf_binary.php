<?php
/**
 * Herramienta de diagnóstico para PDFs
 * Examina el contenido binario y la estructura de un archivo PDF
 */

// Mostrar todos los errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Archivos PDF</h1>";

// Función para analizar un archivo PDF
function analizarPdf($archivo) {
    echo "<h2>Analizando: $archivo</h2>";
    
    if (!file_exists($archivo)) {
        echo "<p style='color:red'>ERROR: El archivo no existe</p>";
        return;
    }
    
    $tamaño = filesize($archivo);
    echo "<p>Tamaño: $tamaño bytes (" . round($tamaño/1024, 2) . " KB)</p>";
    
    // Leer inicio del archivo para verificar cabecera PDF
    $contenido = file_get_contents($archivo, false, null, 0, 1024);
    
    // Revisar firma PDF
    echo "<h3>Verificación de firma PDF:</h3>";
    if (substr($contenido, 0, 4) === '%PDF') {
        echo "<p style='color:green'>✓ Archivo comienza con la firma PDF correcta (%PDF)</p>";
        echo "<p>Versión PDF: " . substr($contenido, 5, 3) . "</p>";
    } else {
        echo "<p style='color:red'>✗ El archivo NO comienza con la firma PDF correcta</p>";
        echo "<p>Los primeros bytes (hex): " . bin2hex(substr($contenido, 0, 20)) . "</p>";
        echo "<p>Los primeros bytes (texto): " . htmlspecialchars(substr($contenido, 0, 20)) . "</p>";
    }
    
    // Buscar fin de archivo (%%EOF)
    $fin = file_get_contents($archivo, false, null, $tamaño - 30, 30);
    if (strpos($fin, '%%EOF') !== false) {
        echo "<p style='color:green'>✓ Se encontró la marca de fin de archivo (%%EOF)</p>";
    } else {
        echo "<p style='color:red'>✗ NO se encontró la marca de fin de archivo (%%EOF)</p>";
    }
    
    // Verificar si tiene contenido extra
    echo "<h3>Análisis de contenido:</h3>";
    
    // Detectar si hay texto HTML antes o después
    $completo = file_get_contents($archivo);
    if (strpos($completo, '<html') !== false || strpos($completo, '<body') !== false) {
        echo "<p style='color:red'>⚠ El archivo contiene etiquetas HTML, lo que puede corromper el PDF</p>";
        
        // Mostrar ubicación de la etiqueta HTML
        $pos_html = strpos($completo, '<html');
        if ($pos_html !== false) {
            echo "<p>Etiqueta &lt;html&gt; encontrada en posición: $pos_html</p>";
        }
    }
    
    // Comprobación de contenido PHP o texto antes del PDF
    if (substr($completo, 0, 5) !== '%PDF-') {
        echo "<p style='color:red'>⚠ El archivo contiene texto o datos antes de la firma PDF, lo que corrompe el archivo</p>";
        $texto_antes = substr($completo, 0, strpos($completo, '%PDF'));
        echo "<p>Texto antes de %PDF: " . htmlspecialchars(substr($texto_antes, 0, 100)) . "</p>";
    }
    
    // Proporcionar enlaces para descarga y visualización
    echo "<div style='margin-top:20px'>";
    echo "<a href='$archivo' download style='padding:10px; background:#007bff; color:white; text-decoration:none; margin-right:10px;'>Descargar Archivo</a>";
    echo "</div>";
}

// Obtener archivos PDF disponibles en el directorio actual y en /pdf
$pdfs_actuales = glob('*.pdf');
$pdfs_en_directorio = glob('pdf/*.pdf');

$todos_pdfs = array_merge($pdfs_actuales, $pdfs_en_directorio);

// Si no hay PDFs, mostrar mensaje
if (empty($todos_pdfs)) {
    echo "<p>No se encontraron archivos PDF para analizar.</p>";
}

// Mostrar lista de PDFs disponibles
echo "<h2>PDFs disponibles:</h2>";
echo "<ul>";
foreach ($todos_pdfs as $pdf) {
    echo "<li><a href='?analizar=" . urlencode($pdf) . "'>" . htmlspecialchars($pdf) . "</a></li>";
}
echo "</ul>";

// Analizar un PDF específico si se solicita
if (isset($_GET['analizar'])) {
    $archivo = $_GET['analizar'];
    analizarPdf($archivo);
} else if (!empty($todos_pdfs)) {
    // Por defecto, analizar el primer PDF disponible
    analizarPdf($todos_pdfs[0]);
}

// Formulario para generar un PDF de prueba
echo "<h2>Generar PDF de Prueba Básico</h2>";
echo "<form action='test_final_completo.php' method='post'>";
echo "<button type='submit'>Generar PDF de Prueba</button>";
echo "</form>";

// Finalizar página
echo "<hr>";
echo "<p><small>Herramienta de diagnóstico de PDF v1.0 - " . date('Y-m-d H:i:s') . "</small></p>";
?>
