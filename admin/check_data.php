<?php
require_once '../config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query('SELECT id, nombre FROM cursos LIMIT 5');
    $cursos = $stmt->fetchAll();
    echo 'Cursos disponibles:' . PHP_EOL;
    foreach ($cursos as $curso) {
        echo '- ID: ' . $curso['id'] . ' | Nombre: ' . $curso['nombre'] . PHP_EOL;
    }
    
    // También vamos a ver fechas de encuestas disponibles
    $stmt = $db->query('SELECT DISTINCT DATE(fecha_envio) as fecha FROM encuestas ORDER BY fecha DESC LIMIT 5');
    $fechas = $stmt->fetchAll();
    echo PHP_EOL . 'Fechas de encuestas disponibles:' . PHP_EOL;
    foreach ($fechas as $fecha) {
        echo '- ' . $fecha['fecha'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
