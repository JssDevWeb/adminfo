<?php
/**
 * CORRECCIÓN MASIVA DE CODIFICACIÓN
 * Corrige todos los datos de la base de datos con caracteres especiales
 */

// Configuración
require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>🔧 CORRECCIÓN MASIVA DE CODIFICACIÓN</h2>\n";
    echo "<p><strong>Corrigiendo todos los datos de la base de datos...</strong></p>\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Configurar charset UTF-8 para la sesión
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<h3>1. Limpiando y reinsertando datos...</h3>\n";
      // ==========================================
    // LIMPIAR TODA LA BASE DE DATOS
    // ==========================================
    echo "🗑️ Eliminando todos los datos anteriores...<br>\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM respuestas");
    $pdo->exec("DELETE FROM encuestas");
    $pdo->exec("DELETE FROM preguntas");
    $pdo->exec("DELETE FROM curso_profesores");
    $pdo->exec("DELETE FROM formularios");
    $pdo->exec("DELETE FROM profesores");
    $pdo->exec("DELETE FROM cursos");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Resetear AUTO_INCREMENT
    $pdo->exec("ALTER TABLE cursos AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE profesores AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE formularios AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE preguntas AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE curso_profesores AUTO_INCREMENT = 1");
    
    echo "✅ Datos anteriores eliminados<br>\n";
    
    // ==========================================
    // INSERTAR CURSOS CON CODIFICACIÓN CORRECTA
    // ==========================================
    echo "<h3>2. Insertando cursos...</h3>\n";
    
    $cursos = [
        ['nombre' => 'Matemáticas Avanzadas', 'codigo' => 'MAT301', 'descripcion' => 'Curso avanzado de cálculo diferencial e integral con aplicaciones en ingeniería y ciencias.', 'creditos' => 4],
        ['nombre' => 'Programación Web', 'codigo' => 'INF201', 'descripcion' => 'Desarrollo de aplicaciones web modernas con HTML5, CSS3, JavaScript y frameworks actuales.', 'creditos' => 3],
        ['nombre' => 'Física General', 'codigo' => 'FIS101', 'descripcion' => 'Fundamentos de mecánica clásica, termodinámica y electromagnetismo aplicado.', 'creditos' => 4],
        ['nombre' => 'Química Orgánica', 'codigo' => 'QUI202', 'descripcion' => 'Estudio de compuestos orgánicos, reacciones y mecanismos de síntesis química.', 'creditos' => 3],
        ['nombre' => 'Estadística Aplicada', 'codigo' => 'EST301', 'descripcion' => 'Análisis estadístico, probabilidad y métodos de investigación cuantitativa.', 'creditos' => 3],
        ['nombre' => 'Historia Contemporánea', 'codigo' => 'HIS101', 'descripcion' => 'Análisis de eventos históricos del siglo XX y su impacto en la sociedad actual.', 'creditos' => 2],
        ['nombre' => 'Inglés Avanzado', 'codigo' => 'ING301', 'descripcion' => 'Desarrollo de habilidades comunicativas avanzadas en inglés técnico y académico.', 'creditos' => 2],
        ['nombre' => 'Microeconomía', 'codigo' => 'ECO201', 'descripcion' => 'Teoría microeconómica, comportamiento del consumidor y análisis de mercados.', 'creditos' => 3]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO cursos (nombre, codigo, descripcion, creditos) VALUES (:nombre, :codigo, :descripcion, :creditos)");
    
    foreach ($cursos as $curso) {
        $stmt->execute($curso);
        echo "✅ Curso: " . $curso['nombre'] . "<br>\n";
    }
    
    // ==========================================
    // INSERTAR PROFESORES CON CODIFICACIÓN CORRECTA
    // ==========================================
    echo "<h3>3. Insertando profesores...</h3>\n";
    
    $profesores = [
        ['nombre' => 'Dr. María González López', 'email' => 'maria.gonzalez@universidad.edu', 'especialidad' => 'Matemáticas Aplicadas'],
        ['nombre' => 'Ing. Carlos Rodríguez Silva', 'email' => 'carlos.rodriguez@universidad.edu', 'especialidad' => 'Desarrollo de Software'],
        ['nombre' => 'Dra. Ana Martínez Pérez', 'email' => 'ana.martinez@universidad.edu', 'especialidad' => 'Física Teórica'],
        ['nombre' => 'Dr. José Luis Hernández', 'email' => 'jose.hernandez@universidad.edu', 'especialidad' => 'Química Orgánica'],
        ['nombre' => 'Mtra. Laura Sánchez Ruiz', 'email' => 'laura.sanchez@universidad.edu', 'especialidad' => 'Estadística'],
        ['nombre' => 'Dr. Francisco Jiménez Torres', 'email' => 'francisco.jimenez@universidad.edu', 'especialidad' => 'Historia Contemporánea'],
        ['nombre' => 'Prof. Patricia López García', 'email' => 'patricia.lopez@universidad.edu', 'especialidad' => 'Lingüística Aplicada'],
        ['nombre' => 'Dr. Roberto Díaz Morales', 'email' => 'roberto.diaz@universidad.edu', 'especialidad' => 'Economía'],
        ['nombre' => 'Dra. Carmen Vásquez Ruiz', 'email' => 'carmen.vasquez@universidad.edu', 'especialidad' => 'Análisis Matemático'],
        ['nombre' => 'Ing. Miguel Ángel Fernández', 'email' => 'miguel.fernandez@universidad.edu', 'especialidad' => 'Tecnologías Web'],
        ['nombre' => 'Dr. Elena Moreno Castro', 'email' => 'elena.moreno@universidad.edu', 'especialidad' => 'Física Experimental'],
        ['nombre' => 'Dra. Sofía Ramírez Ortega', 'email' => 'sofia.ramirez@universidad.edu', 'especialidad' => 'Química Analítica'],
        ['nombre' => 'Prof. David Gutiérrez Luna', 'email' => 'david.gutierrez@universidad.edu', 'especialidad' => 'Métodos Estadísticos'],
        ['nombre' => 'Dra. Isabel Núñez Vargas', 'email' => 'isabel.nunez@universidad.edu', 'especialidad' => 'Historia Social'],
        ['nombre' => 'Prof. Antonio Méndez Cruz', 'email' => 'antonio.mendez@universidad.edu', 'especialidad' => 'Traducción e Interpretación'],
        ['nombre' => 'Dr. Alejandro Peña Soto', 'email' => 'alejandro.pena@universidad.edu', 'especialidad' => 'Teoría Económica']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO profesores (nombre, email, especialidad) VALUES (:nombre, :email, :especialidad)");
    
    foreach ($profesores as $profesor) {
        $stmt->execute($profesor);
        echo "✅ Profesor: " . $profesor['nombre'] . "<br>\n";
    }
    
    // ==========================================
    // INSERTAR FORMULARIOS CON CODIFICACIÓN CORRECTA
    // ==========================================
    echo "<h3>4. Insertando formularios...</h3>\n";
    
    $formularios = [
        ['nombre' => 'Evaluación MAT301 - Semestre 2025-1', 'curso_id' => 1, 'descripcion' => 'Formulario de evaluación para Matemáticas Avanzadas, semestre 2025-1'],
        ['nombre' => 'Evaluación INF201 - Semestre 2025-1', 'curso_id' => 2, 'descripcion' => 'Formulario de evaluación para Programación Web, semestre 2025-1'],
        ['nombre' => 'Evaluación FIS101 - Semestre 2025-1', 'curso_id' => 3, 'descripcion' => 'Formulario de evaluación para Física General, semestre 2025-1'],
        ['nombre' => 'Evaluación QUI202 - Semestre 2025-1', 'curso_id' => 4, 'descripcion' => 'Formulario de evaluación para Química Orgánica, semestre 2025-1'],
        ['nombre' => 'Evaluación EST301 - Semestre 2025-1', 'curso_id' => 5, 'descripcion' => 'Formulario de evaluación para Estadística Aplicada, semestre 2025-1'],
        ['nombre' => 'Evaluación HIS101 - Semestre 2025-1', 'curso_id' => 6, 'descripcion' => 'Formulario de evaluación para Historia Contemporánea, semestre 2025-1'],
        ['nombre' => 'Evaluación ING301 - Semestre 2025-1', 'curso_id' => 7, 'descripcion' => 'Formulario de evaluación para Inglés Avanzado, semestre 2025-1'],
        ['nombre' => 'Evaluación ECO201 - Semestre 2025-1', 'curso_id' => 8, 'descripcion' => 'Formulario de evaluación para Microeconomía, semestre 2025-1']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO formularios (nombre, curso_id, descripcion) VALUES (:nombre, :curso_id, :descripcion)");
    
    foreach ($formularios as $formulario) {
        $stmt->execute($formulario);
        echo "✅ Formulario: " . $formulario['nombre'] . "<br>\n";
    }
    
    // ==========================================
    // INSERTAR PREGUNTAS CON CODIFICACIÓN CORRECTA
    // ==========================================
    echo "<h3>5. Insertando preguntas...</h3>\n";
    
    $preguntas = [
        ['texto' => '¿Cómo califica el contenido general del curso?', 'tipo' => 'escala', 'seccion' => 'curso', 'es_obligatoria' => 1, 'orden' => 1],
        ['texto' => '¿Qué tan clara fue la metodología utilizada en clase?', 'tipo' => 'escala', 'seccion' => 'curso', 'es_obligatoria' => 1, 'orden' => 2],
        ['texto' => '¿Cómo evalúa los recursos didácticos utilizados?', 'tipo' => 'escala', 'seccion' => 'curso', 'es_obligatoria' => 1, 'orden' => 3],
        ['texto' => '¿Qué tan útil considera este curso para su formación profesional?', 'tipo' => 'escala', 'seccion' => 'curso', 'es_obligatoria' => 1, 'orden' => 4],
        ['texto' => 'Comentarios adicionales sobre el curso', 'tipo' => 'texto', 'seccion' => 'curso', 'es_obligatoria' => 0, 'orden' => 5],
        ['texto' => '¿Cómo califica la puntualidad del profesor?', 'tipo' => 'escala', 'seccion' => 'profesor', 'es_obligatoria' => 1, 'orden' => 6],
        ['texto' => '¿Qué tan claro fue el profesor al explicar los temas?', 'tipo' => 'escala', 'seccion' => 'profesor', 'es_obligatoria' => 1, 'orden' => 7],
        ['texto' => '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'tipo' => 'escala', 'seccion' => 'profesor', 'es_obligatoria' => 1, 'orden' => 8],
        ['texto' => '¿Qué tan justo considera el sistema de evaluación del profesor?', 'tipo' => 'escala', 'seccion' => 'profesor', 'es_obligatoria' => 1, 'orden' => 9],
        ['texto' => 'Comentarios adicionales sobre el profesor', 'tipo' => 'texto', 'seccion' => 'profesor', 'es_obligatoria' => 0, 'orden' => 10]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO preguntas (texto, tipo, seccion, es_obligatoria, activa, orden) VALUES (:texto, :tipo, :seccion, :es_obligatoria, 1, :orden)");
    
    foreach ($preguntas as $pregunta) {
        $stmt->execute($pregunta);
        echo "✅ Pregunta: " . $pregunta['texto'] . "<br>\n";
    }
      // ==========================================
    // INSERTAR ASIGNACIONES FORMULARIO-PROFESOR
    // ==========================================
    echo "<h3>6. Insertando asignaciones formulario-profesor...</h3>\n";
    
    $asignaciones = [
        ['formulario_id' => 1, 'profesor_id' => 1, 'orden' => 1], // Matemáticas - María González
        ['formulario_id' => 1, 'profesor_id' => 9, 'orden' => 2], // Matemáticas - Carmen Vásquez
        ['formulario_id' => 2, 'profesor_id' => 2, 'orden' => 1], // Programación - Carlos Rodríguez
        ['formulario_id' => 2, 'profesor_id' => 10, 'orden' => 2], // Programación - Miguel Fernández
        ['formulario_id' => 3, 'profesor_id' => 3, 'orden' => 1], // Física - Ana Martínez
        ['formulario_id' => 3, 'profesor_id' => 11, 'orden' => 2], // Física - Elena Moreno
        ['formulario_id' => 4, 'profesor_id' => 4, 'orden' => 1], // Química - José Hernández
        ['formulario_id' => 4, 'profesor_id' => 12, 'orden' => 2], // Química - Sofía Ramírez
        ['formulario_id' => 5, 'profesor_id' => 5, 'orden' => 1], // Estadística - Laura Sánchez
        ['formulario_id' => 5, 'profesor_id' => 13, 'orden' => 2], // Estadística - David Gutiérrez
        ['formulario_id' => 6, 'profesor_id' => 6, 'orden' => 1], // Historia - Francisco Jiménez
        ['formulario_id' => 6, 'profesor_id' => 14, 'orden' => 2], // Historia - Isabel Núñez
        ['formulario_id' => 7, 'profesor_id' => 7, 'orden' => 1], // Inglés - Patricia López
        ['formulario_id' => 7, 'profesor_id' => 15, 'orden' => 2], // Inglés - Antonio Méndez
        ['formulario_id' => 8, 'profesor_id' => 8, 'orden' => 1], // Economía - Roberto Díaz
        ['formulario_id' => 8, 'profesor_id' => 16, 'orden' => 2]  // Economía - Alejandro Peña
    ];
    
    $stmt = $pdo->prepare("INSERT INTO curso_profesores (formulario_id, profesor_id, orden) VALUES (:formulario_id, :profesor_id, :orden)");
    
    foreach ($asignaciones as $asignacion) {
        $stmt->execute($asignacion);
    }
      echo "✅ 16 asignaciones formulario-profesor creadas<br>\n";
    
    // ==========================================
    // VERIFICACIÓN FINAL
    // ==========================================
    echo "<h3>7. Verificación final...</h3>\n";
      $verificacion = [
        'cursos' => $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn(),
        'profesores' => $pdo->query("SELECT COUNT(*) FROM profesores")->fetchColumn(),
        'formularios' => $pdo->query("SELECT COUNT(*) FROM formularios")->fetchColumn(),
        'preguntas' => $pdo->query("SELECT COUNT(*) FROM preguntas")->fetchColumn(),
        'asignaciones_fp' => $pdo->query("SELECT COUNT(*) FROM curso_profesores")->fetchColumn()
    ];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>\n";
    echo "<tr><th>Tabla</th><th>Registros</th></tr>\n";
    foreach ($verificacion as $tabla => $count) {
        echo "<tr><td>$tabla</td><td>$count</td></tr>\n";
    }
    echo "</table>\n";
    
    // Mostrar algunos ejemplos de datos corregidos
    echo "<h4>Ejemplos de datos con codificación corregida:</h4>\n";
    
    echo "<strong>Cursos:</strong><br>\n";
    $cursos_ejemplo = $pdo->query("SELECT nombre, codigo FROM cursos LIMIT 3")->fetchAll();
    foreach ($cursos_ejemplo as $curso) {
        echo "• " . $curso['nombre'] . " (" . $curso['codigo'] . ")<br>\n";
    }
    
    echo "<br><strong>Profesores:</strong><br>\n";
    $profesores_ejemplo = $pdo->query("SELECT nombre, especialidad FROM profesores LIMIT 3")->fetchAll();
    foreach ($profesores_ejemplo as $profesor) {
        echo "• " . $profesor['nombre'] . " - " . $profesor['especialidad'] . "<br>\n";
    }
    
    echo "<br><strong>Preguntas:</strong><br>\n";
    $preguntas_ejemplo = $pdo->query("SELECT texto FROM preguntas LIMIT 3")->fetchAll();
    foreach ($preguntas_ejemplo as $pregunta) {
        echo "• " . $pregunta['texto'] . "<br>\n";
    }
    
    echo "<h2>🎉 CORRECCIÓN MASIVA COMPLETADA</h2>\n";
    echo "<p>✅ Toda la base de datos ha sido limpiada y repoblada con codificación UTF-8 correcta</p>\n";
    echo "<p>✅ Los caracteres especiales (ñ, tildes, signos) ahora se muestran correctamente en toda la aplicación</p>\n";
    echo "<p>✅ El sistema está listo para usar con datos profesionales y codificación correcta</p>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Traza: " . $e->getTraceAsString() . "</p>\n";
}
?>
