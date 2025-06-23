<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

class ReportePdfGenerator {
    private $pdo;
    private $pdf;    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        }
        
        $this->pdf = new TCPDF();
        $this->configurarPdf();
    }private function configurarPdf() {
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
     * Generar sección de Resumen Ejecutivo con tabla estilizada
     */
    private function generarResumenEjecutivoEstilizado($curso_id, $fecha) {
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

        if (!$stats || $stats['total_encuestas'] == 0) { return; }

        $headers = ['Métrica', 'Valor'];
        $data = [
            ['Total Encuestas', $stats['total_encuestas']],
            ['Total Profesores Evaluados', $stats['total_profesores']],
            ['Promedio General (1-10)', number_format($stats['promedio_general'], 2)],
            ['Desviación Estándar', number_format($stats['desviacion_general'], 2)],
        ];
        $widths = [120, 60];
        
        $this->generarTablaEstilizada($headers, $data, $widths, 'RESUMEN EJECUTIVO');
    }

    /**
     * Generar sección de Estadísticas Detalladas con tabla estilizada
     */
    private function generarSeccionEstadisticasTablaEstilizada($curso_id, $fecha) {
        $profesores = $this->obtenerEstadisticasPorProfesor($curso_id, $fecha);
        if (empty($profesores)) { return; }

        $headers = ['Profesor', 'Respuestas', 'Promedio', '% Satisfacción'];
        $data = [];
        foreach ($profesores as $p) {
            $data[] = [$p['nombre'], $p['total_respuestas'], number_format($p['promedio'], 2), $p['satisfaccion'] . '%'];
        }
        $widths = [80, 30, 30, 40];

        $this->generarTablaEstilizada($headers, $data, $widths, 'ESTADÍSTICAS DETALLADAS POR PROFESOR');
    }

    /**
     * Generar sección de Preguntas Críticas con tabla estilizada
     */
    private function generarPreguntasCriticasEstilizadas($curso_id, $fecha) {
        $stmt = $this->pdo->prepare("
            SELECT pr.texto, AVG(r.valor_int) as promedio, COUNT(r.id) as total_respuestas
            FROM respuestas r
            JOIN preguntas pr ON r.pregunta_id = pr.id
            JOIN encuestas e ON r.encuesta_id = e.id
            WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND pr.tipo = 'escala'
            GROUP BY pr.id, pr.texto
            HAVING promedio < 6.0
            ORDER BY promedio ASC
            LIMIT 10
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($preguntas)) { return; }

        $headers = ['Pregunta con Bajo Rendimiento', 'Promedio', 'Respuestas'];
        $data = [];
        foreach ($preguntas as $p) {
            $data[] = [$p['texto'], number_format($p['promedio'], 2), $p['total_respuestas']];
        }
        $widths = [120, 30, 30];

        $this->generarTablaEstilizada($headers, $data, $widths, 'PREGUNTAS CON RENDIMIENTO MÁS BAJO');
    }
    
    /*
     * =========================================================================
     * MÉTODOS ANTIGUOS ELIMINADOS
     * =========================================================================
     * Las funciones generarResumenEjecutivo, generarDistribucionRespuestas,
     * generarEstadisticasDetalladas y generarPreguntasCriticas han sido
     * eliminadas y reemplazadas por sus versiones "Estilizadas" o de "Gráficos".
     * =========================================================================
     */

    /**
     * Genera el encabezado principal del reporte con los datos del curso y fecha
     */
    private function generarHeaderPrincipal($curso, $fecha) {
        // Logo o título principal
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'REPORTE DE EVALUACIÓN ACADÉMICA', 0, 1, 'C');
        
        // Información del curso
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, 'Curso: ' . $curso['nombre'], 0, 1, 'C');
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->Cell(0, 6, 'Fecha de evaluación: ' . date('d/m/Y', strtotime($fecha)), 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'Reporte generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        
        // Línea separadora
        $this->pdf->Ln(5);
        $this->pdf->SetDrawColor(200, 200, 200);
        $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), $this->pdf->GetX() + 180, $this->pdf->GetY());
        $this->pdf->Ln(10);
    }
    
    /**
     * Método principal para generar el reporte PDF completo por curso y fecha
     */
    public function generarReportePorCursoFecha($curso_id, $fecha, $secciones = [], $imagenes_graficos = []) {
        try {
            // Validar que el curso existe
            $stmt = $this->pdo->prepare("SELECT id, nombre FROM cursos WHERE id = :curso_id");
            $stmt->execute([':curso_id' => $curso_id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) {
                throw new Exception("Curso no encontrado con ID: $curso_id");
            }
            
            $this->pdf->AddPage();
            
            // HEADER PRINCIPAL
            $this->generarHeaderPrincipal($curso, $fecha);
            
            // GENERAR SECCIONES EN EL ORDEN CORRECTO
            foreach ($secciones as $seccion) {
                try {
                    switch ($seccion) {
                        case 'resumen_ejecutivo':
                            $this->generarResumenEjecutivoEstilizado($curso_id, $fecha);
                            break;
                        case 'graficos_evaluacion':
                            $this->generarGraficosEvaluacion($curso_id, $fecha);
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
                        default:
                            // Sección no reconocida
                            $this->pdf->SetFont('dejavusans', 'I', 10);
                            $this->pdf->Cell(0, 10, "Sección '$seccion' no implementada.", 0, 1);
                            $this->pdf->Ln(5);
                            break;
                    }
                } catch (Exception $e) {
                    $this->agregarMensajeError($seccion, $e->getMessage());
                }
            }
            
            return $this->pdf->Output('reporte_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $curso['nombre']) . '_' . $fecha . '.pdf', 'S');
            
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
     */
    public function generarReporteEvaluacion($curso_id, $fecha, $outputPath = '') {
        try {
            // Verificar si tenemos una conexión
            if (!$this->pdo) {
                throw new Exception("No se ha establecido una conexión a la base de datos");
            }
            
            // Obtener datos del curso
            $stmt = $this->pdo->prepare("SELECT nombre FROM cursos WHERE id = ?");
            $stmt->execute([$curso_id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) {
                throw new Exception("No se encontró el curso con ID $curso_id");
            }
            
            // Configurar el título del PDF
            $this->pdf->SetTitle("Evaluación del curso: " . $curso['nombre']);
            
            // Generar portada
            $this->generarPortada($curso['nombre'], $fecha);
            
            // Generar tabla de aprovechamiento y gráficos de evaluación
            $this->generarGraficosEvaluacion($curso_id, $fecha);
            
            // Si se especificó una ruta de salida, guardar el PDF
            if (!empty($outputPath)) {
                $this->pdf->Output($outputPath, 'F');
                return true;
            }
            
            // Si no, mostrar el PDF directamente
            $this->pdf->Output('reporte_evaluacion.pdf', 'I');
            return true;
            
        } catch (Exception $e) {
            error_log("Error al generar el reporte: " . $e->getMessage());
            return false;
        }
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
}
