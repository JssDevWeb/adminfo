<?php
/**
 * Script de Corrección de Codificación
 * Corrige los caracteres especiales en las preguntas
 */

// Configuración
require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>🔧 CORRECCIÓN DE CODIFICACIÓN</h2>\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Configurar charset UTF-8 para la sesión
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<h3>1. Eliminando preguntas con codificación incorrecta...</h3>\n";
    
    // Eliminar todas las preguntas existentes
    $pdo->exec("DELETE FROM preguntas");
    $pdo->exec("ALTER TABLE preguntas AUTO_INCREMENT = 1");
    
    echo "✅ Preguntas anteriores eliminadas<br>\n";
    
    echo "<h3>2. Insertando preguntas con codificación correcta...</h3>\n";    // Insertar preguntas con codificación UTF-8 correcta
    $preguntas = [
        [
            'texto' => '¿Cómo califica el contenido general del curso?',
            'tipo' => 'escala',
            'seccion' => 'curso',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 1
        ],
        [
            'texto' => '¿Qué tan clara fue la metodología utilizada en clase?',
            'tipo' => 'escala',
            'seccion' => 'curso',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 2
        ],
        [
            'texto' => '¿Cómo evalúa los recursos didácticos utilizados?',
            'tipo' => 'escala',
            'seccion' => 'curso',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 3
        ],
        [
            'texto' => '¿Qué tan útil considera este curso para su formación profesional?',
            'tipo' => 'escala',
            'seccion' => 'curso',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 4
        ],
        [
            'texto' => 'Comentarios adicionales sobre el curso',
            'tipo' => 'texto',
            'seccion' => 'curso',
            'es_obligatoria' => 0,
            'activa' => 1,
            'orden' => 5
        ],
        [
            'texto' => '¿Cómo califica la puntualidad del profesor?',
            'tipo' => 'escala',
            'seccion' => 'profesor',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 6
        ],
        [
            'texto' => '¿Qué tan claro fue el profesor al explicar los temas?',
            'tipo' => 'escala',
            'seccion' => 'profesor',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 7
        ],
        [
            'texto' => '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?',
            'tipo' => 'escala',
            'seccion' => 'profesor',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 8
        ],
        [
            'texto' => '¿Qué tan justo considera el sistema de evaluación del profesor?',
            'tipo' => 'escala',
            'seccion' => 'profesor',
            'es_obligatoria' => 1,
            'activa' => 1,
            'orden' => 9
        ],
        [
            'texto' => 'Comentarios adicionales sobre el profesor',
            'tipo' => 'texto',
            'seccion' => 'profesor',
            'es_obligatoria' => 0,
            'activa' => 1,
            'orden' => 10
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO preguntas (texto, tipo, seccion, es_obligatoria, activa, orden) 
        VALUES (:texto, :tipo, :seccion, :es_obligatoria, :activa, :orden)
    ");
    
    $contador = 0;
    foreach ($preguntas as $pregunta) {
        $stmt->execute($pregunta);
        $contador++;
        echo "✅ Pregunta $contador: " . $pregunta['texto'] . "<br>\n";
    }
    
    echo "<h3>3. Verificación final...</h3>\n";
    
    // Verificar las preguntas insertadas
    $stmt = $pdo->query("SELECT id, texto, tipo, seccion FROM preguntas ORDER BY id");
    $preguntas_verificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Texto</th><th>Tipo</th><th>Sección</th></tr>\n";
    
    foreach ($preguntas_verificacion as $pregunta) {
        echo "<tr>";
        echo "<td>" . $pregunta['id'] . "</td>";
        echo "<td>" . htmlspecialchars($pregunta['texto'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . $pregunta['tipo'] . "</td>";
        echo "<td>" . $pregunta['seccion'] . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h3>🎉 CORRECCIÓN COMPLETADA</h3>\n";
    echo "<p>✅ Se insertaron correctamente $contador preguntas con codificación UTF-8</p>\n";
    echo "<p>✅ Los caracteres especiales (ñ, tildes, signos) ahora se muestran correctamente</p>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>
