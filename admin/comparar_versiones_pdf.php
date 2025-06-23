<?php
/**
 * Script para comparar la versión original y la optimizada de los PDFs
 */

echo "<h1>Comparación de PDFs: Original vs Optimizado</h1>";

// Verificar si los archivos existen
$pdf_original = "final_graficos_evaluacion.pdf";
$pdf_optimizado = "final_graficos_evaluacion_optimizado.pdf";

$original_existe = file_exists($pdf_original);
$optimizado_existe = file_exists($pdf_optimizado);

echo "<div style='display:flex; flex-direction:column; gap:20px;'>";

// Mostrar mensaje de estado
if (!$original_existe && !$optimizado_existe) {
    echo "<p style='color:red'>No se encontraron archivos PDF para comparar.</p>";
    echo "<p>Ejecute primero <a href='test_mejoras_graficos_estilos.php'>test_mejoras_graficos_estilos.php</a> para generar el PDF original.</p>";
    echo "<p>Luego ejecute <a href='test_optimizacion_graficos.php'>test_optimizacion_graficos.php</a> para generar el PDF optimizado.</p>";
} else {
    if ($original_existe) {
        echo "<div>";
        echo "<h2>Versión Original</h2>";
        echo "<a href='$pdf_original' target='_blank'>Abrir en nueva ventana</a>";
        echo "<iframe src='$pdf_original' width='100%' height='500px'></iframe>";
        echo "</div>";
    } else {
        echo "<div>";
        echo "<h2>Versión Original</h2>";
        echo "<p style='color:orange'>El archivo original no existe. Ejecute test_mejoras_graficos_estilos.php primero.</p>";
        echo "</div>";
    }
    
    if ($optimizado_existe) {
        echo "<div>";
        echo "<h2>Versión Optimizada</h2>";
        echo "<a href='$pdf_optimizado' target='_blank'>Abrir en nueva ventana</a>";
        echo "<iframe src='$pdf_optimizado' width='100%' height='500px'></iframe>";
        echo "</div>";
    } else {
        echo "<div>";
        echo "<h2>Versión Optimizada</h2>";
        echo "<p style='color:orange'>El archivo optimizado no existe. Ejecute test_optimizacion_graficos.php primero.</p>";
        echo "</div>";
    }
}

echo "</div>";

// Añadir links para generar los PDFs si no existen
echo "<div style='margin-top: 20px;'>";
echo "<h3>Generar PDFs:</h3>";
echo "<ul>";
echo "<li><a href='test_mejoras_graficos_estilos.php'>Generar PDF Original</a></li>";
echo "<li><a href='test_optimizacion_graficos.php'>Generar PDF Optimizado</a></li>";
echo "</ul>";
echo "</div>";
?>
