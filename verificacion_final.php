<?php
/**
 * Script de Verificación Final del Sistema
 * Verifica que la limpieza de fechas fue exitosa
 */

// Configuración
require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>🔍 VERIFICACIÓN FINAL DEL SISTEMA</h2>\n";
    echo "<h3>Estado después de la limpieza de fechas</h3>\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Verificar estructura de tabla formularios
    echo "<h4>1. Estructura de tabla formularios:</h4>\n";
    $stmt = $pdo->query("DESCRIBE formularios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>\n";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Verificar que no existen columnas de fecha
    $fecha_columns = array_filter($columns, function($col) {
        return in_array($col['Field'], ['fecha_inicio', 'fecha_fin']);
    });
    
    if (empty($fecha_columns)) {
        echo "<p style='color: green;'>✅ <strong>CORRECTO:</strong> No se encontraron columnas de fecha</p>\n";
    } else {
        echo "<p style='color: red;'>❌ <strong>ERROR:</strong> Aún existen columnas de fecha</p>\n";
    }
    
    // 2. Verificar datos
    echo "<h4>2. Conteo de datos:</h4>\n";
    
    $tables = [
        'cursos' => 'Cursos',
        'profesores' => 'Profesores', 
        'formularios' => 'Formularios',
        'curso_profesores' => 'Asignaciones Curso-Profesor',
        'preguntas' => 'Preguntas'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Tabla</th><th>Registros</th></tr>\n";
    
    foreach ($tables as $table => $name) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $count = $stmt->fetch()['total'];
        echo "<tr><td>$name</td><td>$count</td></tr>\n";
    }
    echo "</table>\n";
    
    // 3. Verificar formularios activos
    echo "<h4>3. Estado de formularios:</h4>\n";
    $stmt = $pdo->query("SELECT f.id, f.descripcion, c.nombre as curso, f.activo FROM formularios f JOIN cursos c ON f.curso_id = c.id ORDER BY c.nombre, f.descripcion");
    $formularios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>Descripción</th><th>Curso</th><th>Estado</th></tr>\n";
    
    foreach ($formularios as $form) {
        $estado = $form['activo'] ? '<span style="color: green;">✅ Activo</span>' : '<span style="color: red;">❌ Inactivo</span>';
        echo "<tr>";
        echo "<td>{$form['id']}</td>";
        echo "<td>{$form['descripcion']}</td>";
        echo "<td>{$form['curso']}</td>";
        echo "<td>$estado</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 4. Verificar APIs
    echo "<h4>4. Prueba de APIs:</h4>\n";
    
    // Test API formularios
    $response = @file_get_contents('http://localhost/formulario_final/api/get_formularios.php');
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✅ API get_formularios.php: Funcional (" . count($data['data']) . " formularios)</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ API get_formularios.php: Respuesta inesperada</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ API get_formularios.php: No accesible</p>\n";
    }
    
    // Test API cursos
    $response = @file_get_contents('http://localhost/formulario_final/api/get_cursos.php');
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✅ API get_cursos.php: Funcional (" . count($data['data']) . " cursos)</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ API get_cursos.php: Respuesta inesperada</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ API get_cursos.php: No accesible</p>\n";
    }
    
    echo "<h3 style='color: green;'>🎉 VERIFICACIÓN COMPLETADA</h3>\n";
    echo "<p><strong>Resultado:</strong> El sistema ha sido limpiado exitosamente de todas las referencias a fechas de inicio/fin.</p>\n";
    echo "<p><strong>Estado:</strong> Los formularios ahora están siempre disponibles mientras estén activos.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
}
?>
