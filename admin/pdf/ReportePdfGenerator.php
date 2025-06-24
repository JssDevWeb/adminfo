<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

class ReportePdfGenerator {
    private $pdo;
    private $pdf;
    
    public function __construct($pdo = null) {
        // Si se proporciona una conexión, asignarla
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            // Si no, intentar obtenerla automáticamente
            try {
                $db = Database::getInstance();
                $this->pdo = $db->getConnection();
            } catch (Exception $e) {
                // Si falla, registrar el error pero no detener la creación del objeto
                // La conexión debe establecerse antes de usar métodos que requieran BD            error_log("Error al inicializar conexión a base de datos en ReportePdfGenerator: " . $e->getMessage());
            }
        }
        
        $this->pdf = new TCPDF();
        $this->configurarPdf();
    }
    
    private function configurarPdf() {
        // Configurar para mejorar soporte UTF-8
        $this->pdf->SetDefaultMonospacedFont('courier');
        
        $this->pdf->SetCreator('Sistema de Encuestas Academicas');
        $this->pdf->SetAuthor('Academia');
        $this->pdf->SetTitle('Reporte de Encuestas');
        $this->pdf->SetSubject('Reporte de resultados de encuestas academicas');
        $this->pdf->SetKeywords('encuestas, academicas, reporte, profesores');
        
        // Configurar márgenes
        $this->pdf->SetMargins(15, 20, 15);
        $this->pdf->SetHeaderMargin(10);
        $this->pdf->SetFooterMargin(10);
        
        // Configurar fuente con soporte UTF-8
        $this->pdf->SetFont('dejavusans', '', 10);
        
        // Configurar header y footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(true);
        
        // Configurar auto-break para mejor manejo de páginas
        $this->pdf->SetAutoPageBreak(TRUE, 15);
    }
    
    public function generarReporte($secciones = []) {
        $this->pdf->AddPage();
        
        // Título principal
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'REPORTE DE ENCUESTAS ACADÉMICAS', 0, 1, 'C');
        $this->pdf->Ln(5);
        
        // Fecha de generación
        $this->pdf->SetFont('dejavusans', '', 10);
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
                    $this->pdf->SetFont('dejavusans', 'I', 10);
                    $this->pdf->Cell(0, 10, "Sección '$seccion' no reconocida.", 0, 1);
                    $this->pdf->Ln(5);
                    break;
            }}
        
        // Si no hay secciones, agregar una página básica con información
        if (empty($secciones)) {
            $this->pdf->SetFont('dejavusans', '', 12);
            $this->pdf->Cell(0, 10, 'Reporte generado sin secciones específicas.', 0, 1);
            $this->pdf->Cell(0, 10, 'Para ver contenido detallado, especifique las secciones deseadas.', 0, 1);
        }
        
        return $this->pdf->Output('reporte_encuestas_' . date('Y-m-d_H-i-s') . '.pdf', 'S');
    }
    
    private function generarResumenGeneral() {
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'RESUMEN GENERAL', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Obtener estadísticas generales
        $stats = $this->obtenerEstadisticasGenerales();
        
        $this->pdf->SetFont('dejavusans', '', 10);
        
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
        $this->pdf->SetFont('dejavusans', 'B', 14);
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
        $this->pdf->SetFont('dejavusans', 'B', 14);
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
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'ANÁLISIS POR PREGUNTA', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $preguntas = $this->obtenerAnalisisPreguntas();
        
        foreach ($preguntas as $pregunta) {
            $this->pdf->SetFont('dejavusans', 'B', 11);
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
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'GRÁFICOS Y VISUALIZACIONES', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->Cell(0, 10, 'Nota: Los gráficos interactivos no se pueden exportar a PDF.', 0, 1, 'L');
        $this->pdf->Cell(0, 10, 'Para ver los gráficos, consulte la versión web del reporte.', 0, 1, 'L');
        $this->pdf->Ln(10);
        
        // Aquí se podrían agregar gráficos estáticos generados con librerías como Chart.js
        // Por ahora, incluimos un resumen textual
        $this->generarResumenGraficos();
    }
    
    private function generarResumenGraficos() {
        $datos = $this->obtenerDatosParaGraficos();
        
        $this->pdf->SetFont('dejavusans', 'B', 12);
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
                    AVG(r.valor_int) as promedio
                FROM profesores p
                LEFT JOIN respuestas r ON p.id = r.profesor_id
                WHERE r.valor_int IS NOT NULL AND r.valor_int BETWEEN 1 AND 5
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
                SELECT                    c.nombre as nombre_curso,
                    p.nombre as nombre_profesor,
                    COUNT(r.id) as total_encuestas,
                    AVG(r.valor_int) as promedio
                FROM cursos c
                JOIN encuestas e ON c.id = e.curso_id
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN profesores p ON r.profesor_id = p.id
                WHERE r.valor_int IS NOT NULL AND r.valor_int BETWEEN 1 AND 5
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
            $stmt = $this->pdo->query("SELECT id, texto FROM preguntas ORDER BY orden");
            $preguntasData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($preguntasData as $pregunta) {
                $estadisticas = [];
                  // Obtener estadísticas por valor para esta pregunta
                $stmt = $this->pdo->prepare("
                    SELECT 
                        valor_int,
                        COUNT(*) as cantidad,
                        (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM respuestas WHERE pregunta_id = ?)) as porcentaje
                    FROM respuestas 
                    WHERE pregunta_id = ? AND valor_int IS NOT NULL
                    GROUP BY valor_int
                    ORDER BY valor_int
                ");
                
                $stmt->execute([$pregunta['id'], $pregunta['id']]);
                $valores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calcular promedio y desviación para valores numéricos
                $stmt = $this->pdo->prepare("
                    SELECT 
                        AVG(valor_int) as promedio,
                        STDDEV(valor_int) as desviacion
                    FROM respuestas 
                    WHERE pregunta_id = ? AND valor_int IS NOT NULL AND valor_int BETWEEN 1 AND 5
                ");
                
                $stmt->execute([$pregunta['id']]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                foreach ($valores as $valor) {                    $estadisticas[] = [
                        'valor' => $valor['valor_int'],
                        'cantidad' => $valor['cantidad'],
                        'porcentaje' => $valor['porcentaje'],
                        'promedio' => $stats['promedio'] ?? 0,
                        'desviacion' => $stats['desviacion'] ?? 0
                    ];
                }
                
                $preguntas[] = [
                    'texto_pregunta' => $pregunta['texto'],
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
            
            // Distribución de respuestas por escala
            $stmt = $this->pdo->query("
                SELECT 
                    valor_int, 
                    COUNT(*) as cantidad 
                FROM respuestas 
                WHERE valor_int IS NOT NULL AND valor_int BETWEEN 1 AND 5 
                GROUP BY valor_int 
                ORDER BY valor_int
            ");
            
            $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($distribucion as $item) {
                $datos["Respuestas con valor " . $item['valor_int']] = $item['cantidad'];
            }
            
            // Promedio por departamento (si existe)
            $stmt = $this->pdo->query("
                SELECT 
                    p.departamento,
                    AVG(r.valor_int) as promedio
                FROM profesores p
                JOIN respuestas r ON p.id = r.profesor_id
                WHERE r.valor_int IS NOT NULL AND r.valor_int BETWEEN 1 AND 5 AND p.departamento IS NOT NULL
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
    }    /**
     * Obtener estadísticas detalladas por profesor
     */
    private function obtenerEstadisticasPorProfesor($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.nombre,
                    COUNT(r.id) as total_respuestas,
                    AVG(CASE WHEN r.valor_int BETWEEN 1 AND 5 THEN r.valor_int END) as promedio,
                    (COUNT(CASE WHEN r.valor_int IN (4, 5) THEN 1 END) * 100.0 / COUNT(CASE WHEN r.valor_int BETWEEN 1 AND 5 THEN 1 END)) as satisfaccion
                FROM profesores p
                LEFT JOIN respuestas r ON p.id = r.profesor_id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY p.id, p.nombre
                HAVING total_respuestas > 0
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
                $profesor['total_respuestas'] = intval($profesor['total_respuestas']);
            }
            
            return $resultados;
            
        } catch (Exception $e) {
            return [];
        }
    }
      /**
     * Obtener estadísticas por categoría de preguntas
     */    private function obtenerEstadisticasPorCategoria($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(pr.seccion, 'General') as categoria,
                    COUNT(r.id) as total,
                    AVG(CASE WHEN r.valor_int BETWEEN 1 AND 5 THEN r.valor_int END) as promedio
                FROM preguntas pr
                LEFT JOIN respuestas r ON pr.id = r.pregunta_id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY pr.seccion
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
     * ==========================================
     * FUNCIONES PARA GRÁFICOS DE TORTA EN PDF
     * ==========================================
     */
    
    /**
     * Generar sección de gráficos de torta como en la web
     */    
    private function generarGraficosEvaluacion($curso_id, $fecha) {
        $datos_graficos = $this->obtenerDatosGraficos($curso_id, $fecha);

        if (empty($datos_graficos)) {
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay datos suficientes para generar gráficos.', 0, 1);
            $this->pdf->Ln(5);
            return;
        }

        // Primero generar la tabla de aprovechamiento
        $this->generarTablaAprovechamiento($curso_id, $fecha);
        
        // Luego generar los gráficos
        $this->pdf->AddPage();
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'GRÁFICOS DE EVALUACIÓN', 0, 1, 'L');
        $this->pdf->Ln(5);

        // Configuración mejorada para los gráficos
        $pageWidth = $this->pdf->getPageWidth();
        $pageHeight = $this->pdf->getPageHeight();
        $margenIzquierdo = 20;
        $margenDerecho = 20;
        $margenSuperior = 40; // Espacio para el título de la página
        $margenInferior = 25;
        
        // Ancho disponible para contenido
        $anchoDisponible = $pageWidth - $margenIzquierdo - $margenDerecho;
        
        // Configuración del gráfico y leyenda
        $chartR = 35; // Radio del gráfico
        $leyendaAncho = 70; // Ancho para la leyenda
        $espacioEntreGraficoYLeyenda = 5;
        $espacioEntreGraficos = 20; // Espacio vertical entre gráficos
        
        // Disposición horizontal mejorada
        $chartX = $margenIzquierdo + $chartR; // Centro X del gráfico
        $leyendaX = $chartX + $chartR + $espacioEntreGraficoYLeyenda; // Posición X de la leyenda
        
        $chartY = $margenSuperior + $chartR; // Posición Y inicial (centro del gráfico)
        
        // Procesamos cada gráfico
        foreach ($datos_graficos as $indice => $grafico) {
            // Si el siguiente gráfico no cabe en la página actual, crear una nueva
            if ($chartY + $chartR + $espacioEntreGraficos > $pageHeight - $margenInferior) {
                $this->pdf->AddPage();
                $this->pdf->SetFont('dejavusans', 'B', 14);
                $this->pdf->Cell(0, 10, 'GRÁFICOS DE EVALUACIÓN (Continuación)', 0, 1, 'L');
                $this->pdf->Ln(5);
                $chartY = $margenSuperior + $chartR;
            }
            
            // Calcular el aprovechamiento
            $aprovechamiento = $grafico['max_puntuacion'] > 0 ? 
                round(($grafico['puntuacion_real'] / $grafico['max_puntuacion']) * 100, 1) : 0;
            
            // Crear etiqueta según el tipo (curso o profesor)
            $tipo_etiqueta = $grafico['tipo'] == 'curso' ? 'CURSO' : 'PROFESOR';
            
            // Guardar la posición Y antes del título para mantener referencia
            $posYTitulo = $this->pdf->GetY();
            
            // Título del gráfico con fondo
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->SetFillColor(230, 235, 245); // Color de fondo más suave
            $this->pdf->Cell(0, 10, ($indice + 1) . '. ' . mb_strtoupper($tipo_etiqueta) . ': ' . $grafico['nombre'], 0, 1, 'L', true);
            
            // Información adicional a la izquierda del gráfico (no encima)
            // Subtítulos informativos con formato mejorado
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell($anchoDisponible, 8, 'Encuestas: ' . $grafico['total_encuestas'] . ' | Preguntas: ' . $grafico['num_preguntas'], 0, 1, 'L');
            
            // Puntuación y aprovechamiento con mejor formato
            $this->pdf->SetFont('dejavusans', 'B', 10);
            
            // Colorear el aprovechamiento según su valor
            if ($aprovechamiento >= 90) {
                $this->pdf->SetTextColor(46, 139, 87); // Verde oscuro para excelente
            } elseif ($aprovechamiento >= 70) {
                $this->pdf->SetTextColor(30, 144, 255); // Azul para bueno
            } elseif ($aprovechamiento >= 50) {
                $this->pdf->SetTextColor(255, 165, 0); // Naranja para aceptable
            } else {
                $this->pdf->SetTextColor(220, 20, 60); // Rojo para bajo
            }
            
            $puntuacion_texto = "Puntuación: " . $grafico['puntuacion_real'] . " de " . $grafico['max_puntuacion'];
            $aprovechamiento_texto = "Aprovechamiento: " . $aprovechamiento . "%";
            
            $this->pdf->Cell($anchoDisponible, 8, $puntuacion_texto . ' | ' . $aprovechamiento_texto, 0, 1, 'L');
            
            // Restaurar el color del texto
            $this->pdf->SetTextColor(0, 0, 0);
            
            // Calcular altura del título y datos (espacio usado)
            $alturaInfoPrevia = $this->pdf->GetY() - $posYTitulo;
            
            // Establecer posición Y del gráfico después de la información
            // Asegurar espacio suficiente para el gráfico
            $chartY = $this->pdf->GetY() + $chartR;
            
            // Dibujar el gráfico de torta con posición específica para la leyenda
            $this->dibujarGraficoTortaOptimizado($chartX, $chartY, $chartR, "", $grafico['categorias'], $leyendaX);
            
            // Actualizar posición Y para el próximo gráfico - después de verificar dónde terminó la leyenda
            $chartY = $this->pdf->GetY() + $espacioEntreGraficos;
            
            // Añadir línea divisoria entre gráficos excepto después del último
            if ($indice < count($datos_graficos) - 1) {
                $this->pdf->SetDrawColor(200, 200, 200);
                $this->pdf->Line($margenIzquierdo, $chartY - $espacioEntreGraficos/2, $pageWidth - $margenDerecho, $chartY - $espacioEntreGraficos/2);
            }
        }
    }
    
    /**
     * Dibuja un sector de un gráfico de torta.
     */
    private function dibujarSectorTorta($xc, $yc, $r, $a, $b, $color) {
        // Establecer color de relleno
        $this->pdf->SetFillColorArray($color);
        
        // Puntos para el polígono que forma el sector
        $puntos = array($xc, $yc); // El centro como primer punto
        
        // Aumentar el número de segmentos para sectores más suaves
        $n = 40; // Mayor número para curvas más suaves
        
        // Crear los puntos del sector
        for ($i = 0; $i <= $n; $i++) {
            $angulo = $a + ($b - $a) * $i / $n;
            $puntos[] = $xc + $r * cos(deg2rad($angulo));
            $puntos[] = $yc + $r * sin(deg2rad($angulo));
        }
        
        // Dibujar el sector con relleno
        $this->pdf->Polygon($puntos, 'F');
        
        // Dibujar líneas desde el centro hasta los bordes para definir mejor los sectores
        // especialmente importante en sectores pequeños
        $this->pdf->SetDrawColor(255, 255, 255);  // Líneas blancas para mejor contraste
        $this->pdf->SetLineWidth(0.2);
        
        // Línea al punto inicial del arco
        $x_inicio = $xc + $r * cos(deg2rad($a));
        $y_inicio = $yc + $r * sin(deg2rad($a));
        $this->pdf->Line($xc, $yc, $x_inicio, $y_inicio);
        
        // Línea al punto final del arco
        $x_fin = $xc + $r * cos(deg2rad($b));
        $y_fin = $yc + $r * sin(deg2rad($b));
        $this->pdf->Line($xc, $yc, $x_fin, $y_fin);
    }
    
    /**
     * Dibuja un gráfico de torta completo con su leyenda.
     */
    private function dibujarGraficoTorta($xc, $yc, $r, $titulo, $categorias) {
        // Aplicar título si existe
        if (!empty($titulo)) {
            $this->pdf->SetFont('dejavusans', 'B', 11);
            $this->pdf->MultiCell(0, 8, $titulo, 0, 'L');
            $this->pdf->Ln(2);
        }

        // Si no hay categorías con porcentajes, mostrar mensaje vacío
        $hay_datos = false;
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $hay_datos = true;
                break;
            }
        }
        
        if (!$hay_datos) {
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para este gráfico', 0, 1, 'C');
            return;
        }

        $angulo_inicio = 0;
        $offset_text = 5; // Espacio entre el borde del gráfico y la leyenda

        // Dibujar sectores con mejor definición
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $angulo_fin = $angulo_inicio + ($cat['porcentaje'] / 100) * 360;
                $this->dibujarSectorTorta($xc, $yc, $r, $angulo_inicio, $angulo_fin, $cat['color_rgb']);
                $angulo_inicio = $angulo_fin;
            }
        }
        
        // Dibujar línea negra alrededor del círculo completo para mejor definición
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Circle($xc, $yc, $r);

        // Dibujar leyenda en posición optimizada
        $leyendaX = $xc + $r + $offset_text;
        $leyendaY = $yc - $r; // Alinear con el tope del círculo
        $altoCaja = 5; // Alto de cada elemento de la leyenda
        $anchoCaja = 5; // Ancho del cuadrado de color
        $espacioEntreCajas = 2; // Espacio entre elementos
        $maxAnchoTexto = 55; // Ancho máximo para el texto de la leyenda

        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->SetXY($leyendaX, $leyendaY);

        foreach ($categorias as $cat) {
            if (isset($cat['nombre']) && isset($cat['porcentaje']) && isset($cat['color_rgb']) && $cat['porcentaje'] > 0) {
                $this->pdf->SetFillColorArray($cat['color_rgb']);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'F');
                
                // Dibujar borde negro alrededor del cuadrado de color para mejor definición
                $this->pdf->SetDrawColor(0, 0, 0);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'D');
                
                // Texto de la leyenda con porcentaje
                $textoLeyenda = ' ' . $cat['nombre'] . ' (' . $cat['porcentaje'] . '%)';
                
                // Usar MultiCell con ancho fijo para manejar textos largos
                $this->pdf->SetXY($leyendaX + $anchoCaja + 1, $this->pdf->GetY());
                $this->pdf->MultiCell($maxAnchoTexto, $altoCaja, $textoLeyenda, 0, 'L');
                
                // Mover a la siguiente posición
                $this->pdf->SetXY($leyendaX, $this->pdf->GetY() + $espacioEntreCajas);
            }
        }
    }
      /**
     * Dibuja un gráfico de torta con leyenda en posición optimizada a la derecha.
     * Versión mejorada para evitar superposiciones.
     */
    private function dibujarGraficoTortaOptimizado($xc, $yc, $r, $titulo, $categorias, $leyendaX) {
        // Aplicar título si existe
        if (!empty($titulo)) {
            $this->pdf->SetFont('dejavusans', 'B', 11);
            $this->pdf->MultiCell(0, 8, $titulo, 0, 'L');
            $this->pdf->Ln(2);
        }

        // Si no hay categorías con porcentajes, mostrar mensaje vacío
        $hay_datos = false;
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $hay_datos = true;
                break;
            }
        }
        
        if (!$hay_datos) {
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para este gráfico', 0, 1, 'C');
            return;
        }

        $angulo_inicio = 0;

        // Dibujar sectores con mejor definición
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $angulo_fin = $angulo_inicio + ($cat['porcentaje'] / 100) * 360;
                $this->dibujarSectorTorta($xc, $yc, $r, $angulo_inicio, $angulo_fin, $cat['color_rgb']);
                $angulo_inicio = $angulo_fin;
            }
        }
        
        // Dibujar línea negra alrededor del círculo completo para mejor definición
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Circle($xc, $yc, $r);

        // Configuración para la leyenda
        $leyendaY = $yc - $r; // Alinear con el tope del círculo
        $altoCaja = 5; // Alto de cada elemento de la leyenda
        $anchoCaja = 5; // Ancho del cuadrado de color
        $espacioEntreCajas = 2; // Espacio entre elementos
        $maxAnchoTexto = 55; // Ancho máximo para el texto de la leyenda

        // Dibujar leyenda en posición explícita a la derecha
        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->SetXY($leyendaX, $leyendaY);
        
        // Calcular altura máxima entre el gráfico y la leyenda
        $alturaInicialLeyenda = $this->pdf->GetY();
        $alturaMaxima = $yc + $r; // Altura máxima del gráfico (centro + radio)
        
        foreach ($categorias as $cat) {
            if (isset($cat['nombre']) && isset($cat['porcentaje']) && isset($cat['color_rgb']) && $cat['porcentaje'] > 0) {
                $this->pdf->SetFillColorArray($cat['color_rgb']);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'F');
                
                // Dibujar borde negro alrededor del cuadrado de color
                $this->pdf->SetDrawColor(0, 0, 0);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'D');
                
                // Texto de la leyenda con porcentaje
                $textoLeyenda = ' ' . $cat['nombre'] . ' (' . $cat['porcentaje'] . '%)';
                
                // Usar MultiCell con ancho fijo para manejar textos largos
                $this->pdf->SetXY($leyendaX + $anchoCaja + 1, $this->pdf->GetY());
                $this->pdf->MultiCell($maxAnchoTexto, $altoCaja, $textoLeyenda, 0, 'L');
                
                // Mover a la siguiente posición
                $this->pdf->SetXY($leyendaX, $this->pdf->GetY() + $espacioEntreCajas);
            }
        }
        
        // Determinar cuál es mayor: la altura del gráfico o la altura de la leyenda
        $alturaFinalLeyenda = $this->pdf->GetY();
        $alturaLeyenda = $alturaFinalLeyenda - $alturaInicialLeyenda;
        
        // Establecer la posición Y del cursor después del elemento más alto
        $nuevaY = max($alturaMaxima, $alturaFinalLeyenda);
        $this->pdf->SetY($nuevaY);
        
        // Añadir un pequeño espacio después del gráfico
        $this->pdf->Ln(5);
    }
    
    /**
     * Agrupa las respuestas en categorías según su valor
     */
    private function agruparEnCategorias($distribucion_raw) {
        // Definir las categorías y sus valores correspondientes
        // Usamos el valor específico para cada categoría según los requisitos:
        // Excelente=10, Bueno=7, Correcto=5, Regular=3, Deficiente=1
        $categorias = [
            'Excelente'  => ['valores' => [10, 9], 'color' => [46, 139, 87], 'valor_asignado' => 10],   // Verde
            'Bueno'      => ['valores' => [8, 7], 'color' => [30, 144, 255], 'valor_asignado' => 7],    // Azul
            'Correcto'   => ['valores' => [6, 5], 'color' => [255, 215, 0], 'valor_asignado' => 5],     // Amarillo
            'Regular'    => ['valores' => [4, 3], 'color' => [255, 140, 0], 'valor_asignado' => 3],     // Naranja
            'Deficiente' => ['valores' => [2, 1], 'color' => [220, 20, 60], 'valor_asignado' => 1]      // Rojo
        ];

        // Inicializar contadores y total
        $total_respuestas = 0;
        $total_puntos = 0;
        $conteo_categorias = [];
        
        foreach ($categorias as $nombre => $info) {
            $conteo_categorias[$nombre] = 0;
        }

        // Procesar cada resultado y agruparlo en su categoría
        foreach ($distribucion_raw as $item) {
            $valor_int = (int)$item['valor_int'];
            $cantidad = (int)$item['cantidad'];
            $total_respuestas += $cantidad;
            
            foreach ($categorias as $nombre => $info) {
                if (in_array($valor_int, $info['valores'])) {
                    $conteo_categorias[$nombre] += $cantidad;
                    $total_puntos += $cantidad * $info['valor_asignado'];
                    break;
                }
            }
        }

        // Crear array final con los datos procesados para el gráfico
        $datos_categorias = [];
        foreach ($categorias as $nombre => $info) {
            $conteo = $conteo_categorias[$nombre];
            $porcentaje = $total_respuestas > 0 ? round(($conteo / $total_respuestas) * 100, 1) : 0;
            
            // Solo añadir categorías que tengan valores
            if ($conteo > 0 || $porcentaje > 0) {
                $datos_categorias[] = [
                    'nombre' => $nombre,
                    'cantidad' => $conteo,
                    'porcentaje' => $porcentaje,
                    'color_rgb' => $info['color'],
                    'valor_asignado' => $info['valor_asignado']
                ];
            }
        }

        return $datos_categorias;
    }    /**
     * Obtiene los datos para todos los gráficos (curso y profesores).
     */
    private function obtenerDatosGraficos($curso_id, $fecha) {
        $graficos = [];

        // 1. Gráfico del curso
        try {
            // Obtener información del curso
            $stmt_info = $this->pdo->prepare("SELECT c.id, c.nombre FROM cursos c WHERE c.id = :curso_id");
            $stmt_info->execute([':curso_id' => $curso_id]);
            $curso = $stmt_info->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) return [];

            // Contar total de encuestas para el curso
            $stmt_encuestas = $this->pdo->prepare("
                SELECT COUNT(DISTINCT e.id) as total_encuestas
                FROM encuestas e
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id
                AND DATE(e.fecha_envio) = :fecha
                AND pr.seccion = 'curso' AND pr.tipo = 'escala'
            ");
            $stmt_encuestas->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $total_encuestas = $stmt_encuestas->fetch(PDO::FETCH_ASSOC)['total_encuestas'] ?? 0;
            
            // Contar número de preguntas de curso tipo escala
            $stmt_preguntas = $this->pdo->prepare("
                SELECT COUNT(*) as num_preguntas
                FROM preguntas
                WHERE seccion = 'curso' AND tipo = 'escala' AND activa = 1
            ");
            $stmt_preguntas->execute();
            $num_preguntas = $stmt_preguntas->fetch(PDO::FETCH_ASSOC)['num_preguntas'] ?? 0;
            
            // Obtener distribución para generar el gráfico
            $stmt_curso = $this->pdo->prepare("
                SELECT r.valor_int, COUNT(*) as cantidad
                FROM encuestas e
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                  AND pr.seccion = 'curso' AND pr.tipo = 'escala'
                GROUP BY r.valor_int
                ORDER BY r.valor_int DESC
            ");
            $stmt_curso->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $distribucion_curso = $stmt_curso->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($distribucion_curso)) {
                // Calcular puntuación total basada en los valores reales asignados (10, 7, 5, 3, 1)
                $categorias = $this->agruparEnCategorias($distribucion_curso);
                $puntuacion_real = 0;
                $total_respuestas = 0;
                
                foreach ($categorias as $categoria) {
                    $puntuacion_real += $categoria['cantidad'] * $categoria['valor_asignado'];
                    $total_respuestas += $categoria['cantidad'];
                }
                
                // Para cursos: el valor máximo es 100
                // Calculamos el máximo teórico: total_encuestas * num_preguntas * 10 (valor máximo por respuesta)
                $max_puntuacion = $total_encuestas * $num_preguntas * 10;

                // Si hay un problema con los datos, usar un valor predeterminado seguro
                if ($max_puntuacion <= 0 || $num_preguntas <= 0) {
                    $max_puntuacion = $total_encuestas * 100; // 100 puntos por encuesta (estándar para cursos)
                }

                $graficos[] = [
                    'tipo' => 'curso',
                    'titulo' => 'Evaluación General del Curso: ' . $curso['nombre'],
                    'nombre' => $curso['nombre'],
                    'total_encuestas' => $total_encuestas,
                    'num_preguntas' => $num_preguntas,
                    'puntuacion_real' => $puntuacion_real,
                    'max_puntuacion' => $max_puntuacion,
                    'categorias' => $categorias
                ];
            }
        } catch (Exception $e) {
            // Registrar error
            error_log("Error al obtener datos del curso: " . $e->getMessage());
        }

        // 2. Gráficos de profesores
        try {
            $stmt_profesores = $this->pdo->prepare("
                SELECT DISTINCT p.id, p.nombre 
                FROM profesores p 
                JOIN respuestas r ON p.id = r.profesor_id 
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
            ");
            $stmt_profesores->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $profesores = $stmt_profesores->fetchAll(PDO::FETCH_ASSOC);

            foreach ($profesores as $profesor) {
                // Contar encuestas para este profesor
                $stmt_encuestas = $this->pdo->prepare("
                    SELECT COUNT(DISTINCT e.id) as total_encuestas
                    FROM encuestas e
                    JOIN respuestas r ON e.id = r.encuesta_id
                    WHERE e.curso_id = :curso_id
                    AND r.profesor_id = :profesor_id
                    AND DATE(e.fecha_envio) = :fecha
                ");
                $stmt_encuestas->execute([
                    ':curso_id' => $curso_id, 
                    ':profesor_id' => $profesor['id'],
                    ':fecha' => $fecha
                ]);
                $total_encuestas = $stmt_encuestas->fetch(PDO::FETCH_ASSOC)['total_encuestas'] ?? 0;
                
                // Contar número de preguntas de profesor tipo escala
                $stmt_preguntas = $this->pdo->prepare("
                    SELECT COUNT(*) as num_preguntas
                    FROM preguntas
                    WHERE seccion = 'profesor' AND tipo = 'escala' AND activa = 1
                ");
                $stmt_preguntas->execute();
                $num_preguntas = $stmt_preguntas->fetch(PDO::FETCH_ASSOC)['num_preguntas'] ?? 0;
                
                // Obtener distribución para el gráfico
                $stmt_prof = $this->pdo->prepare("
                    SELECT r.valor_int, COUNT(*) as cantidad
                    FROM respuestas r
                    JOIN encuestas e ON r.encuesta_id = e.id
                    JOIN preguntas pr ON r.pregunta_id = pr.id
                    WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                      AND r.profesor_id = :profesor_id AND pr.seccion = 'profesor' AND pr.tipo = 'escala'
                    GROUP BY r.valor_int
                    ORDER BY r.valor_int DESC
                ");
                $stmt_prof->execute([
                    ':curso_id' => $curso_id, 
                    ':fecha' => $fecha, 
                    ':profesor_id' => $profesor['id']
                ]);
                $distribucion_prof = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($distribucion_prof)) {
                    // Calcular puntuación real usando los valores asignados
                    $categorias = $this->agruparEnCategorias($distribucion_prof);
                    $puntuacion_real = 0;
                    $total_respuestas = 0;
                    
                    foreach ($categorias as $categoria) {
                        $puntuacion_real += $categoria['cantidad'] * $categoria['valor_asignado'];
                        $total_respuestas += $categoria['cantidad'];
                    }
                    
                    // Para profesores: el valor máximo es 70
                    // Calculamos el máximo teórico: total_encuestas * num_preguntas * 10 (valor máximo por respuesta)
                    $max_puntuacion = $total_encuestas * $num_preguntas * 10;
                    
                    // Si hay un problema con los datos, usar un valor predeterminado seguro
                    if ($max_puntuacion <= 0 || $num_preguntas <= 0) {
                        $max_puntuacion = $total_encuestas * 70; // 70 puntos por encuesta (estándar para profesores)
                    }

                    $graficos[] = [
                        'tipo' => 'profesor',
                        'titulo' => 'Evaluación de: ' . $profesor['nombre'],
                        'nombre' => $profesor['nombre'],
                        'total_encuestas' => $total_encuestas,
                        'num_preguntas' => $num_preguntas,
                        'puntuacion_real' => $puntuacion_real,
                        'max_puntuacion' => $max_puntuacion,
                        'categorias' => $categorias
                    ];
                }
            }
        } catch (Exception $e) {
            // Registrar error
            error_log("Error al obtener datos de profesores: " . $e->getMessage());
        }
        
        return $graficos;
    }

    /**
     * Genera una tabla con estilos mejorados.
     */
    private function generarTablaEstilizada($headers, $data, $widths, $title = '') {
        // Verificar si tenemos suficiente espacio en la página actual
        $alturaEstimada = 20; // Altura para el título
        $alturaEstimada += 7; // Altura para la cabecera
        $alturaEstimada += count($data) * 8; // Altura estimada para las filas (8 puntos por fila)
        $alturaEstimada += 15; // Margen adicional
        
        $espacioRestante = $this->pdf->getPageHeight() - $this->pdf->GetY();
        
        // Si no hay suficiente espacio, agregar una nueva página
        if ($espacioRestante < $alturaEstimada) {
            $this->pdf->AddPage();
        }
        
        // Título de la tabla con estilo mejorado
        if (!empty($title)) {
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->SetFillColor(240, 240, 240);
            $this->pdf->Cell(0, 10, $title, 0, 1, 'L', true);
            $this->pdf->Ln(2);
        }

        // Cabecera con mejor contraste y legibilidad
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColor(220, 230, 242); // Color más suave y elegante
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(180, 180, 180);
        $this->pdf->SetLineWidth(0.2);

        $sumAnchos = array_sum($widths);
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $this->pdf->Ln();

        // Datos con filas alternas para mejor lectura
        $this->pdf->SetFont('dejavusans', '', 8);
        $fill = false;

        foreach ($data as $row) {
            // Determinar la altura necesaria para cada celda
            $maxHeight = 6; // Altura mínima
            foreach ($row as $i => $cell) {
                $height = $this->pdf->getStringHeight($widths[$i], (string)$cell, false, true, '', 1);
                if ($height > $maxHeight) {
                    $maxHeight = $height;
                }
            }

            // Alternar colores para mejor legibilidad
            $this->pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            
            // Dibujar las celdas con la misma altura
            foreach ($row as $i => $cell) {
                // Alineación según el tipo de contenido (centrar números, alinear a la izquierda el texto)
                $align = is_numeric($cell) && !is_string($cell) ? 'C' : 'L';
                $this->pdf->MultiCell($widths[$i], $maxHeight, (string)$cell, 1, $align, $fill, 0, '', '', true, 0, false, true, $maxHeight, 'M');
            }
            $this->pdf->Ln();
            $fill = !$fill;
        }
        
        // Espacio después de la tabla más compacto
        $this->pdf->Ln(6);
    }

    /**
     * Genera una tabla con estilos modernos y compactos similar al diseño web
     */
    private function generarTablaEstilizadaCompacta($headers, $data, $widths, $title = '') {
        // Colores modernos para la tabla
        $colorCabecera = [41, 128, 185]; // Azul moderno
        $colorBordes = [189, 195, 199]; // Gris claro
        $colorFila1 = [245, 245, 245]; // Casi blanco
        $colorFila2 = [240, 240, 240]; // Gris muy claro
        $colorTexto = [44, 62, 80]; // Azul oscuro
        
        // Espaciado compacto
        $alturaCabecera = 8;
        $alturaFila = 7;
        $padding = 2;
        
        // Título de la tabla (opcional)
        if (!empty($title)) {
            $this->pdf->SetFont('dejavusans', 'B', 13);
            $this->pdf->SetFillColor(236, 240, 241); // Gris muy suave
            $this->pdf->Cell(0, 10, $title, 0, 1, 'L', true);
            $this->pdf->Ln(1); // Muy poco espacio después del título
        }

        // Configuración para la cabecera
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColorArray($colorCabecera);
        $this->pdf->SetTextColor(255, 255, 255); // Texto blanco para contraste
        $this->pdf->SetDrawColorArray($colorBordes);
        $this->pdf->SetLineWidth(0.2);

        // Dibujar la cabecera
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], $alturaCabecera, $header, 1, 0, 'C', 1);
        }
        $this->pdf->Ln();

        // Restaurar color de texto para los datos
        $this->pdf->SetTextColorArray($colorTexto);
        $this->pdf->SetFont('dejavusans', '', 8);
        
        // Alternar colores para filas de datos
        $fill = false;

        // Dibujar filas de datos
        foreach ($data as $row) {
            // Determinar la altura necesaria para este conjunto de celdas
            $maxHeight = $alturaFila; // Altura mínima
            
            // Calcular la altura máxima necesaria para esta fila
            foreach ($row as $i => $cell) {
                $cellHeight = $this->pdf->getStringHeight($widths[$i], (string)$cell);
                $maxHeight = max($maxHeight, $cellHeight + $padding);
            }
            
            // Establecer color de fondo para filas alternas
            $this->pdf->SetFillColorArray($fill ? $colorFila2 : $colorFila1);
            
            // Dibujar las celdas con el mismo alto
            foreach ($row as $i => $cell) {
                // Determinar alineación según el contenido
                $align = is_numeric($cell) && !is_string($cell) ? 'C' : 'L';
                
                // Si es la columna de "Aprovechamiento" añadir color según el porcentaje
                if ($i == count($row) - 1 && strpos($cell, '%') !== false) {
                    $porcentaje = floatval($cell);
                    if ($porcentaje >= 90) {
                        $this->pdf->SetTextColor(39, 174, 96); // Verde para excelente
                    } elseif ($porcentaje >= 70) {
                        $this->pdf->SetTextColor(41, 128, 185); // Azul para bueno
                    } elseif ($porcentaje >= 50) {
                        $this->pdf->SetTextColor(243, 156, 18); // Naranja para regular
                    } else {
                        $this->pdf->SetTextColor(231, 76, 60); // Rojo para deficiente
                    }
                }
                
                // Dibujar celda con MultiCell para soportar texto largo
                $this->pdf->MultiCell($widths[$i], $maxHeight, (string)$cell, 1, $align, $fill, 0);
                
                // Restaurar color de texto normal después de cada celda especial
                if ($i == count($row) - 1 && strpos($cell, '%') !== false) {
                    $this->pdf->SetTextColorArray($colorTexto);
                }
            }
            
            $this->pdf->Ln();
            $fill = !$fill; // Alternar relleno
        }
        
        // Espacio después de la tabla
        $this->pdf->Ln(4);
    }

    /**
     * Genera la tabla de aprovechamiento como se muestra en reportes.php
     */
    private function generarTablaAprovechamiento($curso_id, $fecha) {
        // Datos del curso
        $stmt_curso = $this->pdo->prepare("
            SELECT c.nombre,
                   COUNT(DISTINCT e.id) AS total_encuestas,
                   COUNT(DISTINCT pr.id) AS total_preguntas,
                   SUM(r.valor_int) AS suma_puntos
            FROM cursos c
            JOIN encuestas e ON c.id = e.curso_id
            JOIN respuestas r ON e.id = r.encuesta_id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE c.id = :curso_id 
              AND DATE(e.fecha_envio) = :fecha
              AND pr.seccion = 'curso'
              AND pr.tipo = 'escala'
            GROUP BY c.id, c.nombre
        ");
        $stmt_curso->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $curso_data = $stmt_curso->fetch(PDO::FETCH_ASSOC);

        // Datos de profesores
        $stmt_prof = $this->pdo->prepare("
            SELECT p.nombre,
                   COUNT(DISTINCT e.id) AS total_encuestas,
                   COUNT(DISTINCT pr.id) AS total_preguntas,
                   SUM(r.valor_int) AS suma_puntos
            FROM profesores p
            JOIN respuestas r ON p.id = r.profesor_id
            JOIN encuestas e ON r.encuesta_id = e.id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE e.curso_id = :curso_id 
              AND DATE(e.fecha_envio) = :fecha
              AND pr.seccion = 'profesor'
              AND pr.tipo = 'escala'
            GROUP BY p.id, p.nombre
            ORDER BY p.nombre
        ");
        $stmt_prof->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $profesores_data = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

        // Preparar datos para la tabla
        $headers = ['Tipo', 'Curso/Profesor', 'Encuestas', 'Preguntas', 'Puntuación', 'Aprovechamiento'];
        $data = [];
        
        // Optimizar anchos según el contenido típico
        $widths = [15, 65, 20, 20, 30, 30];

        // Si hay datos del curso, agregarlos
        if ($curso_data && $curso_data['total_preguntas'] > 0) {
            $max_puntos_curso = $curso_data['total_encuestas'] * $curso_data['total_preguntas'] * 10; // 10 puntos máx por pregunta
            $puntuacion = $curso_data['suma_puntos'] . ' / ' . $max_puntos_curso;
            $aprovechamiento = $max_puntos_curso > 0 ? 
                               number_format(($curso_data['suma_puntos'] / $max_puntos_curso) * 100, 1) . '%' : '0%';
            
            $data[] = [
                'Curso', 
                $curso_data['nombre'], 
                $curso_data['total_encuestas'], 
                $curso_data['total_preguntas'], 
                $puntuacion, 
                $aprovechamiento
            ];
        }

        // Agregar datos de profesores
        foreach ($profesores_data as $prof) {
            if ($prof['total_preguntas'] > 0) {
                $max_puntos_prof = $prof['total_encuestas'] * $prof['total_preguntas'] * 10; // 10 puntos máx por pregunta
                $puntuacion = $prof['suma_puntos'] . ' / ' . $max_puntos_prof;
                $aprovechamiento = $max_puntos_prof > 0 ? 
                                  number_format(($prof['suma_puntos'] / $max_puntos_prof) * 100, 1) . '%' : '0%';
                
                $data[] = [
                    'Profesor', 
                    $prof['nombre'], 
                    $prof['total_encuestas'], 
                    $prof['total_preguntas'], 
                    $puntuacion, 
                    $aprovechamiento
                ];
            }
        }

        // Si no hay datos, no mostrar nada
        if (empty($data)) {
            return;
        }

        // No agregar una página nueva para la tabla, usar la página actual
        // Esto permite que los gráficos aparezcan inmediatamente después si hay espacio
        $this->generarTablaEstilizada($headers, $data, $widths, 'TABLA DE APROVECHAMIENTO');
    }
    
    /**
     * Generar tabla de aprovechamiento integrada con el resumen ejecutivo
     * Versión optimizada para mostrar en la misma página que el resumen
     */
    private function generarTablaAprovechamientoIntegrada($curso_id, $fecha) {
        try {
            // TÍTULO
            $this->pdf->SetFont('dejavusans', 'B', 14);
            $this->pdf->Cell(0, 10, 'TABLA DE APROVECHAMIENTO', 0, 1, 'L');
            $this->pdf->Ln(2);
            
            // Consulta para obtener datos del curso y profesores
            $stmt = $this->pdo->prepare("
                SELECT 
                    'CURSO' as tipo,
                    c.nombre as nombre,
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(DISTINCT r.id) as total_preguntas,
                    SUM(r.valor_int) as suma_puntos,
                    (SELECT COUNT(id) * 10 FROM preguntas WHERE tipo = 'escala') as max_puntos
                FROM cursos c
                LEFT JOIN encuestas e ON c.id = e.curso_id
                LEFT JOIN respuestas r ON e.id = r.encuesta_id
                WHERE c.id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY c.id, c.nombre
                
                UNION ALL
                
                SELECT 
                    'PROFESOR' as tipo,
                    CONCAT(p.nombre, ' ', p.apellidos) as nombre,
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(DISTINCT r.id) as total_preguntas,
                    SUM(r.valor_int) as suma_puntos,
                    COUNT(DISTINCT r.id) * 10 as max_puntos
                FROM profesores p
                JOIN respuestas r ON p.id = r.profesor_id
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY p.id, p.nombre, p.apellidos
                ORDER BY tipo DESC, nombre ASC
            ");
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar datos para la tabla
            $headers = ['Tipo', 'Curso/Profesor', 'Encuestas', 'Preguntas', 'Puntuación', 'Aprovechamiento'];
            $data = [];
            $widths = [20, 70, 25, 25, 25, 30]; // Anchos ajustados para la tabla
            
            foreach ($resultados as $r) {
                $max_puntos_prof = intval($r['max_puntos']);
                $puntuacion = $r['suma_puntos'] . ' / ' . $max_puntos_prof;
                $aprovechamiento = $max_puntos_prof > 0 ? 
                                  number_format(($r['suma_puntos'] / $max_puntos_prof) * 100, 1) . '%' : '0%';
                
                // Usar una etiqueta más visual para el tipo
                $tipo_mostrar = $r['tipo'] == 'CURSO' ? 
                    $this->pdf->Image(__DIR__ . '/../../assets/img/curso_icon.png', '', '', 4, 4, 'PNG') : 
                    $this->pdf->Image(__DIR__ . '/../../assets/img/profesor_icon.png', '', '', 4, 4, 'PNG');
                
                if (!file_exists(__DIR__ . '/../../assets/img/curso_icon.png')) {
                    $tipo_mostrar = $r['tipo'];
                }
                
                $data[] = [
                    $r['tipo'], 
                    $r['nombre'], 
                    $r['total_encuestas'], 
                    $r['total_preguntas'], 
                    $puntuacion, 
                    $aprovechamiento
                ];
            }

            // Si no hay datos, no mostrar nada
            if (empty($data)) {
                return;
            }

            // Generar la tabla con un estilo más moderno y compacto
            $this->generarTablaEstilizadaCompacta($headers, $data, $widths);
        } catch (Exception $e) {
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'Error al generar tabla de aprovechamiento: ' . $e->getMessage(), 0, 1);
        }
    }

    /**
     * Generar sección de Resumen Ejecutivo con tabla estilizada
     * Versión optimizada que combina resumen ejecutivo y tabla de aprovechamiento
     */
    private function generarResumenEjecutivoEstilizado($curso_id, $fecha) {
        // Consulta para obtener estadísticas generales del curso
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT e.id) as total_encuestas,
                COUNT(DISTINCT r.profesor_id) as total_profesores,
                AVG(r.valor_int) as promedio_general,
                STDDEV(r.valor_int) as desviacion_general
            FROM encuestas e
            JOIN respuestas r ON e.id = r.encuesta_id
            WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no hay datos, no mostrar nada
        if (!$stats || $stats['total_encuestas'] == 0) { 
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para el resumen ejecutivo.', 0, 1);
            return; 
        }
        
        // Título de la sección
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'RESUMEN EJECUTIVO', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Diseño de dos columnas para mejorar uso del espacio
        $pageWidth = $this->pdf->getPageWidth();
        $leftMargin = $this->pdf->getMargins()['left'];
        $rightMargin = $this->pdf->getMargins()['right'];
        $contentWidth = $pageWidth - $leftMargin - $rightMargin;
        
        // Ancho de la primera sección (resumen)
        $section1Width = $contentWidth * 0.48;
        $section2Width = $contentWidth *  0.48;
        $gapWidth = $contentWidth - $section1Width - $section2Width;
        
        // Guardar posición Y inicial
        $initialY = $this->pdf->GetY();
        
        // Primera columna: Resumen estadístico
        $headers = ['Métrica', 'Valor'];
        $data = [
            ['Total Encuestas', $stats['total_encuestas']],
            ['Total Profesores Evaluados', $stats['total_profesores']],
            ['Promedio General (1-10)', number_format($stats['promedio_general'], 2)],
            ['Desviación Estándar', number_format($stats['desviacion_general'], 2)]
        ];
        $widths = [100, 30];
        
        // Añadir estadísticas por categoría si hay espacio
        $stmt_cat = $this->pdo->prepare("
            SELECT 
                COALESCE(pr.seccion, 'General') as categoria,
                AVG(r.valor_int) as promedio
            FROM respuestas r 
            JOIN preguntas pr ON r.pregunta_id = pr.id
            JOIN encuestas e ON r.encuesta_id = e.id
            WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
            GROUP BY pr.seccion
            HAVING promedio > 0
            ORDER BY promedio DESC
            LIMIT 3
        ");
        $stmt_cat->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($categorias)) {
            $data[] = ['', '']; // Espacio
            $data[] = ['Categorías mejor evaluadas', ''];
            
            foreach ($categorias as $cat) {
                $data[] = [$cat['categoria'], number_format($cat['promedio'], 2)];
            }
        }
        
        // Generar tabla de resumen ejecutivo
        $this->generarTablaEstilizada($headers, $data, $widths, '');
        
        // Obtener la posición Y después de la tabla de resumen
        $endResumenY = $this->pdf->GetY();
        
        // Ahora generar la tabla de aprovechamiento directamente debajo del resumen
        // Incluimos la tabla en la misma página
        $this->generarTablaAprovechamientoIntegrada($curso_id, $fecha);
        
        // Añadir más espacio después de ambas tablas
        $this->pdf->Ln(5);
    }
    
    /**
     * Método principal para generar el reporte PDF completo por curso y fecha
     * Versión optimizada para mejor distribución del espacio
     */
    public function generarReportePorCursoFecha($curso_id, $fecha, $secciones = [], $imagenes_graficos = []) {
        try {
            // Verificar y establecer la conexión a la base de datos
            $this->verificarConexionBD();
            
            // Validar que el curso existe
            $stmt = $this->pdo->prepare("SELECT id, nombre FROM cursos WHERE id = :curso_id");
            $stmt->execute([':curso_id' => $curso_id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) {
                throw new Exception("Curso no encontrado con ID: $curso_id");
            }
            
            $this->pdf->AddPage();
            
            // HEADER PRINCIPAL - Más compacto para ahorrar espacio
            $this->generarHeaderPrincipal($curso, $fecha);
            
            // GENERAR SECCIONES EN EL ORDEN OPTIMIZADO
            // Priorizar las secciones más importantes para la primera página
            $seccionesPrioritarias = ['resumen_ejecutivo', 'resumen_completo'];
            $seccionesRestantes = array_diff($secciones, $seccionesPrioritarias);
            
            // Primero generar las secciones prioritarias
            foreach ($seccionesPrioritarias as $seccion) {
                if (in_array($seccion, $secciones)) {
                    try {
                        switch ($seccion) {
                            case 'resumen_ejecutivo': // Mantener compatibilidad con llamadas antiguas
                            case 'resumen_completo':  // Nueva sección combinada
                                $this->generarResumenEjecutivoEstilizado($curso_id, $fecha);
                                break;
                        }
                    } catch (Exception $e) {
                        $this->agregarMensajeError($seccion, $e->getMessage());
                    }
                }
            }
            
            // Luego generar el resto de las secciones
            foreach ($seccionesRestantes as $seccion) {
                try {
                    switch ($seccion) {
                        case 'graficos_evaluacion':
                            // Ya no generamos la tabla de aprovechamiento aquí porque está incluida en el resumen
                            $this->pdf->AddPage();
                            $this->pdf->SetFont('dejavusans', 'B', 14);
                            $this->pdf->Cell(0, 10, 'GRÁFICOS DE EVALUACIÓN', 0, 1, 'L');
                            $this->pdf->Ln(2);
                            
                            // Obtener los datos para los gráficos
                            $datos_graficos = $this->obtenerDatosGraficos($curso_id, $fecha);
                            if (empty($datos_graficos)) {
                                $this->pdf->SetFont('dejavusans', 'I', 10);
                                $this->pdf->Cell(0, 10, 'No hay datos suficientes para generar gráficos.', 0, 1);
                            } else {
                                // Generar gráficos con la nueva disposición
                                $this->generarGraficosMejorados($datos_graficos);
                            }
                            break;
                        case 'estadisticas_detalladas':
                            $this->generarSeccionEstadisticasTablaEstilizada($curso_id, $fecha);
                            break;
                        case 'preguntas_criticas':
                            $this->generarPreguntasCriticasEstilizadas($curso_id, $fecha);
                            break;
                        case 'comentarios_curso':
                            $this->generarComentariosCurso($curso_id, $fecha);
                            break;
                        case 'comentarios_profesores':
                            // Esta sección aún no está implementada pero manejamos el caso
                            $this->pdf->AddPage();
                            $this->pdf->SetFont('dejavusans', 'B', 14);
                            $this->pdf->Cell(0, 10, 'COMENTARIOS POR PROFESOR', 0, 1, 'L');
                            $this->pdf->Ln(2);
                            $this->pdf->SetFont('dejavusans', 'I', 10);
                            $this->pdf->Cell(0, 10, 'Esta sección está en desarrollo y estará disponible próximamente.', 0, 1);
                            break;
                        default:
                            // Sección no reconocida
                            $this->pdf->SetFont('dejavusans', 'I', 10);
                            $this->pdf->Cell(0, 10, "Sección '$seccion' no implementada.", 0, 1);
                            $this->pdf->Ln(2);
                            break;
                    }
                } catch (Exception $e) {
                    $this->agregarMensajeError($seccion, $e->getMessage());
                }
            }
            
            // Generar el archivo PDF como string binario
            return $this->pdf->Output('', 'S'); // 'S' para devolver como string
            
        } catch (Exception $e) {
            return $this->generarPdfError($e, $curso_id, $fecha);
        }
    }
      /**
     * Genera un reporte de evaluación completo en PDF
     * 
     * @param int $curso_id ID del curso
     * @param string $fecha Fecha en formato Y-m-d
     * @param string $outputPath Ruta donde guardar el PDF
     * @return bool Éxito de la generación
     */    public function generarReporteEvaluacion($curso_id, $fecha, $outputPath = '') {
        try {
            // Verificar y establecer la conexión a la base de datos
            $this->verificarConexionBD();
            
            // Definir las secciones a incluir en el reporte
            $secciones = ['resumen_completo', 'graficos_evaluacion', 'estadisticas_detalladas', 'preguntas_criticas', 'comentarios_curso'];
            
            // Generar el PDF usando el método generarReportePorCursoFecha
            $pdfContent = $this->generarReportePorCursoFecha($curso_id, $fecha, $secciones);
            
            // Si se especificó una ruta de salida, guardar el PDF
            if (!empty($outputPath)) {
                // Guardar el contenido binario directamente en el archivo
                if (file_put_contents($outputPath, $pdfContent) !== false) {
                    return true;
                } else {
                    throw new Exception("Error al guardar el archivo PDF en: $outputPath");
                }
            } else if ($pdfContent) {
                // Si no hay ruta, pero tenemos contenido, devolverlo
                return $pdfContent;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al generar el reporte: " . $e->getMessage());
            // Agregar información de debug más detallada
            error_log("Detalles: " . $e->getFile() . " línea " . $e->getLine());
            error_log("Traza: " . $e->getTraceAsString());
            return false;
        }
    }
      /**
     * Genera el header principal del reporte
     * @param array $curso Datos del curso
     * @param string $fecha Fecha de la evaluación
     */
    private function generarHeaderPrincipal($curso, $fecha) {
        // Header compacto para ahorrar espacio
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->SetFillColor(41, 128, 185); // Color azul
        $this->pdf->SetTextColor(255, 255, 255); // Texto blanco
        
        // Título del reporte
        $this->pdf->Cell(0, 10, 'REPORTE DE EVALUACIÓN ACADÉMICA', 0, 1, 'C', true);
        $this->pdf->Ln(2);
        
        // Información del curso
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(52, 152, 219); // Azul más claro
        $this->pdf->Cell(0, 8, 'Curso: ' . $curso['nombre'], 0, 1, 'C', true);
        $this->pdf->Ln(1);
        
        // Fecha formateada
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->SetFillColor(149, 165, 166); // Gris
        $this->pdf->Cell(0, 6, 'Fecha de Evaluación: ' . $fecha_formateada, 0, 1, 'C', true);
        
        // Resetear colores
        $this->pdf->SetTextColor(0, 0, 0); // Texto negro
        $this->pdf->SetFillColor(255, 255, 255); // Fondo blanco
        
        $this->pdf->Ln(5); // Espacio después del header
    }
    
    /**
     * Genera la portada del reporte
     */
    private function generarPortada($nombre_curso, $fecha) {
        $this->pdf->AddPage();
        
        // Título principal
        $this->pdf->SetFont('dejavusans', 'B', 18);
        $this->pdf->Cell(0, 20, 'REPORTE DE EVALUACIÓN', 0, 1, 'C');
        $this->pdf->Ln(5);
        
        // Nombre del curso
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 15, $nombre_curso, 0, 1, 'C');
        $this->pdf->Ln(5);
        
        // Fecha
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $this->pdf->SetFont('dejavusans', '', 12);
        $this->pdf->Cell(0, 10, 'Fecha: ' . $fecha_formateada, 0, 1, 'C');
        
        // Información adicional
        $this->pdf->Ln(20);
        $this->pdf->SetFont('dejavusans', 'I', 10);
        $this->pdf->Cell(0, 10, 'Este reporte contiene la evaluación completa del curso', 0, 1, 'C');
        $this->pdf->Cell(0, 10, 'incluyendo gráficos de aprovechamiento y estadísticas.', 0, 1, 'C');
        
        // Fecha y hora de generación
        $this->pdf->Ln(40);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    }
    
    /**
     * Genera un PDF con mensaje de error
     */
    private function generarPdfError($error, $curso_id, $fecha) {
        // Crear un PDF simple con mensaje de error
        $this->pdf = new TCPDF();
        $this->configurarPdf();
        
        $this->pdf->AddPage();
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'ERROR AL GENERAR REPORTE', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        $this->pdf->SetFont('dejavusans', '', 12);
        $this->pdf->MultiCell(0, 8, 'Se produjo un error al intentar generar el reporte para el curso ID: ' . $curso_id . ' con fecha: ' . $fecha, 0, 'L');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, 'Detalle del error:', 0, 1);
        
        $this->pdf->SetFont('dejavusans', 'I', 10);
        $this->pdf->SetTextColor(220, 53, 69); // Color rojo
        $this->pdf->MultiCell(0, 6, $error->getMessage(), 0, 'L');
        
        $this->pdf->SetTextColor(0, 0, 0); // Resetear color
        $this->pdf->Ln(10);
        
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->Cell(0, 8, 'Fecha y hora: ' . date('Y-m-d H:i:s'), 0, 1);
        
        // Retornar el PDF como string binario
        return $this->pdf->Output('', 'S');
    }
    
    /**
     * Genera la sección de comentarios del curso
     */
    private function generarComentariosCurso($curso_id, $fecha) {
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'COMENTARIOS DEL CURSO', 0, 1, 'L');
        
        $stmt = $this->pdo->prepare("
            SELECT r.texto, p.texto as pregunta
            FROM respuestas r 
            JOIN preguntas p ON r.pregunta_id = p.id
            JOIN encuestas e ON r.encuesta_id = e.id
            WHERE e.curso_id = :curso_id 
            AND DATE(e.fecha_envio) = :fecha
            AND p.tipo = 'abierta'
            AND r.texto IS NOT NULL AND LENGTH(r.texto) > 0
            ORDER BY p.orden, r.id DESC
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($comentarios)) {
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 8, "No hay comentarios disponibles para este curso y fecha.", 0, 1);
            return;
        }
        
        $pregunta_actual = '';
        
        foreach ($comentarios as $comentario) {
            // Si cambia la pregunta, mostrarla
            if ($pregunta_actual != $comentario['pregunta']) {
                $pregunta_actual = $comentario['pregunta'];
                $this->pdf->Ln(3);
                $this->pdf->SetFont('dejavusans', 'B', 10);
                $this->pdf->MultiCell(0, 7, 'Pregunta: ' . $pregunta_actual, 0, 'L');
            }
            
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->SetFillColor(248, 249, 250);
            $this->pdf->MultiCell(0, 6, $comentario['texto'], 1, 'L', true);
            $this->pdf->Ln(2);
        }
    }
    
    /**
     * Verifica la conexión a la base de datos y la establece si es necesario
     * 
     * @return bool Éxito al establecer la conexión
     * @throws Exception Si no se puede establecer la conexión
     */
    private function verificarConexionBD() {
        if (!$this->pdo) {
            try {
                $db = Database::getInstance();
                $this->pdo = $db->getConnection();
            } catch (Exception $e) {
                throw new Exception("No se pudo establecer conexión con la base de datos: " . $e->getMessage());
            }
            
            if (!$this->pdo) {
                throw new Exception("No se pudo establecer conexión con la base de datos");
            }
        }
        return true;
    }
    
    /**
     * Agrega un mensaje de error al PDF cuando una sección específica falla
     *
     * @param string $seccion Nombre de la sección que falló
     * @param string $mensaje Mensaje de error detallado
     */
    private function agregarMensajeError($seccion, $mensaje) {
        // Configurar estilo para mensajes de error
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetTextColor(220, 53, 69); // Color rojo para errores
        
        // Agregar mensaje de error con el nombre de la sección
        $this->pdf->Cell(0, 10, "Error en sección '$seccion':", 0, 1);
        
        // Detalles del error
        $this->pdf->SetFont('dejavusans', 'I', 10);
        $this->pdf->MultiCell(0, 8, $mensaje, 0, 'L');
        
        // Restaurar colores normales
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);
        
        // Registrar error en los logs para diagnóstico
        error_log("Error en sección PDF '$seccion': $mensaje");
    }
    
    /**
     * Genera gráficos de evaluación con mejor distribución visual
     * Versión optimizada para mostrar más gráficos por página
     */
    private function generarGraficosMejorados($datos_graficos) {
        // Configuración de la página
        $pageWidth = $this->pdf->getPageWidth();
        $pageHeight = $this->pdf->getPageHeight();
        $leftMargin = $this->pdf->getMargins()['left'];
        $rightMargin = $this->pdf->getMargins()['right'];
        $topMargin = 40; // Considerar espacio para el encabezado
        $bottomMargin = 25;
        
        // Espacio disponible
        $availableWidth = $pageWidth - $leftMargin - $rightMargin;
        $availableHeight = $pageHeight - $topMargin - $bottomMargin;
        
        // Configuración para los gráficos
        $chartRadius = min(30, $availableWidth / 6); // Tamaño proporcional y no muy grande
        $legendWidth = 75;
        $spaceBetween = 10;
        $chartSpaceHorizontal = $chartRadius * 3 + $spaceBetween; // Radio * 2 + espacio para la leyenda
        
        // Intentar mostrar dos gráficos por fila si hay espacio suficiente
        $chartsPerRow = ($availableWidth >= $chartSpaceHorizontal * 2) ? 2 : 1;
        
        $chartIndex = 0;
        $currentX = $leftMargin + $chartRadius;
        $currentY = $this->pdf->GetY() + $chartRadius;
        
        // Procesar cada gráfico
        foreach ($datos_graficos as $grafico) {
            // Si es un nuevo gráfico y no cabe en la página actual
            $estimatedHeight = $chartRadius * 2 + 10; // Altura estimada del gráfico
            $isNewPage = false;
            
            if ($currentY + $chartRadius > $pageHeight - $bottomMargin) {
                $this->pdf->AddPage();
                $currentY = $topMargin + $chartRadius;
                $currentX = $leftMargin + $chartRadius;
                $chartIndex = 0; // Reiniciar contador
                $isNewPage = true;
            }
            
            // Si estamos empezando una nueva fila
            if ($chartIndex % $chartsPerRow == 0 && !$isNewPage) {
                $currentX = $leftMargin + $chartRadius;
                if ($chartIndex > 0) {
                    $currentY += $chartRadius * 2 + $spaceBetween;
                }
            }
            
            // Determinar posición de la leyenda
            $legendX = $currentX + $chartRadius + 5;
            
            // Título del gráfico
            $this->pdf->SetXY($currentX - $chartRadius, $currentY - $chartRadius - 10);
            $this->pdf->SetFont('dejavusans', 'B', 10);
            
            // Determinar el título basado en el tipo de gráfico
            $titulo = '';
            if ($grafico['tipo'] == 'curso') {
                $titulo = 'Curso: ' . $grafico['nombre'];
            } elseif ($grafico['tipo'] == 'profesor') {
                $titulo = 'Prof: ' . $grafico['nombre'];
            }
            
            // Dibujar un título más compacto
            $this->pdf->Cell($chartRadius * 2 + $legendWidth, 8, $titulo, 0, 1, 'L');
            
            // Dibujar el gráfico con su leyenda
            $this->dibujarGraficoTortaOptimizado($currentX, $currentY, $chartRadius, "", $grafico['categorias'], $legendX);            
            // Avanzar a la siguiente posición horizontal si estamos mostrando varios gráficos por fila
            if ($chartsPerRow > 1 && $chartIndex % $chartsPerRow < $chartsPerRow - 1) {
                $currentX += $chartSpaceHorizontal;
            }
            
            $chartIndex++;
        }
    }
    
    /**
     * Genera el header principal del reporte
     * @param array $curso Datos del curso
     * @param string $fecha Fecha de la evaluación
     */
    private function generarHeaderPrincipal($curso, $fecha) {
        // Header compacto para ahorrar espacio
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->SetFillColor(41, 128, 185); // Color azul
        $this->pdf->SetTextColor(255, 255, 255); // Texto blanco
        
        // Título del reporte
        $this->pdf->Cell(0, 10, 'REPORTE DE EVALUACIÓN ACADÉMICA', 0, 1, 'C', true);
        $this->pdf->Ln(2);
        
        // Información del curso
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(52, 152, 219); // Azul más claro
        $this->pdf->Cell(0, 8, 'Curso: ' . $curso['nombre'], 0, 1, 'C', true);
        $this->pdf->Ln(1);
        
        // Fecha formateada
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->SetFillColor(149, 165, 166); // Gris
        $this->pdf->Cell(0, 6, 'Fecha de Evaluación: ' . $fecha_formateada, 0, 1, 'C', true);
        
        // Resetear colores
        $this->pdf->SetTextColor(0, 0, 0); // Texto negro
        $this->pdf->SetFillColor(255, 255, 255); // Fondo blanco
        
        $this->pdf->Ln(5); // Espacio después del header
    }
    
    /**
     * Obtener estadísticas detalladas por profesor
     */
    private function obtenerEstadisticasPorProfesor($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.nombre,
                    COUNT(r.id) as total_respuestas,
                    AVG(CASE WHEN r.valor_int BETWEEN 1 AND 5 THEN r.valor_int END) as promedio,
                    (COUNT(CASE WHEN r.valor_int IN (4, 5) THEN 1 END) * 100.0 / COUNT(CASE WHEN r.valor_int BETWEEN 1 AND 5 THEN 1 END)) as satisfaccion
                FROM profesores p
                LEFT JOIN respuestas r ON p.id = r.profesor_id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY p.id, p.nombre
                HAVING total_respuestas > 0
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
                $profesor['total_respuestas'] = intval($profesor['total_respuestas']);
            }
            
            return $resultados;
            
        } catch (Exception $e) {
            return [];
        }
    }
      /**
     * Obtener estadísticas por categoría de preguntas
     */    private function obtenerEstadisticasPorCategoria($curso_id, $fecha) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(pr.seccion, 'General') as categoria,
                    COUNT(r.id) as total,
                    AVG(CASE WHEN r.valor_int BETWEEN 1 AND 5 THEN r.valor_int END) as promedio
                FROM preguntas pr
                LEFT JOIN respuestas r ON pr.id = r.pregunta_id
                LEFT JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY pr.seccion
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
     * ==========================================
     * FUNCIONES PARA GRÁFICOS DE TORTA EN PDF
     * ==========================================
     */
    
    /**
     * Generar sección de gráficos de torta como en la web
     */    
    private function generarGraficosEvaluacion($curso_id, $fecha) {
        $datos_graficos = $this->obtenerDatosGraficos($curso_id, $fecha);

        if (empty($datos_graficos)) {
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay datos suficientes para generar gráficos.', 0, 1);
            $this->pdf->Ln(5);
            return;
        }

        // Primero generar la tabla de aprovechamiento
        $this->generarTablaAprovechamiento($curso_id, $fecha);
        
        // Luego generar los gráficos
        $this->pdf->AddPage();
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'GRÁFICOS DE EVALUACIÓN', 0, 1, 'L');
        $this->pdf->Ln(5);

        // Configuración mejorada para los gráficos
        $pageWidth = $this->pdf->getPageWidth();
        $pageHeight = $this->pdf->getPageHeight();
        $margenIzquierdo = 20;
        $margenDerecho = 20;
        $margenSuperior = 40; // Espacio para el título de la página
        $margenInferior = 25;
        
        // Ancho disponible para contenido
        $anchoDisponible = $pageWidth - $margenIzquierdo - $margenDerecho;
        
        // Configuración del gráfico y leyenda
        $chartR = 35; // Radio del gráfico
        $leyendaAncho = 70; // Ancho para la leyenda
        $espacioEntreGraficoYLeyenda = 5;
        $espacioEntreGraficos = 20; // Espacio vertical entre gráficos
        
        // Disposición horizontal mejorada
        $chartX = $margenIzquierdo + $chartR; // Centro X del gráfico
        $leyendaX = $chartX + $chartR + $espacioEntreGraficoYLeyenda; // Posición X de la leyenda
        
        $chartY = $margenSuperior + $chartR; // Posición Y inicial (centro del gráfico)
        
        // Procesamos cada gráfico
        foreach ($datos_graficos as $indice => $grafico) {
            // Si el siguiente gráfico no cabe en la página actual, crear una nueva
            if ($chartY + $chartR + $espacioEntreGraficos > $pageHeight - $margenInferior) {
                $this->pdf->AddPage();
                $this->pdf->SetFont('dejavusans', 'B', 14);
                $this->pdf->Cell(0, 10, 'GRÁFICOS DE EVALUACIÓN (Continuación)', 0, 1, 'L');
                $this->pdf->Ln(5);
                $chartY = $margenSuperior + $chartR;
            }
            
            // Calcular el aprovechamiento
            $aprovechamiento = $grafico['max_puntuacion'] > 0 ? 
                round(($grafico['puntuacion_real'] / $grafico['max_puntuacion']) * 100, 1) : 0;
            
            // Crear etiqueta según el tipo (curso o profesor)
            $tipo_etiqueta = $grafico['tipo'] == 'curso' ? 'CURSO' : 'PROFESOR';
            
            // Guardar la posición Y antes del título para mantener referencia
            $posYTitulo = $this->pdf->GetY();
            
            // Título del gráfico con fondo
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->SetFillColor(230, 235, 245); // Color de fondo más suave
            $this->pdf->Cell(0, 10, ($indice + 1) . '. ' . mb_strtoupper($tipo_etiqueta) . ': ' . $grafico['nombre'], 0, 1, 'L', true);
            
            // Información adicional a la izquierda del gráfico (no encima)
            // Subtítulos informativos con formato mejorado
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell($anchoDisponible, 8, 'Encuestas: ' . $grafico['total_encuestas'] . ' | Preguntas: ' . $grafico['num_preguntas'], 0, 1, 'L');
            
            // Puntuación y aprovechamiento con mejor formato
            $this->pdf->SetFont('dejavusans', 'B', 10);
            
            // Colorear el aprovechamiento según su valor
            if ($aprovechamiento >= 90) {
                $this->pdf->SetTextColor(46, 139, 87); // Verde oscuro para excelente
            } elseif ($aprovechamiento >= 70) {
                $this->pdf->SetTextColor(30, 144, 255); // Azul para bueno
            } elseif ($aprovechamiento >= 50) {
                $this->pdf->SetTextColor(255, 165, 0); // Naranja para aceptable
            } else {
                $this->pdf->SetTextColor(220, 20, 60); // Rojo para bajo
            }
            
            $puntuacion_texto = "Puntuación: " . $grafico['puntuacion_real'] . " de " . $grafico['max_puntuacion'];
            $aprovechamiento_texto = "Aprovechamiento: " . $aprovechamiento . "%";
            
            $this->pdf->Cell($anchoDisponible, 8, $puntuacion_texto . ' | ' . $aprovechamiento_texto, 0, 1, 'L');
            
            // Restaurar el color del texto
            $this->pdf->SetTextColor(0, 0, 0);
            
            // Calcular altura del título y datos (espacio usado)
            $alturaInfoPrevia = $this->pdf->GetY() - $posYTitulo;
            
            // Establecer posición Y del gráfico después de la información
            // Asegurar espacio suficiente para el gráfico
            $chartY = $this->pdf->GetY() + $chartR;
            
            // Dibujar el gráfico de torta con posición específica para la leyenda
            $this->dibujarGraficoTortaOptimizado($chartX, $chartY, $chartR, "", $grafico['categorias'], $leyendaX);
            
            // Actualizar posición Y para el próximo gráfico - después de verificar dónde terminó la leyenda
            $chartY = $this->pdf->GetY() + $espacioEntreGraficos;
            
            // Añadir línea divisoria entre gráficos excepto después del último
            if ($indice < count($datos_graficos) - 1) {
                $this->pdf->SetDrawColor(200, 200, 200);
                $this->pdf->Line($margenIzquierdo, $chartY - $espacioEntreGraficos/2, $pageWidth - $margenDerecho, $chartY - $espacioEntreGraficos/2);
            }
        }
    }
    
    /**
     * Dibuja un sector de un gráfico de torta.
     */
    private function dibujarSectorTorta($xc, $yc, $r, $a, $b, $color) {
        // Establecer color de relleno
        $this->pdf->SetFillColorArray($color);
        
        // Puntos para el polígono que forma el sector
        $puntos = array($xc, $yc); // El centro como primer punto
        
        // Aumentar el número de segmentos para sectores más suaves
        $n = 40; // Mayor número para curvas más suaves
        
        // Crear los puntos del sector
        for ($i = 0; $i <= $n; $i++) {
            $angulo = $a + ($b - $a) * $i / $n;
            $puntos[] = $xc + $r * cos(deg2rad($angulo));
            $puntos[] = $yc + $r * sin(deg2rad($angulo));
        }
        
        // Dibujar el sector con relleno
        $this->pdf->Polygon($puntos, 'F');
        
        // Dibujar líneas desde el centro hasta los bordes para definir mejor los sectores
        // especialmente importante en sectores pequeños
        $this->pdf->SetDrawColor(255, 255, 255);  // Líneas blancas para mejor contraste
        $this->pdf->SetLineWidth(0.2);
        
        // Línea al punto inicial del arco
        $x_inicio = $xc + $r * cos(deg2rad($a));
        $y_inicio = $yc + $r * sin(deg2rad($a));
        $this->pdf->Line($xc, $yc, $x_inicio, $y_inicio);
        
        // Línea al punto final del arco
        $x_fin = $xc + $r * cos(deg2rad($b));
        $y_fin = $yc + $r * sin(deg2rad($b));
        $this->pdf->Line($xc, $yc, $x_fin, $y_fin);
    }
    
    /**
     * Dibuja un gráfico de torta completo con su leyenda.
     */
    private function dibujarGraficoTorta($xc, $yc, $r, $titulo, $categorias) {
        // Aplicar título si existe
        if (!empty($titulo)) {
            $this->pdf->SetFont('dejavusans', 'B', 11);
            $this->pdf->MultiCell(0, 8, $titulo, 0, 'L');
            $this->pdf->Ln(2);
        }

        // Si no hay categorías con porcentajes, mostrar mensaje vacío
        $hay_datos = false;
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $hay_datos = true;
                break;
            }
        }
        
        if (!$hay_datos) {
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para este gráfico', 0, 1, 'C');
            return;
        }

        $angulo_inicio = 0;
        $offset_text = 5; // Espacio entre el borde del gráfico y la leyenda

        // Dibujar sectores con mejor definición
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $angulo_fin = $angulo_inicio + ($cat['porcentaje'] / 100) * 360;
                $this->dibujarSectorTorta($xc, $yc, $r, $angulo_inicio, $angulo_fin, $cat['color_rgb']);
                $angulo_inicio = $angulo_fin;
            }
        }
        
        // Dibujar línea negra alrededor del círculo completo para mejor definición
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Circle($xc, $yc, $r);

        // Dibujar leyenda en posición optimizada
        $leyendaX = $xc + $r + $offset_text;
        $leyendaY = $yc - $r; // Alinear con el tope del círculo
        $altoCaja = 5; // Alto de cada elemento de la leyenda
        $anchoCaja = 5; // Ancho del cuadrado de color
        $espacioEntreCajas = 2; // Espacio entre elementos
        $maxAnchoTexto = 55; // Ancho máximo para el texto de la leyenda

        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->SetXY($leyendaX, $leyendaY);

        foreach ($categorias as $cat) {
            if (isset($cat['nombre']) && isset($cat['porcentaje']) && isset($cat['color_rgb']) && $cat['porcentaje'] > 0) {
                $this->pdf->SetFillColorArray($cat['color_rgb']);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'F');
                
                // Dibujar borde negro alrededor del cuadrado de color para mejor definición
                $this->pdf->SetDrawColor(0, 0, 0);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'D');
                
                // Texto de la leyenda con porcentaje
                $textoLeyenda = ' ' . $cat['nombre'] . ' (' . $cat['porcentaje'] . '%)';
                
                // Usar MultiCell con ancho fijo para manejar textos largos
                $this->pdf->SetXY($leyendaX + $anchoCaja + 1, $this->pdf->GetY());
                $this->pdf->MultiCell($maxAnchoTexto, $altoCaja, $textoLeyenda, 0, 'L');
                
                // Mover a la siguiente posición
                $this->pdf->SetXY($leyendaX, $this->pdf->GetY() + $espacioEntreCajas);
            }
        }
    }
      /**
     * Dibuja un gráfico de torta con leyenda en posición optimizada a la derecha.
     * Versión mejorada para evitar superposiciones.
     */
    private function dibujarGraficoTortaOptimizado($xc, $yc, $r, $titulo, $categorias, $leyendaX) {
        // Aplicar título si existe
        if (!empty($titulo)) {
            $this->pdf->SetFont('dejavusans', 'B', 11);
            $this->pdf->MultiCell(0, 8, $titulo, 0, 'L');
            $this->pdf->Ln(2);
        }

        // Si no hay categorías con porcentajes, mostrar mensaje vacío
        $hay_datos = false;
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $hay_datos = true;
                break;
            }
        }
        
        if (!$hay_datos) {
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->Cell(0, 10, 'No hay datos disponibles para este gráfico', 0, 1, 'C');
            return;
        }

        $angulo_inicio = 0;

        // Dibujar sectores con mejor definición
        foreach ($categorias as $cat) {
            if (isset($cat['porcentaje']) && $cat['porcentaje'] > 0) {
                $angulo_fin = $angulo_inicio + ($cat['porcentaje'] / 100) * 360;
                $this->dibujarSectorTorta($xc, $yc, $r, $angulo_inicio, $angulo_fin, $cat['color_rgb']);
                $angulo_inicio = $angulo_fin;
            }
        }
        
        // Dibujar línea negra alrededor del círculo completo para mejor definición
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Circle($xc, $yc, $r);

        // Configuración para la leyenda
        $leyendaY = $yc - $r; // Alinear con el tope del círculo
        $altoCaja = 5; // Alto de cada elemento de la leyenda
        $anchoCaja = 5; // Ancho del cuadrado de color
        $espacioEntreCajas = 2; // Espacio entre elementos
        $maxAnchoTexto = 55; // Ancho máximo para el texto de la leyenda

        // Dibujar leyenda en posición explícita a la derecha
        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->SetXY($leyendaX, $leyendaY);
        
        // Calcular altura máxima entre el gráfico y la leyenda
        $alturaInicialLeyenda = $this->pdf->GetY();
        $alturaMaxima = $yc + $r; // Altura máxima del gráfico (centro + radio)
        
        foreach ($categorias as $cat) {
            if (isset($cat['nombre']) && isset($cat['porcentaje']) && isset($cat['color_rgb']) && $cat['porcentaje'] > 0) {
                $this->pdf->SetFillColorArray($cat['color_rgb']);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'F');
                
                // Dibujar borde negro alrededor del cuadrado de color
                $this->pdf->SetDrawColor(0, 0, 0);
                $this->pdf->Rect($leyendaX, $this->pdf->GetY(), $anchoCaja, $altoCaja, 'D');
                
                // Texto de la leyenda con porcentaje
                $textoLeyenda = ' ' . $cat['nombre'] . ' (' . $cat['porcentaje'] . '%)';
                
                // Usar MultiCell con ancho fijo para manejar textos largos
                $this->pdf->SetXY($leyendaX + $anchoCaja + 1, $this->pdf->GetY());
                $this->pdf->MultiCell($maxAnchoTexto, $altoCaja, $textoLeyenda, 0, 'L');
                
                // Mover a la siguiente posición
                $this->pdf->SetXY($leyendaX, $this->pdf->GetY() + $espacioEntreCajas);
            }
        }
        
        // Determinar cuál es mayor: la altura del gráfico o la altura de la leyenda
        $alturaFinalLeyenda = $this->pdf->GetY();
        $alturaLeyenda = $alturaFinalLeyenda - $alturaInicialLeyenda;
        
        // Establecer la posición Y del cursor después del elemento más alto
        $nuevaY = max($alturaMaxima, $alturaFinalLeyenda);
        $this->pdf->SetY($nuevaY);
        
        // Añadir un pequeño espacio después del gráfico
        $this->pdf->Ln(5);
    }
    
    /**
     * Agrupa las respuestas en categorías según su valor
     */
    private function agruparEnCategorias($distribucion_raw) {
        // Definir las categorías y sus valores correspondientes
        // Usamos el valor específico para cada categoría según los requisitos:
        // Excelente=10, Bueno=7, Correcto=5, Regular=3, Deficiente=1
        $categorias = [
            'Excelente'  => ['valores' => [10, 9], 'color' => [46, 139, 87], 'valor_asignado' => 10],   // Verde
            'Bueno'      => ['valores' => [8, 7], 'color' => [30, 144, 255], 'valor_asignado' => 7],    // Azul
            'Correcto'   => ['valores' => [6, 5], 'color' => [255, 215, 0], 'valor_asignado' => 5],     // Amarillo
            'Regular'    => ['valores' => [4, 3], 'color' => [255, 140, 0], 'valor_asignado' => 3],     // Naranja
            'Deficiente' => ['valores' => [2, 1], 'color' => [220, 20, 60], 'valor_asignado' => 1]      // Rojo
        ];

        // Inicializar contadores y total
        $total_respuestas = 0;
        $total_puntos = 0;
        $conteo_categorias = [];
        
        foreach ($categorias as $nombre => $info) {
            $conteo_categorias[$nombre] = 0;
        }

        // Procesar cada resultado y agruparlo en su categoría
        foreach ($distribucion_raw as $item) {
            $valor_int = (int)$item['valor_int'];
            $cantidad = (int)$item['cantidad'];
            $total_respuestas += $cantidad;
            
            foreach ($categorias as $nombre => $info) {
                if (in_array($valor_int, $info['valores'])) {
                    $conteo_categorias[$nombre] += $cantidad;
                    $total_puntos += $cantidad * $info['valor_asignado'];
                    break;
                }
            }
        }

        // Crear array final con los datos procesados para el gráfico
        $datos_categorias = [];
        foreach ($categorias as $nombre => $info) {
            $conteo = $conteo_categorias[$nombre];
            $porcentaje = $total_respuestas > 0 ? round(($conteo / $total_respuestas) * 100, 1) : 0;
            
            // Solo añadir categorías que tengan valores
            if ($conteo > 0 || $porcentaje > 0) {
                $datos_categorias[] = [
                    'nombre' => $nombre,
                    'cantidad' => $conteo,
                    'porcentaje' => $porcentaje,
                    'color_rgb' => $info['color'],
                    'valor_asignado' => $info['valor_asignado']
                ];
            }
        }

        return $datos_categorias;
    }    /**
     * Obtiene los datos para todos los gráficos (curso y profesores).
     */
    private function obtenerDatosGraficos($curso_id, $fecha) {
        $graficos = [];

        // 1. Gráfico del curso
        try {
            // Obtener información del curso
            $stmt_info = $this->pdo->prepare("SELECT c.id, c.nombre FROM cursos c WHERE c.id = :curso_id");
            $stmt_info->execute([':curso_id' => $curso_id]);
            $curso = $stmt_info->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) return [];

            // Contar total de encuestas para el curso
            $stmt_encuestas = $this->pdo->prepare("
                SELECT COUNT(DISTINCT e.id) as total_encuestas
                FROM encuestas e
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id
                AND DATE(e.fecha_envio) = :fecha
                AND pr.seccion = 'curso' AND pr.tipo = 'escala'
            ");
            $stmt_encuestas->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $total_encuestas = $stmt_encuestas->fetch(PDO::FETCH_ASSOC)['total_encuestas'] ?? 0;
            
            // Contar número de preguntas de curso tipo escala
            $stmt_preguntas = $this->pdo->prepare("
                SELECT COUNT(*) as num_preguntas
                FROM preguntas
                WHERE seccion = 'curso' AND tipo = 'escala' AND activa = 1
            ");
            $stmt_preguntas->execute();
            $num_preguntas = $stmt_preguntas->fetch(PDO::FETCH_ASSOC)['num_preguntas'] ?? 0;
            
            // Obtener distribución para generar el gráfico
            $stmt_curso = $this->pdo->prepare("
                SELECT r.valor_int, COUNT(*) as cantidad
                FROM encuestas e
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                  AND pr.seccion = 'curso' AND pr.tipo = 'escala'
                GROUP BY r.valor_int
                ORDER BY r.valor_int DESC
            ");
            $stmt_curso->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $distribucion_curso = $stmt_curso->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($distribucion_curso)) {
                // Calcular puntuación total basada en los valores reales asignados (10, 7, 5, 3, 1)
                $categorias = $this->agruparEnCategorias($distribucion_curso);
                $puntuacion_real = 0;
                $total_respuestas = 0;
                
                foreach ($categorias as $categoria) {
                    $puntuacion_real += $categoria['cantidad'] * $categoria['valor_asignado'];
                    $total_respuestas += $categoria['cantidad'];
                }
                
                // Para cursos: el valor máximo es 100
                // Calculamos el máximo teórico: total_encuestas * num_preguntas * 10 (valor máximo por respuesta)
                $max_puntuacion = $total_encuestas * $num_preguntas * 10;

                // Si hay un problema con los datos, usar un valor predeterminado seguro
                if ($max_puntuacion <= 0 || $num_preguntas <= 0) {
                    $max_puntuacion = $total_encuestas * 100; // 100 puntos por encuesta (estándar para cursos)
                }

                $graficos[] = [
                    'tipo' => 'curso',
                    'titulo' => 'Evaluación General del Curso: ' . $curso['nombre'],
                    'nombre' => $curso['nombre'],
                    'total_encuestas' => $total_encuestas,
                    'num_preguntas' => $num_preguntas,
                    'puntuacion_real' => $puntuacion_real,
                    'max_puntuacion' => $max_puntuacion,
                    'categorias' => $categorias
                ];
            }
        } catch (Exception $e) {
            // Registrar error
            error_log("Error al obtener datos del curso: " . $e->getMessage());
        }

        // 2. Gráficos de profesores
        try {
            $stmt_profesores = $this->pdo->prepare("
                SELECT DISTINCT p.id, p.nombre 
                FROM profesores p 
                JOIN respuestas r ON p.id = r.profesor_id 
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
            ");
            $stmt_profesores->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $profesores = $stmt_profesores->fetchAll(PDO::FETCH_ASSOC);

            foreach ($profesores as $profesor) {
                // Contar encuestas para este profesor
                $stmt_encuestas = $this->pdo->prepare("
                    SELECT COUNT(DISTINCT e.id) as total_encuestas
                    FROM encuestas e
                    JOIN respuestas r ON e.id = r.encuesta_id
                    WHERE e.curso_id = :curso_id
                    AND r.profesor_id = :profesor_id
                    AND DATE(e.fecha_envio) = :fecha
                ");
                $stmt_encuestas->execute([
                    ':curso_id' => $curso_id, 
                    ':profesor_id' => $profesor['id'],
                    ':fecha' => $fecha
                ]);
                $total_encuestas = $stmt_encuestas->fetch(PDO::FETCH_ASSOC)['total_encuestas'] ?? 0;
                
                // Contar número de preguntas de profesor tipo escala
                $stmt_preguntas = $this->pdo->prepare("
                    SELECT COUNT(*) as num_preguntas
                    FROM preguntas
                    WHERE seccion = 'profesor' AND tipo = 'escala' AND activa = 1
                ");
                $stmt_preguntas->execute();
                $num_preguntas = $stmt_preguntas->fetch(PDO::FETCH_ASSOC)['num_preguntas'] ?? 0;
                
                // Obtener distribución para el gráfico
                $stmt_prof = $this->pdo->prepare("
                    SELECT r.valor_int, COUNT(*) as cantidad
                    FROM respuestas r
                    JOIN encuestas e ON r.encuesta_id = e.id
                    JOIN preguntas pr ON r.pregunta_id = pr.id
                    WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                      AND r.profesor_id = :profesor_id AND pr.seccion = 'profesor' AND pr.tipo = 'escala'
                    GROUP BY r.valor_int
                    ORDER BY r.valor_int DESC
                ");
                $stmt_prof->execute([
                    ':curso_id' => $curso_id, 
                    ':fecha' => $fecha, 
                    ':profesor_id' => $profesor['id']
                ]);
                $distribucion_prof = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($distribucion_prof)) {
                    // Calcular puntuación real usando los valores asignados
                    $categorias = $this->agruparEnCategorias($distribucion_prof);
                    $puntuacion_real = 0;
                    $total_respuestas = 0;
                    
                    foreach ($categorias as $categoria) {
                        $puntuacion_real += $categoria['cantidad'] * $categoria['valor_asignado'];
                        $total_respuestas += $categoria['cantidad'];
                    }
                    
                    // Para profesores: el valor máximo es 70
                    // Calculamos el máximo teórico: total_encuestas * num_preguntas * 10 (valor máximo por respuesta)
                    $max_puntuacion = $total_encuestas * $num_preguntas * 10;
                    
                    // Si hay un problema con los datos, usar un valor predeterminado seguro
                    if ($max_puntuacion <= 0 || $num_preguntas <= 0) {
                        $max_puntuacion = $total_encuestas * 70; // 70 puntos por encuesta (estándar para profesores)
                    }

                    $graficos[] = [
                        'tipo' => 'profesor',
                        'titulo' => 'Evaluación de: ' . $profesor['nombre'],
                        'nombre' => $profesor['nombre'],
                        'total_encuestas' => $total_encuestas,
                        'num_preguntas' => $num_preguntas,
                        'puntuacion_real' => $puntuacion_real,
                        'max_puntuacion' => $max_puntuacion,
                        'categorias' => $categorias
                    ];
                }
            }
        } catch (Exception $e) {
            // Registrar error
            error_log("Error al obtener datos de profesores: " . $e->getMessage());
        }
        
        return $graficos;
    }

    /**
     * Genera una tabla con estilos mejorados.
     */
    private function generarTablaEstilizada($headers, $data, $widths, $title = '') {
        // Verificar si tenemos suficiente espacio en la página actual
        $alturaEstimada = 20; // Altura para el título
        $alturaEstimada += 7; // Altura para la cabecera
        $alturaEstimada += count($data) * 8; // Altura estimada para las filas (8 puntos por fila)
        $alturaEstimada += 15; // Margen adicional
        
        $espacioRestante = $this->pdf->getPageHeight() - $this->pdf->GetY();
        
        // Si no hay suficiente espacio, agregar una nueva página
        if ($espacioRestante < $alturaEstimada) {
            $this->pdf->AddPage();
        }
        
        // Título de la tabla con estilo mejorado
        if (!empty($title)) {
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->SetFillColor(240, 240, 240);
            $this->pdf->Cell(0, 10, $title, 0, 1, 'L', true);
            $this->pdf->Ln(2);
        }

        // Cabecera con mejor contraste y legibilidad
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColor(220, 230, 242); // Color más suave y elegante
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(180, 180, 180);
        $this->pdf->SetLineWidth(0.2);

        $sumAnchos = array_sum($widths);
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $this->pdf->Ln();

        // Datos con filas alternas para mejor lectura
        $this->pdf->SetFont('dejavusans', '', 8);
        $fill = false;

        foreach ($data as $row) {
            // Determinar la altura necesaria para cada celda
            $maxHeight = 6; // Altura mínima
            foreach ($row as $i => $cell) {
                $height = $this->pdf->getStringHeight($widths[$i], (string)$cell, false, true, '', 1);
                if ($height > $maxHeight) {
                    $maxHeight = $height;
                }
            }

            // Alternar colores para mejor legibilidad
            $this->pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            
            // Dibujar las celdas con la misma altura
            foreach ($row as $i => $cell) {
                // Alineación según el tipo de contenido (centrar números, alinear a la izquierda el texto)
                $align = is_numeric($cell) && !is_string($cell) ? 'C' : 'L';
                $this->pdf->MultiCell($widths[$i], $maxHeight, (string)$cell, 1, $align, $fill, 0, '', '', true, 0, false, true, $maxHeight, 'M');
            }
            $this->pdf->Ln();
            $fill = !$fill;
        }
        
        // Espacio después de la tabla más compacto
        $this->pdf->Ln(6);
    }

    /**
     * Genera una tabla con estilos modernos y compactos similar al diseño web
     */
    private function generarTablaEstilizadaCompacta($headers, $data, $widths, $title = '') {
        // Colores modernos para la tabla
        $colorCabecera = [41, 128, 185]; // Azul moderno
        $colorBordes = [189, 195, 199]; // Gris claro
        $colorFila1 = [245, 245, 245]; // Casi blanco
        $colorFila2 = [240, 240, 240]; // Gris muy claro
        $colorTexto = [44, 62, 80]; // Azul oscuro
        
        // Espaciado compacto
        $alturaCabecera = 8;
        $alturaFila = 7;
        $padding = 2;
        
        // Título de la tabla (opcional)
        if (!empty($title)) {
            $this->pdf->SetFont('dejavusans', 'B', 13);
            $this->pdf->SetFillColor(236, 240, 241); // Gris muy suave
            $this->pdf->Cell(0, 10, $title, 0, 1, 'L', true);
            $this->pdf->Ln(1); // Muy poco espacio después del título
        }

        // Configuración para la cabecera
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColorArray($colorCabecera);
        $this->pdf->SetTextColor(255, 255, 255); // Texto blanco para contraste
        $this->pdf->SetDrawColorArray($colorBordes);
        $this->pdf->SetLineWidth(0.2);

        // Dibujar la cabecera
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], $alturaCabecera, $header, 1, 0, 'C', 1);
        }
        $this->pdf->Ln();

        // Restaurar color de texto para los datos
        $this->pdf->SetTextColorArray($colorTexto);
        $this->pdf->SetFont('dejavusans', '', 8);
        
        // Alternar colores para filas de datos
        $fill = false;

        // Dibujar filas de datos
        foreach ($data as $row) {
            // Determinar la altura necesaria para este conjunto de celdas
            $maxHeight = $alturaFila; // Altura mínima
            
            // Calcular la altura máxima necesaria para esta fila
            foreach ($row as $i => $cell) {
                $cellHeight = $this->pdf->getStringHeight($widths[$i], (string)$cell);
                $maxHeight = max($maxHeight, $cellHeight + $padding);
            }
            
            // Establecer color de fondo para filas alternas
            $this->pdf->SetFillColorArray($fill ? $colorFila2 : $colorFila1);
            
            // Dibujar las celdas con el mismo alto
            foreach ($row as $i => $cell) {
                // Determinar alineación según el contenido
                $align = is_numeric($cell) && !is_string($cell) ? 'C' : 'L';
                
                // Si es la columna de "Aprovechamiento" añadir color según el porcentaje
                if ($i == count($row) - 1 && strpos($cell, '%') !== false) {
                    $porcentaje = floatval($cell);
                    if ($porcentaje >= 90) {
                        $this->pdf->SetTextColor(39, 174, 96); // Verde para excelente
                    } elseif ($porcentaje >= 70) {
                        $this->pdf->SetTextColor(41, 128, 185); // Azul para bueno
                    } elseif ($porcentaje >= 50) {
                        $this->pdf->SetTextColor(243, 156, 18); // Naranja para regular
                    } else {
                        $this->pdf->SetTextColor(231, 76, 60); // Rojo para deficiente
                    }
                }
                
                // Dibujar celda con MultiCell para soportar texto largo
                $this->pdf->MultiCell($widths[$i], $maxHeight, (string)$cell, 1, $align, $fill, 0);
                
                // Restaurar color de texto normal después de cada celda especial
                if ($i == count($row) - 1 && strpos($cell, '%') !== false) {
                    $this->pdf->SetTextColorArray($colorTexto);
                }
            }
            
            $this->pdf->Ln();
            $fill = !$fill; // Alternar relleno
        }
        
        // Espacio después de la tabla
        $this->pdf->Ln(4);
    }

    /**
     * Genera la tabla de aprovechamiento como se muestra en reportes.php
     */
    private function generarTablaAprovechamiento($curso_id, $fecha) {
        // Datos del curso
        $stmt_curso = $this->pdo->prepare("
            SELECT c.nombre,
                   COUNT(DISTINCT e.id) AS total_encuestas,
                   COUNT(DISTINCT pr.id) AS total_preguntas,
                   SUM(r.valor_int) AS suma_puntos
            FROM cursos c
            JOIN encuestas e ON c.id = e.curso_id
            JOIN respuestas r ON e.id = r.encuesta_id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE c.id = :curso_id 
              AND DATE(e.fecha_envio) = :fecha
              AND pr.seccion = 'curso'
              AND pr.tipo = 'escala'
            GROUP BY c.id, c.nombre
        ");
        $stmt_curso->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $curso_data = $stmt_curso->fetch(PDO::FETCH_ASSOC);

        // Datos de profesores
        $stmt_prof = $this->pdo->prepare("
            SELECT p.nombre,
                   COUNT(DISTINCT e.id) AS total_encuestas,
                   COUNT(DISTINCT pr.id) AS total_preguntas,
                   SUM(r.valor_int) AS suma_puntos
            FROM profesores p
            JOIN respuestas r ON p.id = r.profesor_id
            JOIN encuestas e ON r.encuesta_id = e.id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE e.curso_id = :curso_id 
              AND DATE(e.fecha_envio) = :fecha
              AND pr.seccion = 'profesor'
              AND pr.tipo = 'escala'
            GROUP BY p.id, p.nombre
            ORDER BY p.nombre
        ");
        $stmt_prof->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $profesores_data = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

        // Preparar datos para la tabla
        $headers = ['Tipo', 'Curso/Profesor', 'Encuestas', 'Preguntas', 'Puntuación', 'Aprovechamiento'];
        $data = [];
        
        // Optimizar anchos según el contenido típico
        $widths = [15, 65, 20, 20, 30, 30];

        // Si hay datos del curso, agregarlos
        if ($curso_data && $curso_data['total_preguntas'] > 0) {
            $max_puntos_curso = $curso_data['total_encuestas'] * $curso_data['total_preguntas'] * 10; // 10 puntos máx por pregunta
            $puntuacion = $curso_data['suma_puntos'] . ' / ' . $max_puntos_curso;
            $aprovechamiento = $max_puntos_curso > 0 ? 
                               number_format(($curso_data['suma_puntos'] / $max_puntos_curso) * 100, 1) . '%' : '0%';
            
            $data[] = [
                'Curso', 
                $curso_data['nombre'], 
                $curso_data['total_encuestas'], 
                $curso_data['total_preguntas'], 
                $puntuacion, 
                $aprovechamiento
            ];
        }

        // Agregar datos de profesores
        foreach ($profesores_data as $prof) {
            if ($prof['total_preguntas'] > 0) {
                $max_puntos_prof = $prof['total_encuestas'] * $prof['total_preguntas'] * 10; // 10 puntos máx por pregunta
                $puntuacion = $prof['suma_puntos'] . ' / ' . $max_puntos_prof;
                $aprovechamiento = $max_puntos_prof > 0 ? 
                                  number_format(($prof['suma_puntos'] / $max_puntos_prof) * 100, 1) . '%' : '0%';
                
                $data[] = [
                    'Profesor', 
                    $prof['nombre'], 
                    $prof['total_encuestas'], 
                    $prof['total_preguntas'], 
                    $puntuacion, 
                    $aprovechamiento
                ];
            }
        }

        // Si no hay datos, no mostrar nada
        if (empty($data)) {
            return;
        }

        // No agregar una página nueva para la tabla, usar la página actual
        // Esto permite que los gráficos aparezcan inmediatamente después si hay espacio
        $this->generarTablaEstilizada($headers, $data, $widths, 'TABLA DE APROVECHAMIENTO');
    }
    
    /**
     * Generar tabla de aprovechamiento integrada con el resumen ejecutivo
     * Versión optimizada para mostrar en la misma página que el resumen
     */
    private function generarTablaAprovechamientoIntegrada($curso_id, $fecha) {
        try {
            // TÍTULO
            $this->pdf->SetFont('dejavusans', 'B', 14);
            $this->pdf->Cell(0, 10, 'TABLA DE APROVECHAMIENTO', 0, 1, 'L');
            $this->pdf->Ln(2);
            
            // Consulta para obtener datos del curso y profesores
            $stmt = $this->pdo->prepare("
                SELECT 
                    'CURSO' as tipo,
                    c.nombre as nombre,
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(DISTINCT r.id) as total_preguntas,
                    SUM(r.valor_int) as suma_puntos,
                    (SELECT COUNT(id) * 10 FROM preguntas WHERE tipo = 'escala') as max_puntos
                FROM cursos c
                LEFT JOIN encuestas e ON c.id = e.curso_id
                LEFT JOIN respuestas r ON e.id = r.encuesta_id
                WHERE c.id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY c.id, c.nombre
                
                UNION ALL
                
                SELECT 
                    'PROFESOR' as tipo,
                    CONCAT(p.nombre, ' ', p.apellidos) as nombre,
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(DISTINCT r.id) as total_preguntas,
                    SUM(r.valor_int) as suma_puntos,
                    COUNT(DISTINCT r.id) * 10 as max_puntos
                FROM profesores p
                JOIN respuestas r ON p.id = r.profesor_id
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                GROUP BY p.id, p.nombre, p.apellidos
                ORDER BY tipo DESC, nombre ASC
            ");
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar datos para la tabla
            $headers = ['Tipo', 'Curso/Profesor', 'Encuestas', 'Preguntas', 'Puntuación', 'Aprovechamiento'];
            $data = [];
            $widths = [20, 70, 25, 25, 25, 30]; // Anchos ajustados para la tabla
            
            foreach ($resultados as $r) {
                $max_puntos_prof = intval($r['max_puntos']);
                $puntuacion = $r['suma_puntos'] . ' / ' . $max_puntos_prof;
                $aprovechamiento = $max_puntos_prof > 0 ? 
                                  number_format(($r['suma_puntos'] / $max_puntos_prof) * 100, 1) . '%' : '0%';
                
                // Usar una etiqueta más visual para el tipo
                $tipo_mostrar = $r['tipo'] == 'CURSO' ? 
                    $this->pdf->Image(__DIR__ . '/../../assets/img/curso_icon.png', '', '', 4, 4, 'PNG') : 
                    $this->pdf->Image(__DIR__ . '/../../assets/img/profesor_icon.png', '', '', 4, 4, 'PNG');
                
                if (!file_exists(__DIR__ . '/../../assets/img/curso_icon.png')) {
                    $tipo_mostrar = $r['tipo'];
                }
                
                $data[] = [
                    $r['tipo'], 
                    $r['nombre'], 
                    $r['total_encuestas'], "                    $r['total_preguntas']," 
"                    $puntuacion," 
"                    $aprovechamiento" 
"                ];" 
"            }" 
"" 
"            // Si no hay datos, no mostrar nada" 
"            if (empty($data)) {" 
"                return;" 
"            }" 
"" 
"            // Generar tabla con los datos" 
"            $this->generarTablaEstilizada($headers, $data, $widths);" 
"        } catch (Exception $e) {" 
"            $this->pdf->SetFont('dejavusans', 'I', 10);" 
"            $this->pdf->Cell(0, 10, 'Error al generar tabla de aprovechamiento: ' . $e->getMessage(), 0, 1, 'L');" 
"        }" 
"    }" 
                    $r['total_preguntas'], 
                    $puntuacion, 
                    $aprovechamiento 
                ]; 
            } 
