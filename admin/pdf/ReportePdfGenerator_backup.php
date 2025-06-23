<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

class ReportePdfGenerator {
    private $pdo;
    private $pdf;
      public function __construct() {
        // Usar la clase Database existente
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        
        $this->pdf = new TCPDF();
        $this->configurarPdf();
    }
    
    private function configurarPdf() {
        $this->pdf->SetCreator('Sistema de Encuestas Académicas');
        $this->pdf->SetAuthor('Academia');
        $this->pdf->SetTitle('Reporte de Encuestas');
        $this->pdf->SetSubject('Reporte de resultados de encuestas académicas');
        $this->pdf->SetKeywords('encuestas, académicas, reporte, profesores');
        
        // Configurar márgenes
        $this->pdf->SetMargins(15, 20, 15);
        $this->pdf->SetHeaderMargin(10);
        $this->pdf->SetFooterMargin(10);
        
        // Configurar fuente
        $this->pdf->SetFont('helvetica', '', 10);
        
        // Configurar header y footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(true);
    }
    
    public function generarReporte($secciones = []) {
        $this->pdf->AddPage();
        
        // Título principal
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 10, 'REPORTE DE ENCUESTAS ACADÉMICAS', 0, 1, 'C');
        $this->pdf->Ln(5);
        
        // Fecha de generación
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 10, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
        $this->pdf->Ln(10);
        
        // Generar cada sección solicitada
        foreach ($secciones as $seccion) {            switch ($seccion) {
                case 'resumen_general':
                case 'estadisticas':
                    $this->generarResumenGeneral();
                    break;
                case 'estadisticas_profesores':
                    $this->generarEstadisticasProfesores();
                    break;
                case 'resultados_por_curso':
                    $this->generarResultadosPorCurso();
                    break;
                case 'analisis_preguntas':
                    $this->generarAnalisisPreguntas();
                    break;
                case 'graficos':
                    $this->generarGraficos();
                    break;
                case 'comentarios':
                    $this->generarComentarios();
                    break;
                default:
                    // Sección no reconocida, agregar mensaje
                    $this->pdf->SetFont('helvetica', 'I', 10);
                    $this->pdf->Cell(0, 10, "Sección '$seccion' no reconocida.", 0, 1);
                    $this->pdf->Ln(5);
                    break;
            }}
        
        // Si no hay secciones, agregar una página básica con información
        if (empty($secciones)) {
            $this->pdf->SetFont('helvetica', '', 12);
            $this->pdf->Cell(0, 10, 'Reporte generado sin secciones específicas.', 0, 1);
            $this->pdf->Cell(0, 10, 'Para ver contenido detallado, especifique las secciones deseadas.', 0, 1);
        }
        
        return $this->pdf->Output('reporte_encuestas_' . date('Y-m-d_H-i-s') . '.pdf', 'S');
    }
    
    private function generarResumenGeneral() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'RESUMEN GENERAL', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Obtener estadísticas generales
        $stats = $this->obtenerEstadisticasGenerales();
        
        $this->pdf->SetFont('helvetica', '', 10);
        
        // Crear tabla de estadísticas
        $html = '<table border="1" cellpadding="5">
            <tr bgcolor="#f0f0f0">
                <th width="60%"><b>Métrica</b></th>
                <th width="40%"><b>Valor</b></th>
            </tr>
            <tr>
                <td>Total de encuestas respondidas</td>
                <td align="center">' . $stats['total_encuestas'] . '</td>
            </tr>
            <tr>
                <td>Total de profesores evaluados</td>
                <td align="center">' . $stats['total_profesores'] . '</td>
            </tr>
            <tr>
                <td>Total de cursos con evaluaciones</td>
                <td align="center">' . $stats['total_cursos'] . '</td>
            </tr>
            <tr>
                <td>Promedio general de satisfacción</td>
                <td align="center">' . number_format($stats['promedio_general'], 2) . '</td>
            </tr>
        </table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
    }
    
    private function generarEstadisticasProfesores() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'ESTADÍSTICAS POR PROFESOR', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $profesores = $this->obtenerEstadisticasProfesores();
        
        $html = '<table border="1" cellpadding="4">
            <tr bgcolor="#f0f0f0">
                <th width="40%"><b>Profesor</b></th>
                <th width="20%"><b>Encuestas</b></th>
                <th width="20%"><b>Promedio</b></th>
                <th width="20%"><b>Satisfacción</b></th>
            </tr>';
        
        foreach ($profesores as $profesor) {
            $html .= '<tr>
                <td>' . htmlspecialchars($profesor['nombre']) . '</td>
                <td align="center">' . $profesor['total_encuestas'] . '</td>
                <td align="center">' . number_format($profesor['promedio'], 2) . '</td>
                <td align="center">' . $this->obtenerNivelSatisfaccion($profesor['promedio']) . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
    }
    
    private function generarResultadosPorCurso() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'RESULTADOS POR CURSO', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $cursos = $this->obtenerResultadosPorCurso();
        
        $html = '<table border="1" cellpadding="4">
            <tr bgcolor="#f0f0f0">
                <th width="30%"><b>Curso</b></th>
                <th width="30%"><b>Profesor</b></th>
                <th width="20%"><b>Encuestas</b></th>
                <th width="20%"><b>Promedio</b></th>
            </tr>';
        
        foreach ($cursos as $curso) {
            $html .= '<tr>
                <td>' . htmlspecialchars($curso['nombre_curso']) . '</td>
                <td>' . htmlspecialchars($curso['nombre_profesor']) . '</td>
                <td align="center">' . $curso['total_encuestas'] . '</td>
                <td align="center">' . number_format($curso['promedio'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
    }
    
    private function generarAnalisisPreguntas() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'ANÁLISIS POR PREGUNTA', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $preguntas = $this->obtenerAnalisisPreguntas();
        
        foreach ($preguntas as $pregunta) {
            $this->pdf->SetFont('helvetica', 'B', 11);
            $this->pdf->Cell(0, 8, 'Pregunta: ' . htmlspecialchars($pregunta['texto_pregunta']), 0, 1, 'L');
            
            $html = '<table border="1" cellpadding="3">
                <tr bgcolor="#f0f0f0">
                    <th width="20%"><b>valor_text</b></th>
                    <th width="20%"><b>Cantidad</b></th>
                    <th width="20%"><b>Porcentaje</b></th>
                    <th width="20%"><b>Promedio</b></th>
                    <th width="20%"><b>Desviación</b></th>
                </tr>';
            
            foreach ($pregunta['estadisticas'] as $stat) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($stat['valor_text']) . '</td>
                    <td align="center">' . $stat['cantidad'] . '</td>
                    <td align="center">' . number_format($stat['porcentaje'], 1) . '%</td>
                    <td align="center">' . number_format($stat['promedio'], 2) . '</td>
                    <td align="center">' . number_format($stat['desviacion'], 2) . '</td>
                </tr>';
            }
            
            $html .= '</table>';
            $this->pdf->writeHTML($html, true, false, true, false, '');
            $this->pdf->Ln(8);
        }
    }
    
    private function generarGraficos() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'GRÁFICOS Y VISUALIZACIONES', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 10, 'Nota: Los gráficos interactivos no se pueden exportar a PDF.', 0, 1, 'L');
        $this->pdf->Cell(0, 10, 'Para ver los gráficos, consulte la versión web del reporte.', 0, 1, 'L');
        $this->pdf->Ln(10);
        
        // Aquí se podrían agregar gráficos estáticos generados con librerías como Chart.js
        // Por ahora, incluimos un resumen textual
        $this->generarResumenGraficos();
    }
    
    private function generarResumenGraficos() {
        $datos = $this->obtenerDatosParaGraficos();
        
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 10, 'Resumen de Datos para Gráficos:', 0, 1, 'L');
        $this->pdf->Ln(3);
        
        $html = '<table border="1" cellpadding="4">
            <tr bgcolor="#f0f0f0">
                <th width="50%"><b>Métrica</b></th>
                <th width="50%"><b>Valor</b></th>
            </tr>';
        
        foreach ($datos as $metrica => $valor) {
            $html .= '<tr>
                <td>' . htmlspecialchars($metrica) . '</td>
                <td align="center">' . htmlspecialchars($valor) . '</td>
            </tr>';
        }        
        $html .= '</table>';
        $this->pdf->writeHTML($html, true, false, true, false, '');
    }
    
    // Métodos para obtener datos de la base de datos
    private function obtenerEstadisticasGenerales() {
        try {
            // Total de encuestas
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM encuestas");
            $total_encuestas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de profesores evaluados
            $stmt = $this->pdo->query("SELECT COUNT(DISTINCT profesor_id) as total FROM respuestas WHERE profesor_id IS NOT NULL");
            $total_profesores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de cursos con evaluaciones
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT e.curso_id) as total 
                FROM encuestas e 
                JOIN respuestas r ON e.id = r.encuesta_id
            ");
            $total_cursos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Promedio general (usando valores numéricos de 1-5)
            $stmt = $this->pdo->query("
                SELECT AVG(valor_int) as promedio 
                FROM respuestas 
                WHERE valor_int IS NOT NULL AND valor_int BETWEEN 1 AND 5
            ");
            $promedio_general = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0;
            
            return [
                'total_encuestas' => $total_encuestas,
                'total_profesores' => $total_profesores,
                'total_cursos' => $total_cursos,
                'promedio_general' => $promedio_general
            ];
        } catch (Exception $e) {
            return [
                'total_encuestas' => 0,
                'total_profesores' => 0,
                'total_cursos' => 0,
                'promedio_general' => 0
            ];
        }
    }
    
    private function obtenerEstadisticasProfesores() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.nombre,
                    COUNT(r.id) as total_encuestas,
                    AVG(CAST(r.valor_text AS DECIMAL(3,2))) as promedio
                FROM profesores p
                LEFT JOIN valor_texts r ON p.id = r.profesor_id
                WHERE r.valor_text REGEXP '^[1-5]$'
                GROUP BY p.id, p.nombre
                ORDER BY promedio DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function obtenerResultadosPorCurso() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    c.nombre as nombre_curso,
                    p.nombre as nombre_profesor,
                    COUNT(r.id) as total_encuestas,
                    AVG(CAST(r.valor_text AS DECIMAL(3,2))) as promedio
                FROM cursos c
                JOIN curso_profesores cp ON c.id = cp.curso_id
                JOIN profesores p ON cp.profesor_id = p.id
                LEFT JOIN valor_texts r ON c.id = r.curso_id AND p.id = r.profesor_id
                WHERE r.valor_text REGEXP '^[1-5]$'
                GROUP BY c.id, p.id, c.nombre, p.nombre
                ORDER BY c.nombre, promedio DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function obtenerAnalisisPreguntas() {
        try {
            $preguntas = [];
            
            // Obtener todas las preguntas
            $stmt = $this->pdo->query("SELECT id, texto_pregunta FROM preguntas ORDER BY orden");
            $preguntasData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($preguntasData as $pregunta) {
                $estadisticas = [];
                
                // Obtener estadísticas por valor_text para esta pregunta
                $stmt = $this->pdo->prepare("
                    SELECT 
                        valor_text,
                        COUNT(*) as cantidad,
                        (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM valor_texts WHERE pregunta_id = ?)) as porcentaje
                    FROM valor_texts 
                    WHERE pregunta_id = ?
                    GROUP BY valor_text
                    ORDER BY valor_text
                ");
                
                $stmt->execute([$pregunta['id'], $pregunta['id']]);
                $valor_texts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calcular promedio y desviación para valor_texts numéricas
                $stmt = $this->pdo->prepare("
                    SELECT 
                        AVG(CAST(valor_text AS DECIMAL(3,2))) as promedio,
                        STDDEV(CAST(valor_text AS DECIMAL(3,2))) as desviacion
                    FROM valor_texts 
                    WHERE pregunta_id = ? AND valor_text REGEXP '^[1-5]$'
                ");
                
                $stmt->execute([$pregunta['id']]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                foreach ($valor_texts as $valor_text) {
                    $estadisticas[] = [
                        'valor_text' => $valor_text['valor_text'],
                        'cantidad' => $valor_text['cantidad'],
                        'porcentaje' => $valor_text['porcentaje'],
                        'promedio' => $stats['promedio'] ?? 0,
                        'desviacion' => $stats['desviacion'] ?? 0
                    ];
                }
                
                $preguntas[] = [
                    'texto_pregunta' => $pregunta['texto_pregunta'],
                    'estadisticas' => $estadisticas
                ];
            }
            
            return $preguntas;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function obtenerDatosParaGraficos() {
        try {
            $datos = [];
            
            // Distribución de valor_texts por escala
            $stmt = $this->pdo->query("
                SELECT 
                    valor_text, 
                    COUNT(*) as cantidad 
                FROM valor_texts 
                WHERE valor_text REGEXP '^[1-5]$' 
                GROUP BY valor_text 
                ORDER BY valor_text
            ");
            
            $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($distribucion as $item) {
                $datos["valor_texts con valor " . $item['valor_text']] = $item['cantidad'];
            }
            
            // Promedio por departamento (si existe)
            $stmt = $this->pdo->query("
                SELECT 
                    p.departamento,
                    AVG(CAST(r.valor_text AS DECIMAL(3,2))) as promedio
                FROM profesores p
                JOIN valor_texts r ON p.id = r.profesor_id
                WHERE r.valor_text REGEXP '^[1-5]$' AND p.departamento IS NOT NULL
                GROUP BY p.departamento
                ORDER BY promedio DESC
            ");
            
            $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($departamentos as $dept) {
                $datos["Promedio " . $dept['departamento']] = number_format($dept['promedio'], 2);
            }
            
            return $datos;
        } catch (Exception $e) {
            return ['Error' => 'No se pudieron obtener los datos'];
        }
    }    private function obtenerNivelSatisfaccion($promedio) {
        if ($promedio >= 4.5) return 'Excelente';
        if ($promedio >= 4.0) return 'Muy Bueno';
        if ($promedio >= 3.5) return 'Bueno';
        if ($promedio >= 3.0) return 'Regular';
        return 'Necesita Mejora';
    }
    
    /**
     * Obtener estadísticas detalladas por profesor
     */
    private function obtenerEstadisticasPorProfesor($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    CONCAT(p.nombre, ' ', p.apellido) as nombre,
                    COUNT(r.id) as total_valor_texts,
                    AVG(CASE WHEN r.valor_text REGEXP '^[1-5]$' THEN CAST(r.valor_text AS DECIMAL(3,2)) END) as promedio,
                    (COUNT(CASE WHEN r.valor_text IN ('4', '5') THEN 1 END) * 100.0 / COUNT(CASE WHEN r.valor_text REGEXP '^[1-5]$' THEN 1 END)) as satisfaccion
                FROM profesores p
                LEFT JOIN valor_texts r ON p.id = r.profesor_id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY p.id, p.nombre, p.apellido
                HAVING total_valor_texts > 0
                ORDER BY promedio DESC, satisfaccion DESC
            ");
            
            $stmt->execute([
                ':curso_id' => $curso_id,
                ':fecha' => $fecha
            ]);
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear datos
            foreach ($resultados as &$profesor) {
                $profesor['promedio'] = floatval($profesor['promedio'] ?? 0);
                $profesor['satisfaccion'] = round(floatval($profesor['satisfaccion'] ?? 0), 1);
                $profesor['total_valor_texts'] = intval($profesor['total_valor_texts']);
            }
            
            return $resultados;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener estadísticas por categoría de preguntas
     */
    private function obtenerEstadisticasPorCategoria($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(pr.categoria, 'General') as categoria,
                    COUNT(r.id) as total,
                    AVG(CASE WHEN r.valor_text REGEXP '^[1-5]$' THEN CAST(r.valor_text AS DECIMAL(3,2)) END) as promedio
                FROM preguntas pr
                LEFT JOIN valor_texts r ON pr.id = r.pregunta_id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY pr.categoria
                HAVING total > 0
                ORDER BY promedio DESC
            ");
            
            $stmt->execute([
                ':curso_id' => $curso_id,
                ':fecha' => $fecha
            ]);
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear datos
            foreach ($resultados as &$categoria) {
                $categoria['promedio'] = floatval($categoria['promedio'] ?? 0);
                $categoria['total'] = intval($categoria['total']);
            }
            
            return $resultados;
            
        } catch (Exception $e) {
            return [];
        }
    }/**
     * NUEVO MÉTODO PRINCIPAL - Generar PDF idéntico a la imagen
     * Este método reemplaza toda la lógica anterior
     */
    public function generarReportePorCursoFecha($curso_id, $fecha, $secciones, $imagenes_graficos = []) {
        try {
            // Validar que el curso existe
            $stmt = $this->pdo->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
            $stmt->execute([':curso_id' => $curso_id]);
            $curso = $stmt->fetch();
            
            if (!$curso) {
                throw new Exception("Curso no encontrado con ID: $curso_id");
            }
            
            $this->pdf->AddPage();
            
            // HEADER PRINCIPAL - Igual que en la imagen
            $this->generarHeaderPrincipal($curso, $fecha);
            
            // GENERAR SECCIONES EN EL ORDEN CORRECTO
            foreach ($secciones as $seccion) {
                try {
                    switch ($seccion) {
                        case 'graficos_evaluacion':
                            $this->generarSeccionGraficos($curso_id, $fecha, $imagenes_graficos);
                            break;
                        case 'estadisticas_detalladas':
                            $this->generarSeccionEstadisticasTabla($curso_id, $fecha);
                            break;
                        case 'comentarios_curso':
                            $this->generarSeccionComentariosCurso($curso_id, $fecha);
                            break;
                        case 'comentarios_profesores':
                            $this->generarSeccionComentariosProfesores($curso_id, $fecha);
                            break;
                        case 'resumen_ejecutivo':
                            // Esta sección se omite por ahora, enfoque en gráficos y tablas
                            break;
                        case 'preguntas_criticas':
                            // Esta sección se omite por ahora, enfoque en gráficos y tablas
                            break;
                    }
                } catch (Exception $e) {
                    $this->agregarMensajeError($seccion, $e->getMessage());
                }
            }
              return $this->pdf->Output('', 'S');
            
        } catch (Exception $e) {
            return $this->generarPdfError($e, $curso_id, $fecha);
        }
    }
    
    /**
     * Agregar mensaje de error en el PDF cuando falla una sección
     */
    private function agregarMensajeError($seccion, $mensaje) {
        $this->pdf->SetFont('helvetica', 'I', 10);
        $this->pdf->SetTextColor(220, 53, 69); // Color rojo
        $this->pdf->Cell(0, 8, '❌ Error en sección "' . $seccion . '": ' . $mensaje, 0, 1, 'L');
        $this->pdf->SetTextColor(0, 0, 0); // Resetear color
        $this->pdf->Ln(5);
    }
    
    /**
     * Generar PDF de error cuando falla todo el proceso
     */
    private function generarPdfError($exception, $curso_id, $fecha) {
        // Crear un PDF limpio para mostrar el error
        $this->pdf = new TCPDF();
        $this->configurarPdf();
        $this->pdf->AddPage();
        
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->SetTextColor(220, 53, 69);
        $this->pdf->Cell(0, 15, '❌ ERROR EN GENERACIÓN DE REPORTE', 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);
        
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Se produjo un error al generar el reporte:', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Detalles del error:', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->MultiCell(0, 6, $exception->getMessage(), 0, 'L');
        $this->pdf->Ln(5);
        
        $this->pdf->Cell(0, 6, 'Curso ID: ' . $curso_id, 0, 1, 'L');
        $this->pdf->Cell(0, 6, 'Fecha: ' . $fecha, 0, 1, 'L');
        $this->pdf->Cell(0, 6, 'Archivo: ' . $exception->getFile(), 0, 1, 'L');
        $this->pdf->Cell(0, 6, 'Línea: ' . $exception->getLine(), 0, 1, 'L');
        
        return $this->pdf->Output('', 'S');
    }
    
    /**
     * Generar sección de Resumen Ejecutivo
     */
    private function generarResumenEjecutivo($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, '📊 RESUMEN EJECUTIVO', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Obtener estadísticas específicas del curso y fecha
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT e.id) as total_encuestas,
                COUNT(DISTINCT r.profesor_id) as total_profesores,
                AVG(r.valor_int) as promedio_general,
                STDDEV(r.valor_int) as desviacion_general
            FROM encuestas e
            JOIN valor_texts r ON e.id = r.encuesta_id
            JOIN preguntas p ON r.pregunta_id = p.id
            WHERE e.curso_id = :curso_id 
            AND DATE(e.fecha_envio) = :fecha
            AND p.tipo = 'escala'
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $stats = $stmt->fetch();
        
        $this->pdf->SetFont('helvetica', '', 10);
        
        $html = '<table border="1" cellpadding="5">
            <tr bgcolor="#e3f2fd">
                <th width="60%"><b>Métrica</b></th>
                <th width="40%"><b>Valor</b></th>
            </tr>
            <tr>
                <td>Total de encuestas respondidas</td>
                <td align="center">' . ($stats['total_encuestas'] ?? 0) . '</td>
            </tr>
            <tr>
                <td>Total de profesores evaluados</td>
                <td align="center">' . ($stats['total_profesores'] ?? 0) . '</td>
            </tr>
            <tr>
                <td>Promedio general de satisfacción</td>
                <td align="center">' . number_format($stats['promedio_general'] ?? 0, 2) . '/10</td>
            </tr>
            <tr>
                <td>Desviación estándar</td>
                <td align="center">' . number_format($stats['desviacion_general'] ?? 0, 2) . '</td>
            </tr>
        </table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
    }
    
    /**
     * Generar sección de Distribución de valor_texts
     */
    private function generarDistribucionvalor_texts($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, '📈 DISTRIBUCIÓN DE valor_textS', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Obtener distribución de valor_texts
        $stmt = $this->pdo->prepare("
            SELECT 
                r.valor_int,
                COUNT(*) as frecuencia,
                ROUND((COUNT(*) * 100.0 / (
                    SELECT COUNT(*) 
                    FROM encuestas e2 
                    JOIN valor_texts r2 ON e2.id = r2.encuesta_id 
                    JOIN preguntas p2 ON r2.pregunta_id = p2.id 
                    WHERE e2.curso_id = :curso_id 
                    AND DATE(e2.fecha_envio) = :fecha 
                    AND p2.tipo = 'escala'
                )), 1) as porcentaje
            FROM encuestas e
            JOIN valor_texts r ON e.id = r.encuesta_id
            JOIN preguntas p ON r.pregunta_id = p.id
            WHERE e.curso_id = :curso_id
            AND DATE(e.fecha_envio) = :fecha
            AND p.tipo = 'escala'
            GROUP BY r.valor_int
            ORDER BY r.valor_int DESC
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $distribucion = $stmt->fetchAll();
        
        $this->pdf->SetFont('helvetica', '', 10);
        
        $html = '<table border="1" cellpadding="5">
            <tr bgcolor="#e8f5e8">
                <th width="25%"><b>Puntuación</b></th>
                <th width="25%"><b>Frecuencia</b></th>
                <th width="25%"><b>Porcentaje</b></th>
                <th width="25%"><b>Nivel</b></th>
            </tr>';
        
        foreach ($distribucion as $dist) {
            $nivel = $this->obtenerNivelSatisfaccion($dist['valor_int']);
            $color = $dist['valor_int'] >= 8 ? '#d4edda' : ($dist['valor_int'] >= 6 ? '#fff3cd' : '#f8d7da');
            
            $html .= '<tr bgcolor="' . $color . '">
                <td align="center"><b>' . $dist['valor_int'] . '</b></td>
                <td align="center">' . $dist['frecuencia'] . '</td>
                <td align="center">' . $dist['porcentaje'] . '%</td>
                <td align="center">' . $nivel . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
    }
    
    /**
     * Generar sección de Estadísticas Detalladas
     */
    private function generarEstadisticasDetalladas($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, '📋 ESTADÍSTICAS DETALLADAS', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Obtener estadísticas por profesor
        $stmt = $this->pdo->prepare("
            SELECT 
                p.nombre,
                COUNT(DISTINCT e.id) as total_encuestas,
                COUNT(DISTINCT r.pregunta_id) as total_preguntas,
                AVG(r.valor_int) as promedio,
                MIN(r.valor_int) as minimo,
                MAX(r.valor_int) as maximo
            FROM profesores p
            JOIN valor_texts r ON p.id = r.profesor_id
            JOIN encuestas e ON r.encuesta_id = e.id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE e.curso_id = :curso_id
            AND DATE(e.fecha_envio) = :fecha
            AND pr.tipo = 'escala'
            GROUP BY p.id, p.nombre
            ORDER BY promedio DESC
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $profesores = $stmt->fetchAll();
        
        $this->pdf->SetFont('helvetica', '', 9);
        
        $html = '<table border="1" cellpadding="4">
            <tr bgcolor="#f0f8ff">
                <th width="30%"><b>Profesor</b></th>
                <th width="15%"><b>Encuestas</b></th>
                <th width="15%"><b>Preguntas</b></th>
                <th width="15%"><b>Promedio</b></th>
                <th width="12%"><b>Mín</b></th>
                <th width="13%"><b>Máx</b></th>
            </tr>';
        
        foreach ($profesores as $prof) {
            $color = $prof['promedio'] >= 8 ? '#d4edda' : ($prof['promedio'] >= 6 ? '#fff3cd' : '#f8d7da');
            
            $html .= '<tr bgcolor="' . $color . '">
                <td>' . htmlspecialchars($prof['nombre']) . '</td>
                <td align="center">' . $prof['total_encuestas'] . '</td>
                <td align="center">' . $prof['total_preguntas'] . '</td>
                <td align="center"><b>' . number_format($prof['promedio'], 2) . '</b></td>
                <td align="center">' . $prof['minimo'] . '</td>
                <td align="center">' . $prof['maximo'] . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
    }
    
    /**
     * Generar sección de Preguntas Críticas
     */
    private function generarPreguntasCriticas($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, '⚠️ PREGUNTAS CRÍTICAS', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Obtener preguntas con bajo rendimiento
        $stmt = $this->pdo->prepare("
            SELECT 
                p.texto,
                p.seccion,
                COUNT(r.id) as total_valor_texts,
                AVG(r.valor_int) as promedio,
                STDDEV(r.valor_int) as desviacion_estandar,
                SUM(CASE WHEN r.valor_int <= 5 THEN 1 ELSE 0 END) as valor_texts_bajas
            FROM preguntas p
            JOIN valor_texts r ON p.id = r.pregunta_id
            JOIN encuestas e ON r.encuesta_id = e.id
            WHERE e.curso_id = :curso_id
            AND DATE(e.fecha_envio) = :fecha
            AND p.tipo = 'escala'
            GROUP BY p.id, p.texto, p.seccion
            HAVING promedio < 7
            ORDER BY promedio ASC, valor_texts_bajas DESC
            LIMIT 10
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $preguntas_criticas = $stmt->fetchAll();
        
        if (empty($preguntas_criticas)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, '✅ No se encontraron preguntas críticas (todas las preguntas tienen puntuación >= 7)', 0, 1, 'L');
        } else {
            $this->pdf->SetFont('helvetica', '', 8);
            
            $html = '<table border="1" cellpadding="3">
                <tr bgcolor="#ffe6e6">
                    <th width="50%"><b>Pregunta</b></th>
                    <th width="10%"><b>Sección</b></th>
                    <th width="10%"><b>valor_texts</b></th>
                    <th width="10%"><b>Promedio</b></th>
                    <th width="10%"><b>Bajas</b></th>
                    <th width="10%"><b>% Crítico</b></th>
                </tr>';
            
            foreach ($preguntas_criticas as $pregunta) {
                $porcentaje_critico = round(($pregunta['valor_texts_bajas'] / $pregunta['total_valor_texts']) * 100, 1);
                $color = $porcentaje_critico >= 30 ? '#f8d7da' : ($porcentaje_critico >= 15 ? '#fff3cd' : '#f0f0f0');
                
                $html .= '<tr bgcolor="' . $color . '">
                    <td>' . htmlspecialchars(substr($pregunta['texto'], 0, 80)) . '...</td>
                    <td align="center">' . ucfirst($pregunta['seccion']) . '</td>
                    <td align="center">' . $pregunta['total_valor_texts'] . '</td>
                    <td align="center">' . number_format($pregunta['promedio'], 2) . '</td>
                    <td align="center">' . $pregunta['valor_texts_bajas'] . '</td>
                    <td align="center">' . $porcentaje_critico . '%</td>
                </tr>';
            }
            
            $html .= '</table>';
            
            $this->pdf->writeHTML($html, true, false, true, false, '');
        }
        
        $this->pdf->Ln(10);
    }
    
    /**
     * ==========================================
     * NUEVOS MÉTODOS QUE REPLICAN LA PÁGINA WEB
     * ==========================================
     */
    
    /**
     * Generar Resumen Ejecutivo idéntico a la página web
     */
    private function generarResumenEjecutivoReal($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(13, 110, 253); // bg-primary
        $this->pdf->SetTextColor(255, 255, 255); // text-white
        $this->pdf->Cell(0, 12, '   RESUMEN EJECUTIVO - ' . date('d/m/Y', strtotime($fecha)), 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0); // Reset color
        $this->pdf->Ln(5);
        
        // Obtener datos del resumen ejecutivo igual que en reportes.php
        $resumen_ejecutivo = $this->obtenerResumenEjecutivoCompleto($curso_id, $fecha);
        
        if (!$resumen_ejecutivo) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para el resumen ejecutivo', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // 1. KPIs principales (6 tarjetas como en la web)
        $this->generarKPIsPrincipales($resumen_ejecutivo);
        
        // 2. Estadísticas descriptivas (tabla de la izquierda)
        $this->generarEstadisticasDescriptivas($resumen_ejecutivo);
        
        $this->pdf->Ln(10);
    }
    
    /**
     * Generar KPIs principales como tarjetas de colores
     */
    private function generarKPIsPrincipales($resumen_ejecutivo) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, '📊 Indicadores Clave de Rendimiento', 0, 1, 'L');
        $this->pdf->Ln(3);
        
        $stats = $resumen_ejecutivo['stats'];
        
        // Crear tabla HTML con los KPIs estilizada como las tarjetas
        $html = '<table cellpadding="8" cellspacing="3">
            <tr>
                <td bgcolor="#e3f2fd" align="center" width="16.66%">
                    <span style="font-size:16px; color:#0d6efd; font-weight:bold;">' . $stats['total_encuestas_global'] . '</span><br>
                    <span style="font-size:8px; color:#6c757d;">Encuestas</span>
                </td>
                <td bgcolor="#d1e7dd" align="center" width="16.66%">
                    <span style="font-size:16px; color:#198754; font-weight:bold;">' . $stats['total_profesores_evaluados'] . '</span><br>
                    <span style="font-size:8px; color:#6c757d;">Profesores</span>
                </td>
                <td bgcolor="#cff4fc" align="center" width="16.66%">
                    <span style="font-size:16px; color:#0dcaf0; font-weight:bold;">' . round($stats['promedio_general'], 1) . '</span><br>
                    <span style="font-size:8px; color:#6c757d;">Promedio</span>
                </td>
                <td bgcolor="#fff3cd" align="center" width="16.66%">
                    <span style="font-size:16px; color:#ffc107; font-weight:bold;">' . $resumen_ejecutivo['percentiles']['mediana'] . '</span><br>
                    <span style="font-size:8px; color:#6c757d;">Mediana</span>
                </td>
                <td bgcolor="#f8f9fa" align="center" width="16.66%">
                    <span style="font-size:16px; color:#6c757d; font-weight:bold;">' . round($stats['desviacion_general'], 1) . '</span><br>
                    <span style="font-size:8px; color:#6c757d;">Desv. Est.</span>
                </td>
                <td bgcolor="#212529" align="center" width="16.66%">
                    <span style="font-size:16px; color:#ffffff; font-weight:bold;">' . $stats['total_valor_texts_escala'] . '</span><br>
                    <span style="font-size:8px; color:#ffffff;">valor_texts</span>
                </td>
            </tr>
        </table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(8);
    }
    
    /**
     * Generar estadísticas descriptivas como tabla
     */
    private function generarEstadisticasDescriptivas($resumen_ejecutivo) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, '📊 Estadísticas Descriptivas', 0, 1, 'L');
        $this->pdf->Ln(3);
        
        $stats = $resumen_ejecutivo['stats'];
        $percentiles = $resumen_ejecutivo['percentiles'];
        
        // Calcular coeficiente de variación
        $cv = $stats['promedio_general'] > 0 ? 
             round(($stats['desviacion_general'] / $stats['promedio_general']) * 100, 1) : 0;
        $cv_color = $cv < 20 ? '#d1e7dd' : ($cv < 30 ? '#fff3cd' : '#f8d7da');
          $html = '<table border="1" cellpadding="5">
            <tr bgcolor="#f8f9fa">
                <td width="50%"><b>Rango:</b></td>
                <td width="50%">
                    <span bgcolor="#dc3545">' . $stats['valor_minimo'] . '</span>
                    -
                    <span bgcolor="#198754">' . $stats['valor_maximo'] . '</span>
                </td>
            </tr>
            <tr>
                <td><b>Percentil 25:</b></td>
                <td><span bgcolor="#0dcaf0">' . $percentiles['p25'] . '</span></td>
            </tr>
            <tr bgcolor="#f8f9fa">
                <td><b>Percentil 75:</b></td>
                <td><span bgcolor="#0dcaf0">' . $percentiles['p75'] . '</span></td>
            </tr>
            <tr>
                <td><b>Coef. Variación:</b></td>
                <td>' . $cv . '%</td>
            </tr>
        </table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(5);
        
        // Distribución de valor_texts (tabla de la derecha)
        $this->generarDistribucionvalor_textsTabla($resumen_ejecutivo);
    }
    
    /**
     * Generar tabla de distribución de valor_texts
     */
    private function generarDistribucionvalor_textsTabla($resumen_ejecutivo) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, '🥧 Distribución de valor_texts', 0, 1, 'L');
        $this->pdf->Ln(3);
          $html = '<table border="1" cellpadding="4">
            <tr bgcolor="#f8f9fa">
                <th width="33%"><b>Valor</b></th>
                <th width="33%"><b>Frecuencia</b></th>
                <th width="34%"><b>%</b></th>
            </tr>';
        
        foreach ($resumen_ejecutivo['distribucion'] as $dist) {
            // Colores según el valor (como en la web)
            $color = $dist['valor_int'] >= 8 ? '#d1e7dd' : ($dist['valor_int'] >= 6 ? '#fff3cd' : '#f8d7da');
            
            $html .= '<tr bgcolor="' . $color . '">
                <td align="center"><b>' . $dist['valor_int'] . '</b></td>
                <td align="center">' . $dist['frecuencia'] . '</td>
                <td align="center">' . round($dist['porcentaje'], 1) . '%</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
    }
    
    /**
     * Obtener datos completos del resumen ejecutivo igual que en reportes.php
     */
    private function obtenerResumenEjecutivoCompleto($curso_id, $fecha) {
        try {
            // Replicar exactamente la misma consulta que está en reportes.php
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_encuestas_global,
                    COUNT(DISTINCT r.profesor_id) as total_profesores_evaluados,
                    COUNT(r.id) as total_valor_texts_escala,
                    AVG(r.valor_int) as promedio_general,
                    STDDEV(r.valor_int) as desviacion_general,
                    MIN(r.valor_int) as valor_minimo,
                    MAX(r.valor_int) as valor_maximo
                FROM encuestas e
                JOIN valor_texts r ON e.id = r.encuesta_id
                JOIN preguntas p ON r.pregunta_id = p.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND p.tipo = 'escala'
                AND (p.seccion = 'curso' OR p.seccion = 'profesor')
            ");
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $stats = $stmt->fetch();
            
            if (!$stats || $stats['total_encuestas_global'] == 0) {
                return null;
            }
            
            // Obtener percentiles
            $stmt = $this->pdo->prepare("
                SELECT r.valor_int
                FROM encuestas e
                JOIN valor_texts r ON e.id = r.encuesta_id
                JOIN preguntas p ON r.pregunta_id = p.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND p.tipo = 'escala'
                AND (p.seccion = 'curso' OR p.seccion = 'profesor')
                ORDER BY r.valor_int
            ");
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $valores = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $total = count($valores);
            $percentiles = [
                'p25' => $total > 0 ? $valores[intval($total * 0.25)] : 0,
                'mediana' => $total > 0 ? $valores[intval($total * 0.5)] : 0,
                'p75' => $total > 0 ? $valores[intval($total * 0.75)] : 0
            ];
            
            // Obtener distribución de valor_texts
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.valor_int,
                    COUNT(*) as frecuencia,
                    (COUNT(*) * 100.0 / :total) as porcentaje
                FROM encuestas e
                JOIN valor_texts r ON e.id = r.encuesta_id
                JOIN preguntas p ON r.pregunta_id = p.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND p.tipo = 'escala'
                AND (p.seccion = 'curso' OR p.seccion = 'profesor')
                GROUP BY r.valor_int
                ORDER BY r.valor_int DESC
            ");
            $stmt->execute([
                ':curso_id' => $curso_id, 
                ':fecha' => $fecha,
                ':total' => $stats['total_valor_texts_escala']
            ]);
            $distribucion = $stmt->fetchAll();
            
            return [
                'stats' => $stats,
                'percentiles' => $percentiles,
                'distribucion' => $distribucion
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo resumen ejecutivo: " . $e->getMessage());
            return null;
        }
    }
      /**
     * Generar gráficos de evaluación usando imágenes de Chart.js
     */
    private function generarGraficosEvaluacionReal($curso_id, $fecha, $imagenes_graficos = []) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(13, 110, 253); // bg-primary  
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   GRÁFICOS DE EVALUACIÓN - ' . date('d/m/Y', strtotime($fecha)), 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0); // Reset color
        $this->pdf->Ln(5);
        
        if (empty($imagenes_graficos)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No se recibieron imágenes de gráficos para exportar', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // Insertar cada imagen recibida desde el frontend
        foreach ($imagenes_graficos as $imagen) {
            $this->insertarImagenGrafico($imagen);
        }
        
        $this->pdf->Ln(10);
    }
    
    /**
     * Insertar imagen de gráfico en el PDF
     */
    private function insertarImagenGrafico($imagen_data) {
        if (!isset($imagen_data['data']) || !isset($imagen_data['titulo'])) {
            return;
        }
        
        // Título del gráfico
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, $imagen_data['titulo'], 0, 1, 'C');
        $this->pdf->Ln(5);
        
        try {
            // Decodificar imagen base64
            $imagen_binary = base64_decode($imagen_data['data']);
            
            // Crear archivo temporal
            $temp_file = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
            file_put_contents($temp_file, $imagen_binary);
            
            // Insertar imagen en el PDF
            $this->pdf->Image($temp_file, 20, null, 170, 0, 'PNG');
            
            // Eliminar archivo temporal
            unlink($temp_file);
            
            $this->pdf->Ln(10);
            
        } catch (Exception $e) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 8, 'Error al insertar imagen: ' . $e->getMessage(), 0, 1, 'L');            $this->pdf->Ln(5);
        }
    }    /**
     * Generar estadísticas detalladas con colores y barras de progreso como en la web
     */
    private function generarEstadisticasDetalladasReal($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(13, 110, 253);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   📊 ESTADÍSTICAS DETALLADAS', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        // Obtener datos detallados por profesor
        $profesores_stats = $this->obtenerEstadisticasPorProfesor($curso_id, $fecha);
        
        if (empty($profesores_stats)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No hay datos de estadísticas por profesor para esta fecha', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // Generar tabla de estadísticas por profesor con colores y barras
        $this->generarTablaEstadisticasProfesores($profesores_stats);
        
        // Espacio entre secciones
        $this->pdf->Ln(8);
        
        // Generar estadísticas por pregunta/sección
        $this->generarEstadisticasPorSeccion($curso_id, $fecha);
        
        $this->pdf->Ln(10);
    }
    
    private function generarComentariosCursoReal($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(13, 110, 253);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   COMENTARIOS DEL CURSO', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 10, 'Comentarios del curso ID: ' . $curso_id . ' en fecha: ' . $fecha, 0, 1, 'L');
        $this->pdf->Ln(10);
    }
    
    private function generarComentariosProfesoresReal($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(25, 135, 84);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   COMENTARIOS DE PROFESORES', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 10, 'Comentarios de profesores para curso ID: ' . $curso_id . ' en fecha: ' . $fecha, 0, 1, 'L');
        $this->pdf->Ln(10);
    }
    
    private function generarPreguntasCriticasReal($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(220, 53, 69);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   ⚠️ PREGUNTAS CRÍTICAS', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 10, 'Preguntas críticas para curso ID: ' . $curso_id . ' en fecha: ' . $fecha, 0, 1, 'L');
        $this->pdf->Ln(10);
    }

    /**
     * ==========================================
     * NUEVOS MÉTODOS - RÉPLICA EXACTA DE LA IMAGEN
     * ==========================================
     */
    
    /**
     * Generar header principal como en la imagen
     */
    private function generarHeaderPrincipal($curso, $fecha) {
        // Fondo oscuro como en la imagen
        $this->pdf->SetFillColor(52, 58, 64); // Dark background
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 15, '📊 Gráficos de Evaluación - Fecha: ' . date('d-m-Y', strtotime($fecha)) . ' (3 gráficos)', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0); // Reset color
        $this->pdf->Ln(10);
    }
    
    /**
     * Generar sección de gráficos - IGUAL QUE LA IMAGEN
     */
    private function generarSeccionGraficos($curso_id, $fecha, $imagenes_graficos) {
        if (empty($imagenes_graficos)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No se recibieron gráficos para mostrar', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // Calcular cuántos gráficos por fila (3 columnas como en la imagen)
        $graficos_por_fila = 3;
        $ancho_grafico = 180 / $graficos_por_fila; // Dividir el ancho disponible
        $contador = 0;
        
        foreach ($imagenes_graficos as $imagen) {
            if ($contador % $graficos_por_fila == 0 && $contador > 0) {
                $this->pdf->Ln(80); // Nueva fila
            }
            
            $x = 15 + ($contador % $graficos_por_fila) * $ancho_grafico;
            $y = $this->pdf->GetY();
            
            // Insertar gráfico con tamaño específico
            $this->insertarGraficoEnPosicion($imagen, $x, $y, $ancho_grafico - 5);
            
            $contador++;
        }
        
        $this->pdf->Ln(90); // Espacio después de todos los gráficos
    }
    
    /**
     * Insertar gráfico en posición específica
     */
    private function insertarGraficoEnPosicion($imagen_data, $x, $y, $ancho) {
        if (!isset($imagen_data['data']) || !isset($imagen_data['titulo'])) {
            return;
        }
        
        try {
            // Decodificar imagen base64
            $imagen_binary = base64_decode($imagen_data['data']);
            
            // Crear archivo temporal
            $temp_file = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
            file_put_contents($temp_file, $imagen_binary);
            
            // Título del gráfico encima
            $this->pdf->SetFont('helvetica', 'B', 9);
            $this->pdf->SetXY($x, $y - 5);
            $this->pdf->Cell($ancho, 5, $imagen_data['titulo'], 0, 0, 'C');
            
            // Insertar imagen
            $this->pdf->Image($temp_file, $x, $y, $ancho, 0, 'PNG');
            
            // Eliminar archivo temporal
            unlink($temp_file);
            
        } catch (Exception $e) {
            // En caso de error, mostrar un rectángulo placeholder
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->SetXY($x, $y);
            $this->pdf->Cell($ancho, 40, 'Error: gráfico no disponible', 1, 0, 'C');
        }
    }
    
    /**
     * Generar tabla de estadísticas detalladas - IGUAL QUE LA IMAGEN
     */
    private function generarSeccionEstadisticasTabla($curso_id, $fecha) {
        // Header de la sección
        $this->pdf->SetFillColor(52, 58, 64); // Dark background
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 12, '📊 Estadísticas Detalladas', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        // Obtener datos reales de la base de datos
        $estadisticas = $this->obtenerEstadisticasRealesParaTabla($curso_id, $fecha);
        
        if (empty($estadisticas)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para las estadísticas detalladas', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // Crear tabla con colores exactos como en la imagen
        $html = '<table border="1" cellpadding="6" cellspacing="0">
            <tr bgcolor="#343a40" style="color: white;">
                <th width="8%"><b>Tipo</b></th>
                <th width="32%"><b>Curso/Profesor</b></th>
                <th width="12%"><b>Encuestas</b></th>
                <th width="12%"><b>Preguntas</b></th>
                <th width="12%"><b>Puntuación</b></th>
                <th width="24%"><b>% Aprovechamiento</b></th>
            </tr>';
        
        foreach ($estadisticas as $stat) {
            // Determinar color de fondo según puntuación (como en la imagen)
            $puntuacion = $stat['puntuacion'];
            if ($puntuacion >= 8) {
                $bg_color = '#d4edda'; // Verde
                $aprovechamiento_color = '#28a745';
            } elseif ($puntuacion >= 6) {
                $bg_color = '#fff3cd'; // Amarillo
                $aprovechamiento_color = '#ffc107';
            } else {
                $bg_color = '#f8d7da'; // Rojo
                $aprovechamiento_color = '#dc3545';
            }
            
            // Calcular porcentaje de aprovechamiento
            $porcentaje = round(($puntuacion / 10) * 100, 1);
            
            // Crear barra de progreso visual
            $barra_progreso = $this->crearBarraProgreso($porcentaje, $aprovechamiento_color);
            
            $html .= '<tr bgcolor="' . $bg_color . '">
                <td align="center"><b>' . htmlspecialchars($stat['tipo']) . '</b></td>
                <td>' . htmlspecialchars($stat['nombre']) . '</td>
                <td align="center">' . $stat['encuestas'] . '</td>
                <td align="center">' . $stat['preguntas'] . '</td>
                <td align="center"><b>' . number_format($puntuacion, 1) . '</b></td>
                <td>' . $barra_progreso . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(15);
    }
      /**
     * Crear barra de progreso visual para la tabla
     */
    private function crearBarraProgreso($porcentaje, $color) {
        $barras_llenas = intval($porcentaje / 10); // Cada barra representa 10%
        $barra_html = '';
        
        // Crear barras llenas
        for ($i = 0; $i < $barras_llenas; $i++) {
            $barra_html .= '<span style="background-color:' . $color . '; color:white;">█</span>';
        }
        
        // Crear barras vacías
        for ($i = $barras_llenas; $i < 10; $i++) {
            $barra_html .= '<span style="color:#e9ecef;">█</span>';
        }
        
        return $barra_html . ' ' . $porcentaje . '%';
    }
    
    /**
     * Generar tabla de estadísticas por profesores con colores y barras
     */
    private function generarTablaEstadisticasProfesores($profesores_stats) {
        // Header de la tabla
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->SetTextColor(33, 37, 41);
        
        // Anchos de columnas (total 190mm)
        $col_widths = [50, 40, 35, 35, 30];
        
        $this->pdf->Cell($col_widths[0], 10, 'Profesor', 1, 0, 'L', true);
        $this->pdf->Cell($col_widths[1], 10, 'valor_texts', 1, 0, 'C', true);
        $this->pdf->Cell($col_widths[2], 10, 'Promedio', 1, 0, 'C', true);
        $this->pdf->Cell($col_widths[3], 10, 'Satisfacción', 1, 0, 'C', true);
        $this->pdf->Cell($col_widths[4], 10, 'Estado', 1, 1, 'C', true);
        
        // Datos de la tabla
        $this->pdf->SetFont('helvetica', '', 10);
        
        foreach ($profesores_stats as $profesor) {
            $y_position = $this->pdf->GetY();
            
            // Color de fondo alternado
            $fill = (count($profesores_stats) % 2 == 0) ? true : false;
            $this->pdf->SetFillColor(255, 255, 255);
            
            // Nombre del profesor
            $this->pdf->Cell($col_widths[0], 12, $profesor['nombre'], 1, 0, 'L', $fill);
            
            // Número de valor_texts
            $this->pdf->Cell($col_widths[1], 12, $profesor['total_valor_texts'], 1, 0, 'C', $fill);
            
            // Promedio con color según valor
            $promedio = number_format($profesor['promedio'], 1);
            $color_promedio = $this->obtenerColorPromedio($profesor['promedio']);
            $this->pdf->SetFillColor($color_promedio[0], $color_promedio[1], $color_promedio[2]);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell($col_widths[2], 12, $promedio, 1, 0, 'C', true);
            $this->pdf->SetTextColor(33, 37, 41);
            
            // Barra de satisfacción (porcentaje visual)
            $satisfaccion = $profesor['satisfaccion'];
            $this->generarBarraSatisfaccion($col_widths[3], 12, $satisfaccion);
            
            // Estado con badge de color
            $estado = $this->obtenerEstadoProfesor($profesor['promedio']);
            $color_estado = $this->obtenerColorEstado($estado);
            $this->pdf->SetFillColor($color_estado[0], $color_estado[1], $color_estado[2]);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell($col_widths[4], 12, $estado, 1, 1, 'C', true);
            $this->pdf->SetTextColor(33, 37, 41);
        }
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Generar barra de progreso visual para satisfacción
     */
    private function generarBarraSatisfaccion($width, $height, $porcentaje) {
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        
        // Fondo de la barra (gris claro)
        $this->pdf->SetFillColor(233, 236, 239);
        $this->pdf->Rect($x + 2, $y + 3, $width - 4, $height - 6, 'F');
        
        // Barra de progreso (color según porcentaje)
        $barra_width = ($width - 4) * ($porcentaje / 100);
        $color_barra = $this->obtenerColorSatisfaccion($porcentaje);
        $this->pdf->SetFillColor($color_barra[0], $color_barra[1], $color_barra[2]);
        $this->pdf->Rect($x + 2, $y + 3, $barra_width, $height - 6, 'F');
        
        // Texto del porcentaje
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($width, $height, $porcentaje . '%', 1, 0, 'C');
        
        // Mover cursor a la siguiente posición
        $this->pdf->SetX($x + $width);
    }
    
    /**
     * Obtener color según promedio (escala 1-5)
     */
    private function obtenerColorPromedio($promedio) {
        if ($promedio >= 4.5) return [34, 197, 94];  // Verde
        if ($promedio >= 4.0) return [59, 130, 246]; // Azul
        if ($promedio >= 3.5) return [251, 191, 36]; // Amarillo
        if ($promedio >= 3.0) return [249, 115, 22]; // Naranja
        return [239, 68, 68];  // Rojo
    }
    
    /**
     * Obtener color para barra de satisfacción
     */
    private function obtenerColorSatisfaccion($porcentaje) {
        if ($porcentaje >= 80) return [34, 197, 94];  // Verde
        if ($porcentaje >= 60) return [59, 130, 246]; // Azul
        if ($porcentaje >= 40) return [251, 191, 36]; // Amarillo
        if ($porcentaje >= 20) return [249, 115, 22]; // Naranja
        return [239, 68, 68];  // Rojo
    }
    
    /**
     * Obtener estado del profesor según promedio
     */
    private function obtenerEstadoProfesor($promedio) {
        if ($promedio >= 4.5) return 'Excelente';
        if ($promedio >= 4.0) return 'Muy Bueno';
        if ($promedio >= 3.5) return 'Bueno';
        if ($promedio >= 3.0) return 'Regular';
        return 'Deficiente';
    }
    
    /**
     * Obtener color para badge de estado
     */
    private function obtenerColorEstado($estado) {
        switch ($estado) {
            case 'Excelente': return [34, 197, 94];   // Verde
            case 'Muy Bueno': return [59, 130, 246];  // Azul
            case 'Bueno': return [251, 191, 36];      // Amarillo
            case 'Regular': return [249, 115, 22];    // Naranja
            case 'Deficiente': return [239, 68, 68];  // Rojo
            default: return [107, 114, 128];          // Gris
        }
    }
    
    /**
     * Generar estadísticas por sección/categoría de preguntas
     */
    private function generarEstadisticasPorSeccion($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->Cell(0, 10, '📈 Estadísticas por Sección', 0, 1, 'L');
        $this->pdf->Ln(3);
        
        // Obtener estadísticas por categoría
        $stats_secciones = $this->obtenerEstadisticasPorCategoria($curso_id, $fecha);
        
        if (empty($stats_secciones)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 8, 'No hay datos por sección disponibles', 0, 1, 'L');
            return;
        }
        
        // Crear mini-tabla por cada sección
        $this->pdf->SetFont('helvetica', '', 9);
        
        foreach ($stats_secciones as $seccion) {
            // Título de la sección
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->SetFillColor(248, 249, 250);
            $this->pdf->Cell(95, 8, $seccion['categoria'], 1, 0, 'L', true);
            $this->pdf->Cell(30, 8, 'Promedio: ' . number_format($seccion['promedio'], 1), 1, 0, 'C');
            $this->pdf->Cell(30, 8, 'valor_texts: ' . $seccion['total'], 1, 0, 'C');
            
            // Mini barra de progreso
            $satisfaccion_seccion = ($seccion['promedio'] / 5) * 100;
            $this->generarMiniBarra(35, 8, $satisfaccion_seccion);
            $this->pdf->Ln();
        }
        
        $this->pdf->Ln(3);
    }
    
    /**
     * Generar mini barra de progreso
     */
    private function generarMiniBarra($width, $height, $porcentaje) {
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        
        // Fondo
        $this->pdf->SetFillColor(233, 236, 239);
        $this->pdf->Rect($x, $y, $width, $height, 'DF');
        
        // Barra
        $barra_width = $width * ($porcentaje / 100);
        $color = $this->obtenerColorSatisfaccion($porcentaje);
        $this->pdf->SetFillColor($color[0], $color[1], $color[2]);
        $this->pdf->Rect($x, $y, $barra_width, $height, 'F');
        
        // Borde
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Rect($x, $y, $width, $height, 'D');
        
        $this->pdf->SetX($x + $width);
    }
    
    /**
     * Obtener estadísticas reales para la tabla (replicando la imagen)
     */
    private function obtenerEstadisticasRealesParaTabla($curso_id, $fecha) {
        try {
            $estadisticas = [];
            
            // 1. Estadísticas del curso
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.nombre,
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(DISTINCT r.pregunta_id) as total_preguntas,
                    AVG(r.valor_int) as promedio
                FROM cursos c
                JOIN encuestas e ON c.id = e.curso_id
                JOIN valor_texts r ON e.id = r.encuesta_id
                JOIN preguntas p ON r.pregunta_id = p.id
                WHERE c.id = :curso_id
                AND DATE(e.fecha_envio) = :fecha
                AND p.tipo = 'escala'
                AND p.seccion = 'curso'
                GROUP BY c.id, c.nombre
            ");
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $curso_stats = $stmt->fetch();
            
            if ($curso_stats) {
                $estadisticas[] = [
                    'tipo' => 'CURSO',
                    'nombre' => $curso_stats['nombre'],
                    'encuestas' => $curso_stats['total_encuestas'],
                    'preguntas' => $curso_stats['total_preguntas'],
                    'puntuacion' => $curso_stats['promedio']
                ];
            }
            
            // 2. Estadísticas por profesor
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.nombre,
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(DISTINCT r.pregunta_id) as total_preguntas,
                    AVG(r.valor_int) as promedio
                FROM profesores p
                JOIN valor_texts r ON p.id = r.profesor_id
                JOIN encuestas e ON r.encuesta_id = e.id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id
                AND DATE(e.fecha_envio) = :fecha
                AND pr.tipo = 'escala'
                AND pr.seccion = 'profesor'
                GROUP BY p.id, p.nombre
                ORDER BY promedio DESC
            ");
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $profesores_stats = $stmt->fetchAll();
            
            foreach ($profesores_stats as $prof) {
                $estadisticas[] = [
                    'tipo' => 'PROFESOR',
                    'nombre' => $prof['nombre'],
                    'encuestas' => $prof['total_encuestas'],
                    'preguntas' => $prof['total_preguntas'],
                    'puntuacion' => $prof['promedio']
                ];
            }
              return $estadisticas;
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * SECCIONES DE COMENTARIOS - Replicar formato de la web
     */
    
    private function generarSeccionComentariosCurso($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(13, 110, 253);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   💬 COMENTARIOS DEL CURSO', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        // Obtener comentarios del curso
        $comentarios = $this->obtenerComentariosCurso($curso_id, $fecha);
        
        if (empty($comentarios)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No hay comentarios disponibles para este curso en la fecha especificada.', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // Generar tarjetas de comentarios
        $this->generarTarjetasComentarios($comentarios, 'curso');
        
        $this->pdf->Ln(10);
    }
    
    private function generarSeccionComentariosProfesores($curso_id, $fecha) {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetFillColor(13, 110, 253);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 12, '   👥 COMENTARIOS DE PROFESORES', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        // Obtener comentarios por profesor
        $comentarios_profesores = $this->obtenerComentariosProfesores($curso_id, $fecha);
        
        if (empty($comentarios_profesores)) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 10, 'No hay comentarios de profesores disponibles para este curso en la fecha especificada.', 0, 1, 'L');
            $this->pdf->Ln(10);
            return;
        }
        
        // Generar sección por cada profesor
        foreach ($comentarios_profesores as $profesor_id => $datos_profesor) {
            $this->generarSeccionProfesor($datos_profesor);
        }
        
        $this->pdf->Ln(10);
    }
    
    /**
     * Obtener comentarios del curso
     */
    private function obtenerComentariosCurso($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.valor_text,
                    r.fecha_valor_text,
                    p.pregunta,
                    pr.nombre as nombre_pregunta,
                    CONCAT(prof.nombre, ' ', prof.apellido) as evaluador
                FROM valor_texts r
                JOIN preguntas pr ON r.pregunta_id = pr.id
                JOIN encuestas e ON r.encuesta_id = e.id
                LEFT JOIN profesores prof ON r.profesor_id = prof.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND r.valor_text IS NOT NULL 
                AND r.valor_text != ''
                AND r.valor_text NOT REGEXP '^[1-5]$'
                AND LENGTH(r.valor_text) > 10
                ORDER BY r.fecha_valor_text DESC
                LIMIT 20
            ");
            
            $stmt->execute([
                ':curso_id' => $curso_id,
                ':fecha' => $fecha
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener comentarios agrupados por profesor
     */
    private function obtenerComentariosProfesores($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    prof.id as profesor_id,
                    CONCAT(prof.nombre, ' ', prof.apellido) as nombre_profesor,
                    prof.departamento,
                    r.valor_text,
                    r.fecha_valor_text,
                    pr.pregunta,
                    AVG(CASE WHEN r2.valor_text REGEXP '^[1-5]$' THEN CAST(r2.valor_text AS DECIMAL(3,2)) END) as promedio_profesor
                FROM profesores prof
                LEFT JOIN valor_texts r ON prof.id = r.profesor_id
                LEFT JOIN valor_texts r2 ON prof.id = r2.profesor_id
                LEFT JOIN preguntas pr ON r.pregunta_id = pr.id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND r.valor_text IS NOT NULL 
                AND r.valor_text != ''
                AND r.valor_text NOT REGEXP '^[1-5]$'
                AND LENGTH(r.valor_text) > 10
                GROUP BY prof.id, prof.nombre, prof.apellido, prof.departamento, r.valor_text, r.fecha_valor_text, pr.pregunta
                ORDER BY prof.nombre, r.fecha_valor_text DESC
            ");
            
            $stmt->execute([
                ':curso_id' => $curso_id,
                ':fecha' => $fecha
            ]);
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por profesor
            $comentarios_agrupados = [];
            foreach ($resultados as $fila) {
                $profesor_id = $fila['profesor_id'];
                
                if (!isset($comentarios_agrupados[$profesor_id])) {
                    $comentarios_agrupados[$profesor_id] = [
                        'nombre' => $fila['nombre_profesor'],
                        'departamento' => $fila['departamento'],
                        'promedio' => floatval($fila['promedio_profesor'] ?? 0),
                        'comentarios' => []
                    ];
                }
                
                if (!empty($fila['valor_text'])) {
                    $comentarios_agrupados[$profesor_id]['comentarios'][] = [
                        'texto' => $fila['valor_text'],
                        'pregunta' => $fila['pregunta'],
                        'fecha' => $fila['fecha_valor_text']
                    ];
                }
            }
            
            return $comentarios_agrupados;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Generar tarjetas de comentarios estilo web
     */
    private function generarTarjetasComentarios($comentarios, $tipo = 'curso') {
        $this->pdf->SetFont('helvetica', '', 10);
        
        $comentarios_por_pagina = 0;
        $max_comentarios_por_pagina = 8;
        
        foreach ($comentarios as $comentario) {
            // Verificar si necesitamos nueva página
            if ($comentarios_por_pagina >= $max_comentarios_por_pagina || $this->pdf->GetY() > 250) {
                $this->pdf->AddPage();
                $comentarios_por_pagina = 0;
            }
            
            // Tarjeta de comentario
            $this->generarTarjetaComentario($comentario, $tipo);
            $comentarios_por_pagina++;
        }
    }
    
    /**
     * Generar una tarjeta individual de comentario
     */
    private function generarTarjetaComentario($comentario, $tipo) {
        $x_inicial = $this->pdf->GetX();
        $y_inicial = $this->pdf->GetY();
        
        // Fondo de la tarjeta
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->SetDrawColor(222, 226, 230);
        $this->pdf->Rect($x_inicial, $y_inicial, 190, 25, 'DF');
        
        // Icono y header
        $this->pdf->SetXY($x_inicial + 5, $y_inicial + 3);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetTextColor(13, 110, 253);
        
        $icono = ($tipo === 'curso') ? '💬' : '👤';
        $header = ($tipo === 'curso') ? 'Comentario General' : 'Evaluación Profesor';
        $this->pdf->Cell(0, 5, $icono . ' ' . $header, 0, 1, 'L');
        
        // Evaluador/Fecha
        if (isset($comentario['evaluador']) && !empty($comentario['evaluador'])) {
            $this->pdf->SetXY($x_inicial + 5, $y_inicial + 8);
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->SetTextColor(108, 117, 125);
            $this->pdf->Cell(0, 4, 'Por: ' . $comentario['evaluador'] . ' | ' . date('d/m/Y', strtotime($comentario['fecha_valor_text'])), 0, 1, 'L');
        }
        
        // Texto del comentario
        $this->pdf->SetXY($x_inicial + 5, $y_inicial + 13);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetTextColor(33, 37, 41);
        
        // Limitar texto a 160 caracteres
        $texto = $comentario['valor_text'] ?? $comentario['texto'] ?? '';
        if (strlen($texto) > 160) {
            $texto = substr($texto, 0, 157) . '...';
        }
        
        $this->pdf->MultiCell(180, 4, $texto, 0, 'L');
        
        // Espacio entre tarjetas
        $this->pdf->SetY($y_inicial + 28);
    }
    
    /**
     * Generar sección de un profesor específico
     */
    private function generarSeccionProfesor($datos_profesor) {
        // Header del profesor
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetFillColor(33, 37, 41);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 10, '   👨‍🏫 ' . $datos_profesor['nombre'], 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        
        // Info del profesor
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(108, 117, 125);
        $info_profesor = '';
        if (!empty($datos_profesor['departamento'])) {
            $info_profesor .= 'Departamento: ' . $datos_profesor['departamento'] . ' | ';
        }
        $info_profesor .= 'Promedio: ' . number_format($datos_profesor['promedio'], 1) . '/5.0';
        
        $this->pdf->Cell(0, 8, $info_profesor, 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Comentarios del profesor
        if (!empty($datos_profesor['comentarios'])) {
            $this->generarTarjetasComentarios($datos_profesor['comentarios'], 'profesor');
        } else {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->SetTextColor(108, 117, 125);
            $this->pdf->Cell(0, 8, 'No hay comentarios específicos para este profesor.', 0, 1, 'L');
        }
          $this->pdf->Ln(8);
    }
    
    private function generarComentarios() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'COMENTARIOS Y SUGERENCIAS', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        try {
            // Obtener comentarios de las encuestas
            $stmt = $this->pdo->query("
                SELECT 
                    r.valor_text as comentario,
                    c.nombre as curso,
                    p.nombre as profesor,
                    e.fecha_envio
                FROM respuestas r
                JOIN encuestas e ON r.encuesta_id = e.id
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN profesores p ON r.profesor_id = p.id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE r.valor_text IS NOT NULL 
                AND r.valor_text != ''
                AND LENGTH(r.valor_text) > 10
                ORDER BY e.fecha_envio DESC
                LIMIT 20
            ");
            
            $comentarios = $stmt->fetchAll();
            
            if (empty($comentarios)) {
                $this->pdf->SetFont('helvetica', '', 11);
                $this->pdf->Cell(0, 10, 'No se encontraron comentarios detallados en las encuestas.', 0, 1);
                return;
            }
            
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 8, 'Total de comentarios encontrados: ' . count($comentarios), 0, 1);
            $this->pdf->Ln(5);
            
            foreach ($comentarios as $index => $comentario) {
                // Fondo alternado para cada comentario
                if ($index % 2 == 0) {
                    $this->pdf->SetFillColor(248, 249, 250);
                } else {
                    $this->pdf->SetFillColor(255, 255, 255);
                }
                
                // Información del comentario
                $this->pdf->SetFont('helvetica', 'B', 9);
                $this->pdf->SetTextColor(0, 123, 255);
                
                $info = ($comentario['curso'] ?? 'Sin curso') . ' - ' . ($comentario['profesor'] ?? 'Sin profesor');
                if ($comentario['fecha_envio']) {
                    $info .= ' (' . date('d/m/Y', strtotime($comentario['fecha_envio'])) . ')';
                }
                
                $this->pdf->Cell(0, 6, $info, 0, 1, 'L', true);
                
                // Contenido del comentario
                $this->pdf->SetFont('helvetica', '', 9);
                $this->pdf->SetTextColor(0, 0, 0);
                
                // Ajustar texto del comentario
                $comentario_texto = substr($comentario['comentario'], 0, 300);
                if (strlen($comentario['comentario']) > 300) {
                    $comentario_texto .= '...';
                }
                
                $this->pdf->MultiCell(0, 5, $comentario_texto, 0, 'L', true);
                $this->pdf->Ln(3);
            }
            
        } catch (Exception $e) {
            $this->pdf->SetFont('helvetica', '', 11);
            $this->pdf->SetTextColor(220, 53, 69);
            $this->pdf->Cell(0, 10, 'Error al cargar comentarios: ' . $e->getMessage(), 0, 1);
        }
        
        $this->pdf->Ln(10);
    }

    // ...existing code...
}
