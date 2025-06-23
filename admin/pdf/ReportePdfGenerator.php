<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

class ReportePdfGenerator {
    private $pdo;
    private $pdf;
    private $currentY = 0;
    private $cursoNombreGlobal = '';
    private $fechaEvaluacionGlobal = '';

    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            try {
                $this->pdo = Database::getInstance()->getConnection();
            } catch (Exception $e) {
                error_log("Error al conectar a la BD en ReportePdfGenerator: " . $e->getMessage());
                $this->pdo = null;
            }
        }
        
        // Utilizar constantes de TCPDF para orientación y unidad
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->configurarPdf();
    }

    private function configurarPdf() {
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->pdf->SetCreator('Sistema de Encuestas Academicas');
        $this->pdf->SetAuthor('Academia');
        // El título se establecerá dinámicamente por reporte
        
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        $this->pdf->SetFont('dejavusans', '', 10); // Fuente base consistente
        
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);
        
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    }
    
    // Este método es un stub y no es el principal para la tarea.
    public function generarReporte($secciones = []) {
        $this->pdf->AddPage();
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'REPORTE GENÉRICO DE ENCUESTAS', 0, 1, 'C');
        // ... (implementación si fuera necesaria) ...
        return $this->pdf->Output('reporte_generico.pdf', 'S');
    }
    
    // Funciones de obtención de datos (revisadas para asegurar que usan $this->pdo)
    private function obtenerEstadisticasPorProfesor($curso_id, $fecha) {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.nombre,
                    COUNT(DISTINCT e.id) as total_encuestas_profesor,
                    COUNT(r.id) as total_respuestas_profesor,
                    AVG(r.valor_int) as promedio_profesor,
                    (SUM(CASE WHEN r.valor_int >= 8 THEN r.valor_int ELSE 0 END) * 10.0 / SUM(r.valor_int)) * 10 as satisfaccion_profesor_porcentaje  -- Ajustado
                FROM profesores p
                JOIN respuestas r ON p.id = r.profesor_id
                JOIN encuestas e ON r.encuesta_id = e.id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
                  AND pr.seccion = 'profesor' AND pr.tipo = 'escala'
                GROUP BY p.id, p.nombre
                HAVING COUNT(r.id) > 0
                ORDER BY promedio_profesor DESC
            ");
            
            $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($resultados as &$profesor) {
                $profesor['promedio_profesor'] = round(floatval($profesor['promedio_profesor'] ?? 0), 2);
                // El cálculo de satisfacción ya está en porcentaje, solo redondear.
                $profesor['satisfaccion_profesor_porcentaje'] = round(floatval($profesor['satisfaccion_profesor_porcentaje'] ?? 0), 1);
            }
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasPorProfesor: " . $e->getMessage());
            return [];
        }
    }
    
    private function agruparEnCategorias($distribucion_raw) {
        // Colores tomados de reportes.php para consistencia
        $categorias_estandar = [
            'Excelente' => ['valores' => [9, 10], 'color_rgb' => [40, 167, 69], 'nombre_grafico' => 'Excelente'], // Verde #28a745
            'Bueno' => ['valores' => [7, 8], 'color_rgb' => [23, 162, 184], 'nombre_grafico' => 'Bueno'],       // Azul #17a2b8
            'Correcto' => ['valores' => [5, 6], 'color_rgb' => [255, 193, 7], 'nombre_grafico' => 'Correcto'],    // Amarillo #ffc107
            'Regular' => ['valores' => [3, 4], 'color_rgb' => [253, 126, 20], 'nombre_grafico' => 'Regular'],     // Naranja #fd7e14
            'Deficiente' => ['valores' => [1, 2], 'color_rgb' => [220, 53, 69], 'nombre_grafico' => 'Deficiente'] // Rojo #dc3545
        ];

        $conteo_categorias = [];
        foreach ($categorias_estandar as $key => $cat) {
            $conteo_categorias[$key] = ['cantidad' => 0, 'color_rgb' => $cat['color_rgb'], 'nombre_grafico' => $cat['nombre_grafico']];
        }

        $total_respuestas_validas = 0;
        foreach ($distribucion_raw as $item) {
            $valor = (int)$item['valor_int'];
            $cantidad = (int)$item['cantidad'];
            $total_respuestas_validas += $cantidad;
            foreach ($categorias_estandar as $key => $cat_info) {
                if (in_array($valor, $cat_info['valores'])) {
                    $conteo_categorias[$key]['cantidad'] += $cantidad;
                    break;
                }
            }
        }

        $datos_finales_categorias = [];
        foreach ($conteo_categorias as $key => $data) {
            $porcentaje = $total_respuestas_validas > 0 ? round(($data['cantidad'] / $total_respuestas_validas) * 100, 1) : 0;
            // Incluir todas las categorías para la leyenda, incluso si el porcentaje es 0
            $datos_finales_categorias[] = [
                'nombre' => $data['nombre_grafico'] . ' (' . $data['cantidad'] . ')', // Usado en la leyenda del PDF
                'cantidad' => $data['cantidad'],
                'porcentaje' => $porcentaje,
                'color_rgb' => $data['color_rgb']
            ];
        }
        return $datos_finales_categorias;
    }

    private function obtenerDatosGraficos($curso_id, $fecha) {
        if (!$this->pdo) return [];
        $graficos = [];

        try {
            $stmt_curso_info = $this->pdo->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
            $stmt_curso_info->execute([':curso_id' => $curso_id]);
            $curso = $stmt_curso_info->fetch(PDO::FETCH_ASSOC);
            if (!$curso) return [];

            // Datos para el gráfico del curso
            $stmt_curso_data = $this->pdo->prepare("
                SELECT
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(r.id) as total_respuestas_escala,
                    SUM(r.valor_int) as puntuacion_real_sum,
                    (SELECT COUNT(DISTINCT pr_inner.id) FROM preguntas pr_inner WHERE pr_inner.tipo = 'escala' AND pr_inner.seccion = 'curso' AND pr_inner.activa = 1) as num_preguntas_configuradas
                FROM encuestas e
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND pr.seccion = 'curso' AND pr.tipo = 'escala'
            ");
            $stmt_curso_data->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $curso_stats = $stmt_curso_data->fetch(PDO::FETCH_ASSOC);

            $stmt_distribucion_curso = $this->pdo->prepare("
                SELECT r.valor_int, COUNT(*) as cantidad
                FROM encuestas e JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND pr.seccion = 'curso' AND pr.tipo = 'escala'
                GROUP BY r.valor_int ORDER BY r.valor_int DESC
            ");
            $stmt_distribucion_curso->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $distribucion_curso_raw = $stmt_distribucion_curso->fetchAll(PDO::FETCH_ASSOC);

            if ($curso_stats && ($curso_stats['total_encuestas'] ?? 0) > 0 && ($curso_stats['num_preguntas_configuradas'] ?? 0) > 0) {
                $categorias_curso = $this->agruparEnCategorias($distribucion_curso_raw);
                $num_preguntas_curso = (int)$curso_stats['num_preguntas_configuradas'];
                $max_puntuacion_curso = ((int)$curso_stats['total_encuestas'] * $num_preguntas_curso * 10);
                if ($max_puntuacion_curso == 0) $max_puntuacion_curso = 1;

                $graficos[] = [
                    'tipo' => 'curso', 'nombre' => $curso['nombre'],
                    'total_encuestas' => (int)$curso_stats['total_encuestas'],
                    'num_preguntas' => $num_preguntas_curso,
                    'puntuacion_real' => (int)($curso_stats['puntuacion_real_sum'] ?? 0),
                    'max_puntuacion' => $max_puntuacion_curso,
                    'categorias' => $categorias_curso,
                    'total_respuestas_reales' => (int)($curso_stats['total_respuestas_escala'] ?? 0)
                ];
            }
        } catch (Exception $e) { error_log("PDF Gen Error (Curso Data): " . $e->getMessage()); }

        // Datos para gráficos de profesores
        try {
            $stmt_profesores_evaluados = $this->pdo->prepare("
                SELECT DISTINCT p.id, p.nombre FROM profesores p
                JOIN respuestas r ON p.id = r.profesor_id JOIN encuestas e ON r.encuesta_id = e.id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND pr.seccion = 'profesor' AND pr.tipo = 'escala'
            ");
            $stmt_profesores_evaluados->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
            $profesores = $stmt_profesores_evaluados->fetchAll(PDO::FETCH_ASSOC);

            foreach ($profesores as $prof) {
                $stmt_prof_data = $this->pdo->prepare("
                    SELECT
                        COUNT(DISTINCT e.id) as total_encuestas_prof,
                        COUNT(r.id) as total_respuestas_escala_prof,
                        SUM(r.valor_int) as puntuacion_real_sum_prof,
                         (SELECT COUNT(DISTINCT pr_inner.id) FROM preguntas pr_inner WHERE pr_inner.tipo = 'escala' AND pr_inner.seccion = 'profesor' AND pr_inner.activa = 1) as num_preguntas_configuradas
                    FROM encuestas e
                    JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
                    WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND r.profesor_id = :profesor_id AND pr.seccion = 'profesor' AND pr.tipo = 'escala'
                ");
                $stmt_prof_data->execute([':curso_id' => $curso_id, ':fecha' => $fecha, ':profesor_id' => $prof['id']]);
                $prof_stats = $stmt_prof_data->fetch(PDO::FETCH_ASSOC);

                $stmt_distribucion_prof = $this->pdo->prepare("
                    SELECT r.valor_int, COUNT(*) as cantidad FROM encuestas e
                    JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
                    WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND r.profesor_id = :profesor_id AND pr.seccion = 'profesor' AND pr.tipo = 'escala'
                    GROUP BY r.valor_int ORDER BY r.valor_int DESC
                ");
                $stmt_distribucion_prof->execute([':curso_id' => $curso_id, ':fecha' => $fecha, ':profesor_id' => $prof['id']]);
                $distribucion_prof_raw = $stmt_distribucion_prof->fetchAll(PDO::FETCH_ASSOC);

                if ($prof_stats && ($prof_stats['total_encuestas_prof'] ?? 0) > 0 && ($prof_stats['num_preguntas_configuradas'] ?? 0) > 0) {
                    $categorias_prof = $this->agruparEnCategorias($distribucion_prof_raw);
                    $num_preguntas_prof = (int)$prof_stats['num_preguntas_configuradas'];
                    $max_puntuacion_prof = ((int)$prof_stats['total_encuestas_prof'] * $num_preguntas_prof * 10);
                    if ($max_puntuacion_prof == 0) $max_puntuacion_prof = 1;

                    $graficos[] = [
                        'tipo' => 'profesor', 'nombre' => $prof['nombre'],
                        'total_encuestas' => (int)$prof_stats['total_encuestas_prof'],
                        'num_preguntas' => $num_preguntas_prof,
                        'puntuacion_real' => (int)($prof_stats['puntuacion_real_sum_prof'] ?? 0),
                        'max_puntuacion' => $max_puntuacion_prof,
                        'categorias' => $categorias_prof,
                        'total_respuestas_reales' => (int)($prof_stats['total_respuestas_escala_prof'] ?? 0)
                    ];
                }
            }
        } catch (Exception $e) { error_log("PDF Gen Error (Prof Data): " . $e->getMessage()); }
        
        return $graficos;
    }

    private function dibujarSectorTorta($xc, $yc, $r, $a, $b, $colorRgbArray) {
        $this->pdf->SetFillColorArray($colorRgbArray);
        $this->pdf->PieSector($xc, $yc, $r, $a, $b, 'F', false, 0, 2);
    }

    private function dibujarGraficoTortaConLeyenda($xc, $yc, $r, $data_categorias, $leyendaXStart, $leyendaYStart) {
        if (empty($data_categorias)) {
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->SetXY($xc - $r, $yc - 5);
            $this->pdf->Cell($r * 2, 10, 'No hay datos para este gráfico', 0, 1, 'C');
            return $yc + 10;
        }

        $angulo_inicio = 0;
        foreach ($data_categorias as $cat) {
            if ($cat['porcentaje'] > 0) {
                $angulo_fin = $angulo_inicio + ($cat['porcentaje'] / 100) * 360;
                $this->dibujarSectorTorta($xc, $yc, $r, $angulo_inicio, $angulo_fin, $cat['color_rgb']);
                $angulo_inicio = $angulo_fin;
            }
        }
        $this->pdf->SetDrawColor(100, 100, 100);
        $this->pdf->Circle($xc, $yc, $r);

        $this->pdf->SetFont('dejavusans', '', 7.5); // Fuente más pequeña para leyenda
        $currentLeyendaY = $leyendaYStart;
        $leyendaAlturaCaja = 3.5;
        $leyendaAnchoCaja = 3.5;
        $espacioLeyenda = 1;

        foreach ($data_categorias as $cat) {
            if ($currentLeyendaY + $leyendaAlturaCaja > $this->pdf->getPageHeight() - $this->pdf->getBreakMargin() - 5) {
                 return $currentLeyendaY + 1000;
            }
            $this->pdf->SetFillColorArray($cat['color_rgb']);
            $this->pdf->Rect($leyendaXStart, $currentLeyendaY, $leyendaAnchoCaja, $leyendaAlturaCaja, 'F');
            $this->pdf->SetXY($leyendaXStart + $leyendaAnchoCaja + 1.5, $currentLeyendaY - 0.5);
            $this->pdf->MultiCell(45, $leyendaAlturaCaja, $cat['nombre'] . ': ' . $cat['porcentaje'] . '%', 0, 'L');
            $currentLeyendaY = $this->pdf->GetY() + $espacioLeyenda;
        }
        return max($yc + $r + 3, $currentLeyendaY); // Espacio reducido después del gráfico/leyenda
    }

    private function generarGraficosEvaluacion($curso_id, $fecha) {
        $datos_graficos = $this->obtenerDatosGraficos($curso_id, $fecha);

        if (empty($datos_graficos)) {
            $this->checkPageBreak(10);
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay datos suficientes para generar gráficos de evaluación.', 0, 1, 'L');
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
            return;
        }
        
        $this->checkPageBreak(12);
        $this->pdf->SetFont('dejavusans', 'B', 12); // Título de sección
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(0, 9, mb_strtoupper('Gráficos de Evaluación', 'UTF-8'), 0, 1, 'L', true);
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();

        $chartRadius = 20;
        $chartDiameter = $chartRadius * 2;
        $padding = 3;
        $infoHeight = 16;
        $leyendaWidth = 45;
        $blockWidth = $chartDiameter + $leyendaWidth + ($padding * 3) ; // Un poco más de padding

        $pageContentWidth = $this->pdf->getPageWidth() - $this->pdf->getMargins()['left'] - $this->pdf->getMargins()['right'];
        $numCols = floor($pageContentWidth / $blockWidth);
        if ($numCols == 0) $numCols = 1;
        if ($numCols > 2) $numCols = 2; // Máximo 2 columnas

        $colWidth = $pageContentWidth / $numCols;
        $currentX = $this->pdf->getMargins()['left'];
        $maxYinRow = $this->currentY;

        for ($idx = 0; $idx < count($datos_graficos); $idx++) {
            $grafico = $datos_graficos[$idx];
            $aprovechamiento = $grafico['max_puntuacion'] > 0 ? round(($grafico['puntuacion_real'] / $grafico['max_puntuacion']) * 100, 1) : 0;

            $neededHeight = $infoHeight + $chartDiameter + ($padding * 2); // Altura base
             // Si la leyenda es muy larga, puede necesitar más
            $numCategorias = count($grafico['categorias']);
            $leyendaEstimadaH = $numCategorias * (3.5 + 1) + 5; // (altura caja + espacio) * num_items + padding
            $neededHeight = max($neededHeight, $infoHeight + $leyendaEstimadaH + $padding);


            if (($idx > 0 && $idx % $numCols == 0) || ($this->currentY + $neededHeight > $this->pdf->getPageHeight() - $this->pdf->getBreakMargin())) {
                if ($idx % $numCols == 0 && $idx > 0) {
                     $this->pdf->SetY($maxYinRow + $padding);
                } else {
                    $this->pdf->AddPage();
                }
                $currentX = $this->pdf->getMargins()['left'];
                $this->currentY = $this->pdf->GetY();
                $maxYinRow = $this->currentY;

                if ($this->pdf->getPage() > 1 && $this->currentY < PDF_MARGIN_TOP + 10) {
                    $this->pdf->SetFont('dejavusans', 'B', 12);
                    $this->pdf->SetFillColor(230, 230, 230);
                    $this->pdf->Cell(0, 9, mb_strtoupper('Gráficos de Evaluación (Continuación)', 'UTF-8'), 0, 1, 'L', true);
                    $this->pdf->Ln(3);
                    $this->currentY = $this->pdf->GetY();
                    $maxYinRow = $this->currentY;
                }
            }

            $this->pdf->SetY($this->currentY);
            $startYForBlock = $this->currentY;

            $this->pdf->SetX($currentX + $padding);
            $this->pdf->SetFont('dejavusans', 'B', 8.5); // Un poco más pequeño
            $this->pdf->MultiCell($colWidth - ($padding * 2), 4, ucfirst($grafico['tipo']) . ': ' . htmlspecialchars($grafico['nombre']), 0, 'L');

            $this->pdf->SetX($currentX + $padding);
            $this->pdf->SetFont('dejavusans', '', 7); // Más pequeño para info detallada
            $infoText = 'Enc: ' . $grafico['total_encuestas'] .
                        ' | Resp: ' . $grafico['total_respuestas_reales'] .
                        ' | Punt: ' . $grafico['puntuacion_real'] . '/' . $grafico['max_puntuacion'] .
                        ' (' . $aprovechamiento . '%)';
            $this->pdf->MultiCell($colWidth - ($padding*2), 3, $infoText, 0, 'L');
            $this->pdf->Ln(0.5);

            $chartDrawX = $currentX + $padding + $chartRadius;
            $chartDrawY = $this->pdf->GetY() + $chartRadius;
            $leyendaDrawX = $currentX + $padding + $chartDiameter + 2; // Menos espacio
            $leyendaDrawY = $this->pdf->GetY();

            $endY = $this->dibujarGraficoTortaConLeyenda($chartDrawX, $chartDrawY, $chartRadius, $grafico['categorias'], $leyendaDrawX, $leyendaDrawY);

            if ($endY > ($this->pdf->getPageHeight() - $this->pdf->getBreakMargin() + 999 )){
                 $this->pdf->AddPage();
                 $currentX = $this->pdf->getMargins()['left'];
                 $this->currentY = $this->pdf->GetY();
                 $maxYinRow = $this->currentY;
                 $this->pdf->SetFont('dejavusans', 'B', 12);
                 $this->pdf->SetFillColor(230, 230, 230);
                 $this->pdf->Cell(0, 9, mb_strtoupper('Gráficos de Evaluación (Continuación)', 'UTF-8'), 0, 1, 'L', true);
                 $this->pdf->Ln(3);
                 $this->currentY = $this->pdf->GetY();
                 $maxYinRow = $this->currentY;
                 $idx--;
                 continue;
            }

            $maxYinRow = max($maxYinRow, $endY);

            $currentX += $colWidth;
            if (($idx + 1) % $numCols == 0 || $idx == count($datos_graficos) -1) {
                $this->currentY = $maxYinRow + $padding;
                $this->pdf->SetY($this->currentY);
                $currentX = $this->pdf->getMargins()['left'];
                $maxYinRow = $this->currentY;
                if ($idx < count($datos_graficos) -1) $this->pdf->Ln(1);
            } else {
                 $this->pdf->SetY($startYForBlock);
            }
        }
        $this->pdf->SetY($maxYinRow);
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();
    }

    private function generarTablaEstilizada($headers, $data, $widths, $title = '') {
        $minSectionHeight = empty($title) ? 0 : 11;
        $minSectionHeight += 6;
        $minSectionHeight += (count($data) > 0 ? 5 : 0);
        $minSectionHeight += 5;

        $this->checkPageBreak($minSectionHeight);
        // $startY = $this->pdf->GetY(); // No es necesario si checkPageBreak actualiza currentY
        
        if (!empty($title)) {
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->SetFillColor(230, 230, 230);
            $this->pdf->Cell(0, 9, mb_strtoupper($title, 'UTF-8'), 0, 1, 'L', true);
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
        }

        $this->pdf->SetFont('dejavusans', 'B', 8);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(170, 170, 170);
        $this->pdf->SetLineWidth(0.15);

        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 6, mb_strtoupper($header, 'UTF-8'), 1, 0, 'C', 1);
        }
        $this->pdf->Ln();
        $this->currentY = $this->pdf->GetY();

        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->SetTextColor(30, 30, 30);
        $fill = false;

        foreach ($data as $row) {
            $rowHeight = 0;
            foreach($row as $i => $cellContent){
                // Calcular altura de celda basada en el contenido y ancho
                 $rowHeight = max($rowHeight, $this->pdf->getStringHeight($widths[$i], (string)$cellContent, false, true, '', 1));
            }
            $rowHeight = max($rowHeight, 4.5); // Altura mínima de celda

            $this->checkPageBreak($rowHeight);
            $this->pdf->SetFillColor($fill ? 248 : 255, $fill ? 248 : 255, $fill ? 248 : 255);
            
            foreach ($row as $i => $cell) {
                $align = (is_numeric($cell) && !is_string($cell)) || str_ends_with((string)$cell, '%') ? 'R' : 'L';
                if (str_ends_with((string)$cell, '%')) $align = 'C';
                $this->pdf->MultiCell($widths[$i], $rowHeight, (string)$cell, 1, $align, $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            }
            $this->pdf->Ln($rowHeight);
            $this->currentY = $this->pdf->GetY();
            $fill = !$fill;
        }
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();
    }

    private function generarTablaEstadisticasDetalladas($curso_id, $fecha) {
        $datos_graficos = $this->obtenerDatosGraficos($curso_id, $fecha);
        if (empty($datos_graficos)) {
            $this->checkPageBreak(10);
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0,10, 'No hay datos para la tabla de estadísticas detalladas.',0,1,'L');
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
            return;
        }

        $headers = ['Tipo', 'Curso/Profesor', 'Enc.', 'Resp.', 'Puntuación', 'Aprov.'];
        $data = [];
        // Anchos ajustados para un total aproximado de 180 (A4 menos márgenes)
        $widths = [18, 67, 15, 18, 32, 20];

        foreach ($datos_graficos as $grafico) {
            $puntuacion_str = $grafico['puntuacion_real'] . '/' . $grafico['max_puntuacion'];
            $aprovechamiento = $grafico['max_puntuacion'] > 0 ?
                               number_format(($grafico['puntuacion_real'] / $grafico['max_puntuacion']) * 100, 1) . '%' : '0%';
            
            $data[] = [
                ucfirst($grafico['tipo']),
                htmlspecialchars($grafico['nombre']),
                $grafico['total_encuestas'],
                $grafico['total_respuestas_reales'],
                $puntuacion_str,
                $aprovechamiento
            ];
        }

        if (empty($data)) return;
        $this->generarTablaEstilizada($headers, $data, $widths, 'Estadísticas Detalladas');
    }

    private function generarResumenEjecutivoEstilizado($curso_id, $fecha) {
        if (!$this->pdo) return;
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT e.id) as total_encuestas_global, COUNT(DISTINCT r.profesor_id) as total_profesores_evaluados,
                   AVG(r.valor_int) as promedio_general, STDDEV(r.valor_int) as desviacion_general,
                   MIN(r.valor_int) as valor_minimo, MAX(r.valor_int) as valor_maximo
            FROM encuestas e JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND pr.tipo = 'escala'
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stats || ($stats['total_encuestas_global'] ?? 0) == 0) {
            $this->checkPageBreak(10);
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay datos para el resumen ejecutivo.', 0, 1,'L');
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
            return;
        }

        $stmt_valores = $this->pdo->prepare("
            SELECT r.valor_int FROM encuestas e JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha AND pr.tipo = 'escala' ORDER BY r.valor_int
        ");
        $stmt_valores->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $valores_ordenados = $stmt_valores->fetchAll(PDO::FETCH_COLUMN);

        $total_valores = count($valores_ordenados);
        $percentil_25 = $total_valores > 0 ? $valores_ordenados[floor($total_valores * 0.25)] : 'N/A';
        $mediana = $total_valores > 0 ? $valores_ordenados[floor($total_valores * 0.5)] : 'N/A';
        $percentil_75 = $total_valores > 0 ? $valores_ordenados[floor($total_valores * 0.75)] : 'N/A';

        $headers = ['Métrica', 'Valor'];
        $data = [
            ['Total Encuestas', $stats['total_encuestas_global']],
            ['Total Profesores Evaluados', $stats['total_profesores_evaluados']],
            ['Promedio General (1-10)', number_format($stats['promedio_general'] ?? 0, 2)],
            ['Mediana', $mediana],
            ['Desviación Estándar', number_format($stats['desviacion_general'] ?? 0, 2)],
            ['Rango (Mín-Máx)', ($stats['valor_minimo'] ?? 'N/A') . ' - ' . ($stats['valor_maximo'] ?? 'N/A')],
            ['Percentil 25', $percentil_25],
            ['Percentil 75', $percentil_75],
        ];
        $widths = [110, 70]; // Ajustar si es necesario
        
        $this->generarTablaEstilizada($headers, $data, $widths, 'Resumen Ejecutivo');
    }

    private function generarPreguntasCriticasEstilizadas($curso_id, $fecha) {
        if (!$this->pdo) return;
        $stmt = $this->pdo->prepare("
            SELECT pr.texto as texto_pregunta, pr.seccion, AVG(r.valor_int) as promedio,
                   COUNT(r.id) as total_respuestas, COUNT(CASE WHEN r.valor_int <= 5 THEN 1 END) as respuestas_bajas
            FROM encuestas e JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE pr.tipo = 'escala' AND e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
            GROUP BY pr.id, pr.texto, pr.seccion HAVING COUNT(r.id) >= 1 AND AVG(r.valor_int) < 7.0
            ORDER BY promedio ASC, respuestas_bajas DESC LIMIT 10
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($preguntas)) {
            $this->checkPageBreak(10);
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No se encontraron preguntas críticas.', 0, 1,'L');
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
            return;
        }

        $headers = ['Sección', 'Pregunta Crítica', 'Prom.', 'Bajas (%)'];
        $data = [];
        $widths = [22, 98, 20, 20];

        foreach ($preguntas as $p) {
            $porcentaje_critico = $p['total_respuestas'] > 0 ? round(($p['respuestas_bajas'] / $p['total_respuestas']) * 100, 1) : 0;
            $data[] = [
                ucfirst(htmlspecialchars($p['seccion'])), htmlspecialchars($p['texto_pregunta']),
                number_format($p['promedio'], 2), $p['respuestas_bajas'] . '(' . $porcentaje_critico . '%)'
            ];
        }
        $this->generarTablaEstilizada($headers, $data, $widths, 'Preguntas Más Críticas');
    }

    private function generarComentariosCurso($curso_id, $fecha) {
        if (!$this->pdo) return;
        $stmt = $this->pdo->prepare("
            SELECT r.valor_text as comentario, pr.texto as pregunta_texto, e.fecha_envio FROM encuestas e
            JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE pr.tipo = 'texto' AND pr.seccion = 'curso' AND e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
              AND r.valor_text IS NOT NULL AND TRIM(r.valor_text) != '' AND CHAR_LENGTH(TRIM(r.valor_text)) > 5
            ORDER BY e.fecha_envio DESC LIMIT 10
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($comentarios)) {
            $this->checkPageBreak(10);
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay comentarios del curso para mostrar.', 0, 1,'L');
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
            return;
        }

        $this->checkPageBreak(12);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(0, 9, mb_strtoupper('Comentarios del Curso', 'UTF-8'), 0, 1, 'L', true);
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();

        foreach ($comentarios as $comentario) {
            $this->checkPageBreak(15);
            $this->pdf->SetFont('dejavusans', 'B', 9);
            $this->pdf->MultiCell(0, 4.5, 'Pregunta: ' . htmlspecialchars($comentario['pregunta_texto']), 0, 'L');
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->MultiCell(0, 4.5, '"' . htmlspecialchars(trim($comentario['comentario'])) . '"', 0, 'L');
            $this->pdf->SetFont('dejavusans', '', 8);
            $this->pdf->Cell(0, 4.5, 'Fecha: ' . date('d/m/Y', strtotime($comentario['fecha_envio'])), 0, 1, 'R');
            $this->pdf->Ln(1.5);
            $this->currentY = $this->pdf->GetY();
        }
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();
    }
    
    private function generarComentariosProfesores($curso_id, $fecha) {
        if (!$this->pdo) return;
        $stmt = $this->pdo->prepare("
            SELECT r.valor_text as comentario, pr.texto as pregunta_texto, p.nombre as profesor_nombre, e.fecha_envio
            FROM encuestas e JOIN respuestas r ON e.id = r.encuesta_id JOIN preguntas pr ON r.pregunta_id = pr.id
            LEFT JOIN profesores p ON r.profesor_id = p.id
            WHERE pr.tipo = 'texto' AND pr.seccion = 'profesor' AND e.curso_id = :curso_id AND DATE(e.fecha_envio) = :fecha
              AND r.valor_text IS NOT NULL AND TRIM(r.valor_text) != '' AND CHAR_LENGTH(TRIM(r.valor_text)) > 5
            ORDER BY p.nombre, e.fecha_envio DESC LIMIT 20
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($comentarios)) {
            $this->checkPageBreak(10);
            $this->pdf->SetFont('dejavusans', 'I', 10);
            $this->pdf->Cell(0, 10, 'No hay comentarios de profesores para mostrar.', 0, 1,'L');
            $this->pdf->Ln(2);
            $this->currentY = $this->pdf->GetY();
            return;
        }
        
        $this->checkPageBreak(12);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(0, 9, mb_strtoupper('Comentarios de Profesores', 'UTF-8'), 0, 1, 'L', true);
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();

        $currentProfesor = null;
        foreach ($comentarios as $comentario) {
            $this->checkPageBreak(20);
            if ($currentProfesor !== $comentario['profesor_nombre']) {
                if ($currentProfesor !== null) $this->pdf->Ln(2.5);
                $this->pdf->SetFont('dejavusans', 'B', 10);
                $this->pdf->Cell(0, 5.5, 'Profesor: ' . htmlspecialchars($comentario['profesor_nombre'] ?? 'No especificado'), 0, 1, 'L');
                $currentProfesor = $comentario['profesor_nombre'];
                $this->currentY = $this->pdf->GetY();
            }

            $this->pdf->SetFont('dejavusans', 'B', 9);
            $this->pdf->MultiCell(0, 4.5, 'Pregunta: ' . htmlspecialchars($comentario['pregunta_texto']), 0, 'L');
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->MultiCell(0, 4.5, '"' . htmlspecialchars(trim($comentario['comentario'])) . '"', 0, 'L');
            $this->pdf->SetFont('dejavusans', '', 8);
            $this->pdf->Cell(0, 4.5, 'Fecha: ' . date('d/m/Y', strtotime($comentario['fecha_envio'])), 0, 1, 'R');
            $this->pdf->Ln(1.5);
            $this->currentY = $this->pdf->GetY();
        }
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();
    }

    public function Header() {
        // No mostrar header en la primera página si ya tenemos un título principal allí.
        if ($this->pdf->getPage() == 1 && !empty($this->cursoNombreGlobal)) {
             // Podríamos añadir un logo aquí si se quisiera, o dejarlo vacío.
            return;
        }

        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->SetTextColor(128,128,128); // Gris para el header

        $headerText = '';
        if(!empty($this->cursoNombreGlobal) && !empty($this->fechaEvaluacionGlobal)){
            $headerText = 'Reporte Evaluación: ' . htmlspecialchars($this->cursoNombreGlobal) . '  |  Fecha: ' . htmlspecialchars(date('d/m/Y', strtotime($this->fechaEvaluacionGlobal)));
        } elseif (!empty($this->cursoNombreGlobal)) {
            $headerText = 'Reporte Evaluación: ' . htmlspecialchars($this->cursoNombreGlobal);
        } else {
            $headerText = 'Reporte de Evaluación Académica'; // Fallback
        }

        $pageNumber = 'Pág. ' . $this->pdf->getAliasNumPage() . '/' . $this->pdf->getAliasNbPages();

        // Usar GetPageWidth para calcular anchos relativos si es necesario
        $pageWidth = $this->pdf->getPageWidth() - $this->pdf->getMargins()['left'] - $this->pdf->getMargins()['right'];

        $this->pdf->Cell($pageWidth * 0.7, 8, $headerText, 0, false, 'L'); // 70% del ancho para el texto
        $this->pdf->Cell($pageWidth * 0.3, 8, $pageNumber, 0, false, 'R');  // 30% para el número de página
        
        $this->pdf->Ln(4); // Espacio después del texto del header
        $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), $this->pdf->getPageWidth() - $this->pdf->getMargins()['right'], $this->pdf->GetY());
        $this->pdf->SetTextColor(0,0,0); // Restaurar color de texto
        $this->currentY = $this->pdf->GetY() + 2; // Actualizar currentY después de la línea y un pequeño margen
    }

    public function Footer() {
        $this->pdf->SetY(-15); // Posición a 1.5 cm del final
        $this->pdf->SetFont('dejavusans', 'I', 8);
        $this->pdf->SetTextColor(128,128,128);
        $this->pdf->Cell(0, 10, 'Sistema de Encuestas Académicas', 0, false, 'L');
        $this->pdf->Cell(0, 10, 'Página ' . $this->pdf->getAliasNumPage() . '/' . $this->pdf->getAliasNbPages(), 0, false, 'R');
        $this->pdf->SetTextColor(0,0,0); // Restaurar color de texto
    }
    
    public function generarReportePorCursoFecha($curso_id, $fecha, $secciones = [], $imagenes_graficos = []) {
        if (!$this->pdo) {
            return $this->generarPdfError(new Exception("Conexión a BD no disponible."), $curso_id, $fecha);
        }
        try {
            $stmt = $this->pdo->prepare("SELECT id, nombre FROM cursos WHERE id = :curso_id");
            $stmt->execute([':curso_id' => $curso_id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$curso) throw new Exception("Curso no encontrado con ID: $curso_id");

            $this->cursoNombreGlobal = $curso['nombre'];
            $this->fechaEvaluacionGlobal = $fecha;

            $this->pdf->SetTitle('Reporte Evaluación - ' . $this->cursoNombreGlobal . ' - ' . $this->fechaEvaluacionGlobal);
            $this->pdf->AddPage();
            $this->currentY = $this->pdf->GetY(); // Iniciar Y después de AddPage (que llama a Header)

            // Título principal del reporte en la primera página (debajo del Header si existe)
            $this->pdf->SetFont('dejavusans', 'B', 15);
            $this->pdf->MultiCell(0, 9, mb_strtoupper('Reporte de Evaluación Académica', 'UTF-8'), 0, 'C', false, 1);
            $this->pdf->SetFont('dejavusans', 'B', 11);
            $this->pdf->MultiCell(0, 7, 'Curso: ' . htmlspecialchars($this->cursoNombreGlobal), 0, 'C', false, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->MultiCell(0, 6, 'Fecha de evaluación: ' . date('d/m/Y', strtotime($this->fechaEvaluacionGlobal)), 0, 'C', false, 1);
            $this->pdf->Ln(5);
            $this->currentY = $this->pdf->GetY();
            
            $ordenSecciones = [
                'resumen_ejecutivo', 'estadisticas_detalladas', 'graficos_evaluacion',
                'preguntas_criticas', 'comentarios_curso', 'comentarios_profesores'
            ];
            $seccionesOrdenadas = array_values(array_filter($ordenSecciones, function($s) use ($secciones) {
                return in_array($s, $secciones);
            }));

            foreach ($seccionesOrdenadas as $seccion) {
                try {
                    switch ($seccion) {
                        case 'resumen_ejecutivo': $this->generarResumenEjecutivoEstilizado($curso_id, $fecha); break;
                        case 'estadisticas_detalladas': $this->generarTablaEstadisticasDetalladas($curso_id, $fecha); break;
                        case 'graficos_evaluacion': $this->generarGraficosEvaluacion($curso_id, $fecha); break;
                        case 'preguntas_criticas': $this->generarPreguntasCriticasEstilizadas($curso_id, $fecha); break;
                        case 'comentarios_curso': $this->generarComentariosCurso($curso_id, $fecha); break;
                        case 'comentarios_profesores': $this->generarComentariosProfesores($curso_id, $fecha); break;
                        default:
                            $this->checkPageBreak(10);
                            $this->pdf->SetFont('dejavusans', 'I', 10);
                            $this->pdf->Cell(0, 10, "Sección '$seccion' no implementada.", 0, 1, 'L');
                            $this->pdf->Ln(2);
                            break;
                    }
                } catch (Exception $e_seccion) {
                    $this->agregarMensajeErrorInterno("Error en sección '$seccion': " . $e_seccion->getMessage());
                }
                 $this->currentY = $this->pdf->GetY();
            }
            
            return $this->pdf->Output(null, 'S');
            
        } catch (Exception $e) {
            error_log("Error mayor en generarReportePorCursoFecha: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return $this->generarPdfError($e, $curso_id, $fecha);
        }
    }
    
    private function checkPageBreak($h) {
        if ($this->currentY + $h > $this->pdf->getPageHeight() - $this->pdf->getBreakMargin()) {
            $this->pdf->AddPage();
            $this->currentY = $this->pdf->GetY(); // Actualizar Y después del salto (Header ya se ejecutó)
        }
    }

    private function agregarMensajeErrorInterno($mensaje) {
        $this->checkPageBreak(20);
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetTextColor(200, 0, 0);
        $this->pdf->MultiCell(0, 5, "ERROR INTERNO DEL REPORTE:", 0, 'L');
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->MultiCell(0, 5, htmlspecialchars($mensaje), 0, 'L');
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->Ln(3);
        $this->currentY = $this->pdf->GetY();
    }

    private function generarPdfError(Exception $e, $curso_id, $fecha) {
        try {
            $errorPdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $errorPdf->SetMargins(15, 20, 15);
            $errorPdf->SetFont('dejavusans', '', 10);
            $errorPdf->AddPage();
            $errorPdf->SetFont('dejavusans', 'B', 16);
            $errorPdf->Cell(0, 10, 'Error al Generar el Reporte PDF', 0, 1, 'C');
            $errorPdf->Ln(10);
            $errorPdf->SetFont('dejavusans', '', 10);
            $errorPdf->MultiCell(0, 5, "Se produjo un error grave al generar el reporte para el curso ID '$curso_id' y fecha '$fecha'.", 0, 'L');
            $errorPdf->Ln(5);
            $errorPdf->MultiCell(0, 5, "Mensaje: " . htmlspecialchars($e->getMessage()), 0, 'L');
            if (defined('DEBUG') && DEBUG === true) { // Asegurar que DEBUG sea booleano
                $errorPdf->Ln(5);
                $errorPdf->MultiCell(0, 4, "Archivo: " . htmlspecialchars($e->getFile()) . " (Línea: " . $e->getLine() . ")", 0, 'L');
                $errorPdf->Ln(3);
                $errorPdf->SetFont('dejavusans', '', 7);
                $errorPdf->MultiCell(0, 3, "Traza: \n" . htmlspecialchars($e->getTraceAsString()), 0, 'L');
            }
            return $errorPdf->Output(null, 'S');
        } catch (Exception $ex) {
            return "Error crítico generando PDF de error: " . htmlspecialchars($e->getMessage()) . " | Detalle adicional en fallback: " . htmlspecialchars($ex->getMessage());
        }
    }

    public function generarReporteEvaluacion($curso_id, $fecha, $outputPath = '') {
        if (!$this->pdo) {
            error_log("generarReporteEvaluacion: PDO no disponible.");
            return false;
        }
        try {
            $stmt = $this->pdo->prepare("SELECT nombre FROM cursos WHERE id = ?");
            $stmt->execute([$curso_id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$curso) throw new Exception("Curso ID $curso_id no encontrado.");

            $seccionesDefault = [
                'resumen_ejecutivo', 'estadisticas_detalladas', 'graficos_evaluacion',
                'preguntas_criticas', 'comentarios_curso', 'comentarios_profesores'
            ];
            
            $pdfContent = $this->generarReportePorCursoFecha($curso_id, $fecha, $seccionesDefault, []);

            if (empty($pdfContent) || strpos($pdfContent, '%PDF') !== 0) {
                throw new Exception("La generación del contenido del PDF falló o no es un PDF válido.");
            }

            if (!empty($outputPath)) {
                if (file_put_contents($outputPath, $pdfContent) === false) {
                     throw new Exception("No se pudo escribir el archivo PDF en: $outputPath");
                }
                return true;
            }
            return true;
            
        } catch (Exception $e) {
            error_log("Error en generarReporteEvaluacion: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    // generarPortada no se usa activamente.
    private function generarPortada($nombre_curso, $fecha) {}
}
>>>>>>> REPLACE
