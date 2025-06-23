<?php
/**
 * ============================================
 * DIAGNÓSTICO DE ENCUESTAS - ANÁLISIS PROFUNDO
 * ============================================
 * Archivo para diagnosticar discrepancias entre formularios y reportes
 */

session_start();
require_once '../config/database.php';

// Configuración para mostrar errores durante diagnóstico
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = Database::getInstance()->getConnection();
    
    // Parámetros de análisis
    $curso_analizar = $_GET['curso'] ?? '';
    $fecha_analizar = $_GET['fecha'] ?? '';
    
    echo "<html><head><title>Diagnóstico de Encuestas</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body class='container py-4'>";
    
    echo "<h1><i class='bi bi-bug'></i> Diagnóstico de Encuestas</h1>";
    echo "<p class='text-muted'>Análisis profundo de discrepancias entre formularios y reportes</p>";
    
    // ============================================
    // PASO 1: MOSTRAR TODOS LOS CURSOS DISPONIBLES
    // ============================================
    echo "<div class='card mb-4'>";
    echo "<div class='card-header'><h3>📋 Paso 1: Seleccionar Curso para Análisis</h3></div>";
    echo "<div class='card-body'>";
    
    $stmt = $db->query("
        SELECT DISTINCT c.id, c.nombre, c.codigo,
               COUNT(DISTINCT f.id) as total_formularios,
               COUNT(DISTINCT e.id) as total_encuestas_todas
        FROM cursos c
        LEFT JOIN formularios f ON c.id = f.curso_id
        LEFT JOIN encuestas e ON f.id = e.formulario_id
        WHERE c.activo = 1
        GROUP BY c.id, c.nombre, c.codigo
        ORDER BY c.nombre
    ");
    $cursos = $stmt->fetchAll();
    
    echo "<div class='row'>";
    foreach ($cursos as $curso) {
        $activo = ($curso_analizar == $curso['id']) ? 'btn-primary' : 'btn-outline-primary';
        echo "<div class='col-md-4 mb-2'>";
        echo "<a href='?curso={$curso['id']}' class='btn {$activo} w-100'>";
        echo "<strong>{$curso['nombre']}</strong><br>";
        echo "<small>{$curso['codigo']} - {$curso['total_encuestas_todas']} encuestas</small>";
        echo "</a>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div></div>";
    
    // ============================================
    // PASO 2: ANÁLISIS DETALLADO DEL CURSO SELECCIONADO
    // ============================================
    if (!empty($curso_analizar)) {
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>🔍 Paso 2: Análisis Detallado del Curso</h3></div>";
        echo "<div class='card-body'>";
        
        // Información del curso
        $stmt = $db->prepare("SELECT * FROM cursos WHERE id = :curso_id");
        $stmt->execute([':curso_id' => $curso_analizar]);
        $curso_info = $stmt->fetch();
        
        echo "<h4>Curso: {$curso_info['nombre']} ({$curso_info['codigo']})</h4>";
        
        // ANÁLISIS 1: Encuestas totales vs encuestas con respuestas
        echo "<h5 class='mt-4'>📊 Análisis 1: Comparación de Encuestas</h5>";
        
        // Encuestas totales (como en formularios.php)
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT e.id) as total_encuestas_todas
            FROM formularios f
            LEFT JOIN encuestas e ON f.id = e.formulario_id
            WHERE f.curso_id = :curso_id
        ");
        $stmt->execute([':curso_id' => $curso_analizar]);
        $total_todas = $stmt->fetch()['total_encuestas_todas'];
        
        // Encuestas con respuestas (como en reportes.php)
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT e.id) as total_con_respuestas
            FROM encuestas e
            JOIN respuestas r ON e.id = r.encuesta_id
            JOIN formularios f ON e.formulario_id = f.id
            WHERE f.curso_id = :curso_id
        ");
        $stmt->execute([':curso_id' => $curso_analizar]);
        $total_con_respuestas = $stmt->fetch()['total_con_respuestas'];
        
        // Encuestas con respuestas de escala (como en sugerencias de fechas)
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT e.id) as total_con_escala
            FROM encuestas e
            JOIN respuestas r ON e.id = r.encuesta_id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            JOIN formularios f ON e.formulario_id = f.id
            WHERE f.curso_id = :curso_id
              AND pr.tipo = 'escala'
              AND (pr.seccion = 'curso' OR pr.seccion = 'profesor')
        ");
        $stmt->execute([':curso_id' => $curso_analizar]);
        $total_con_escala = $stmt->fetch()['total_con_escala'];
        
        echo "<div class='row'>";
        echo "<div class='col-md-4'>";
        echo "<div class='alert alert-info'>";
        echo "<h6>Encuestas Totales</h6>";
        echo "<h3>{$total_todas}</h3>";
        echo "<small>Como aparece en 'Formularios'</small>";
        echo "</div></div>";
        
        echo "<div class='col-md-4'>";
        echo "<div class='alert alert-warning'>";
        echo "<h6>Con Respuestas</h6>";
        echo "<h3>{$total_con_respuestas}</h3>";
        echo "<small>Que tienen al menos una respuesta</small>";
        echo "</div></div>";
        
        echo "<div class='col-md-4'>";
        echo "<div class='alert alert-success'>";
        echo "<h6>Con Respuestas de Escala</h6>";
        echo "<h3>{$total_con_escala}</h3>";
        echo "<small>Como aparece en 'Reportes'</small>";
        echo "</div></div>";
        echo "</div>";
        
        // ANÁLISIS 2: Fechas disponibles y encuestas por fecha
        echo "<h5 class='mt-4'>📅 Análisis 2: Encuestas por Fecha</h5>";
        
        $stmt = $db->prepare("
            SELECT DATE(e.fecha_envio) as fecha,
                   COUNT(DISTINCT e.id) as total_todas,
                   COUNT(DISTINCT CASE WHEN r.id IS NOT NULL THEN e.id END) as total_con_respuestas,
                   COUNT(DISTINCT CASE WHEN pr.tipo = 'escala' AND (pr.seccion = 'curso' OR pr.seccion = 'profesor') THEN e.id END) as total_con_escala
            FROM encuestas e
            JOIN formularios f ON e.formulario_id = f.id
            LEFT JOIN respuestas r ON e.id = r.encuesta_id
            LEFT JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE f.curso_id = :curso_id
              AND e.fecha_envio IS NOT NULL
            GROUP BY DATE(e.fecha_envio)
            ORDER BY DATE(e.fecha_envio) DESC
        ");
        $stmt->execute([':curso_id' => $curso_analizar]);
        $fechas_analisis = $stmt->fetchAll();
        
        if (!empty($fechas_analisis)) {
            echo "<div class='table-responsive'>";
            echo "<table class='table table-striped'>";
            echo "<thead><tr>";
            echo "<th>Fecha</th>";
            echo "<th>Total Encuestas</th>";
            echo "<th>Con Respuestas</th>";
            echo "<th>Con Escala</th>";
            echo "<th>Acciones</th>";
            echo "</tr></thead><tbody>";
            
            foreach ($fechas_analisis as $fecha_datos) {
                $fecha = $fecha_datos['fecha'];
                $clase_fila = '';
                if ($fecha_datos['total_todas'] != $fecha_datos['total_con_escala']) {
                    $clase_fila = 'table-warning';
                }
                
                echo "<tr class='{$clase_fila}'>";
                echo "<td><strong>{$fecha}</strong></td>";
                echo "<td>{$fecha_datos['total_todas']}</td>";
                echo "<td>{$fecha_datos['total_con_respuestas']}</td>";
                echo "<td>{$fecha_datos['total_con_escala']}</td>";
                echo "<td>";
                echo "<a href='?curso={$curso_analizar}&fecha={$fecha}' class='btn btn-sm btn-primary'>Analizar</a>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>No hay encuestas con fechas válidas para este curso.</div>";
        }
        
        echo "</div></div>";
    }
    
    // ============================================
    // PASO 3: ANÁLISIS ESPECÍFICO DE UNA FECHA
    // ============================================
    if (!empty($curso_analizar) && !empty($fecha_analizar)) {
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>🕵️ Paso 3: Análisis Específico - Fecha {$fecha_analizar}</h3></div>";
        echo "<div class='card-body'>";
          // Obtener todas las encuestas de esa fecha
        $stmt = $db->prepare("
            SELECT e.id, e.fecha_envio, f.nombre as formulario_nombre,
                   COUNT(DISTINCT r.id) as total_respuestas,
                   COUNT(DISTINCT CASE WHEN pr.tipo = 'escala' THEN r.id END) as respuestas_escala,
                   COUNT(DISTINCT CASE WHEN pr.seccion = 'curso' THEN r.id END) as respuestas_curso,
                   COUNT(DISTINCT CASE WHEN pr.seccion = 'profesor' THEN r.id END) as respuestas_profesor
            FROM encuestas e
            JOIN formularios f ON e.formulario_id = f.id
            LEFT JOIN respuestas r ON e.id = r.encuesta_id
            LEFT JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE f.curso_id = :curso_id
              AND DATE(e.fecha_envio) = :fecha
            GROUP BY e.id, e.fecha_envio, f.nombre
            ORDER BY e.fecha_envio DESC
        ");
        $stmt->execute([':curso_id' => $curso_analizar, ':fecha' => $fecha_analizar]);
        $encuestas_detalle = $stmt->fetchAll();
        
        if (!empty($encuestas_detalle)) {
            echo "<h5>📋 Encuestas Encontradas: " . count($encuestas_detalle) . "</h5>";
            echo "<div class='table-responsive'>";
            echo "<table class='table table-striped table-sm'>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Formulario</th><th>Fecha Envío</th>";
            echo "<th>Total Respuestas</th><th>Resp. Escala</th>";
            echo "<th>Resp. Curso</th><th>Resp. Profesor</th><th>Estado</th>";
            echo "</tr></thead><tbody>";
            
            foreach ($encuestas_detalle as $encuesta) {
                $estado = '';
                $clase = '';
                
                if ($encuesta['total_respuestas'] == 0) {
                    $estado = '❌ Sin Respuestas';
                    $clase = 'table-danger';
                } elseif ($encuesta['respuestas_escala'] == 0) {
                    $estado = '⚠️ Sin Escala';
                    $clase = 'table-warning';
                } elseif ($encuesta['respuestas_curso'] == 0 || $encuesta['respuestas_profesor'] == 0) {
                    $estado = '⚠️ Incompleta';
                    $clase = 'table-warning';
                } else {
                    $estado = '✅ Completa';
                    $clase = 'table-success';
                }
                
                echo "<tr class='{$clase}'>";
                echo "<td>{$encuesta['id']}</td>";
                echo "<td>{$encuesta['formulario_nombre']}</td>";
                echo "<td>{$encuesta['fecha_envio']}</td>";
                echo "<td>{$encuesta['total_respuestas']}</td>";
                echo "<td>{$encuesta['respuestas_escala']}</td>";
                echo "<td>{$encuesta['respuestas_curso']}</td>";
                echo "<td>{$encuesta['respuestas_profesor']}</td>";
                echo "<td>{$estado}</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
            
            // Análisis de preguntas disponibles
            echo "<h5 class='mt-4'>❓ Análisis de Preguntas del Formulario</h5>";
            $stmt = $db->prepare("
                SELECT pr.id, pr.texto, pr.tipo, pr.seccion, pr.requerida,
                       COUNT(DISTINCT r.id) as total_respuestas_pregunta
                FROM formularios f
                JOIN preguntas pr ON f.id = pr.formulario_id
                LEFT JOIN respuestas r ON pr.id = r.pregunta_id 
                    AND r.encuesta_id IN (
                        SELECT e.id FROM encuestas e 
                        WHERE e.formulario_id = f.id 
                        AND DATE(e.fecha_envio) = :fecha
                    )
                WHERE f.curso_id = :curso_id
                GROUP BY pr.id, pr.texto, pr.tipo, pr.seccion, pr.requerida
                ORDER BY pr.seccion, pr.orden
            ");
            $stmt->execute([':curso_id' => $curso_analizar, ':fecha' => $fecha_analizar]);
            $preguntas_analisis = $stmt->fetchAll();
            
            if (!empty($preguntas_analisis)) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm'>";
                echo "<thead><tr>";
                echo "<th>ID</th><th>Pregunta</th><th>Tipo</th><th>Sección</th>";
                echo "<th>Requerida</th><th>Respuestas</th>";
                echo "</tr></thead><tbody>";
                
                foreach ($preguntas_analisis as $pregunta) {
                    $clase = '';
                    if ($pregunta['requerida'] && $pregunta['total_respuestas_pregunta'] == 0) {
                        $clase = 'table-danger';
                    } elseif ($pregunta['tipo'] == 'escala' && $pregunta['total_respuestas_pregunta'] == 0) {
                        $clase = 'table-warning';
                    }
                    
                    echo "<tr class='{$clase}'>";
                    echo "<td>{$pregunta['id']}</td>";
                    echo "<td>" . substr($pregunta['texto'], 0, 50) . "...</td>";
                    echo "<td>{$pregunta['tipo']}</td>";
                    echo "<td>{$pregunta['seccion']}</td>";
                    echo "<td>" . ($pregunta['requerida'] ? '✅' : '❌') . "</td>";
                    echo "<td>{$pregunta['total_respuestas_pregunta']}</td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
                echo "</div>";
            }
            
        } else {
            echo "<div class='alert alert-warning'>No se encontraron encuestas para esta fecha.</div>";
        }
        
        echo "</div></div>";
    }
    
    echo "<div class='alert alert-info mt-4'>";
    echo "<h5>📖 Leyenda:</h5>";
    echo "<ul>";
    echo "<li><strong>❌ Sin Respuestas:</strong> La encuesta existe pero no tiene ninguna respuesta</li>";
    echo "<li><strong>⚠️ Sin Escala:</strong> Tiene respuestas pero ninguna es de tipo 'escala'</li>";
    echo "<li><strong>⚠️ Incompleta:</strong> Falta alguna sección (curso o profesor)</li>";
    echo "<li><strong>✅ Completa:</strong> Tiene respuestas de escala para curso y profesor</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
