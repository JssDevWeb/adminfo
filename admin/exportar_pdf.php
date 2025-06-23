<?php
/**
 * Interfaz de Exportación a PDF
 * Permite seleccionar qué secciones incluir en el PDF
 */

// Obtener parámetros
$curso_id = $_GET['curso_id'] ?? '';
$fecha = $_GET['fecha'] ?? '';

if (empty($curso_id) || empty($fecha)) {
    header('Location: reportes.php');
    exit();
}

// Incluir configuración de base de datos para obtener nombre del curso
require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT nombre FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch();
    $curso_nombre = $curso ? $curso['nombre'] : 'Curso no encontrado';
} catch (Exception $e) {
    $curso_nombre = 'Error al cargar curso';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Reporte a PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .section-card {
            transition: all 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .section-preview {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Header -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-pdf"></i> 
                            Exportar Reporte a PDF
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>📚 Curso:</strong><br>
                                <?php echo htmlspecialchars($curso_nombre); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>📅 Fecha de Evaluación:</strong><br>
                                <?php echo date('d/m/Y', strtotime($fecha)); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de selección -->
                <form action="procesar_pdf.php" method="POST" id="pdfForm">
                    <input type="hidden" name="curso_id" value="<?php echo htmlspecialchars($curso_id); ?>">
                    <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-check2-square"></i> 
                                Selecciona las secciones a incluir
                            </h5>
                        </div>
                        <div class="card-body">
                            
                            <!-- Resumen Ejecutivo -->
                            <div class="section-card card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="secciones[]" value="resumen_ejecutivo" id="resumen_ejecutivo" checked>
                                        <label class="form-check-label" for="resumen_ejecutivo">
                                            <strong>📊 Resumen Ejecutivo</strong>
                                        </label>
                                    </div>
                                    <div class="section-preview mt-2">
                                        Incluye estadísticas principales, métricas clave, alertas automáticas y análisis general del rendimiento.
                                    </div>
                                </div>
                            </div>

                            <!-- Distribución de Respuestas -->
                            <div class="section-card card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="secciones[]" value="distribucion_respuestas" id="distribucion_respuestas" checked>
                                        <label class="form-check-label" for="distribucion_respuestas">
                                            <strong>📈 Distribución de Respuestas</strong>
                                        </label>
                                    </div>
                                    <div class="section-preview mt-2">
                                        Tabla con la distribución de calificaciones, frecuencias y porcentajes por valor de escala.
                                    </div>
                                </div>
                            </div>

                            <!-- Estadísticas Detalladas -->
                            <div class="section-card card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="secciones[]" value="estadisticas_detalladas" id="estadisticas_detalladas" checked>
                                        <label class="form-check-label" for="estadisticas_detalladas">
                                            <strong>📋 Estadísticas Detalladas</strong>
                                        </label>
                                    </div>
                                    <div class="section-preview mt-2">
                                        Métricas detalladas por curso y profesor, incluyendo puntuaciones y porcentajes de aprovechamiento.
                                    </div>
                                </div>
                            </div>

                            <!-- Preguntas Críticas -->
                            <div class="section-card card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="secciones[]" value="preguntas_criticas" id="preguntas_criticas" checked>
                                        <label class="form-check-label" for="preguntas_criticas">
                                            <strong>⚠️ Preguntas Más Críticas</strong>
                                        </label>
                                    </div>
                                    <div class="section-preview mt-2">
                                        Lista de preguntas con puntuaciones más bajas y mayor porcentaje de respuestas críticas.
                                    </div>
                                </div>
                            </div>

                            <!-- Gráficos -->
                            <div class="section-card card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="secciones[]" value="graficos" id="graficos">
                                        <label class="form-check-label" for="graficos">
                                            <strong>📊 Gráficos de Evaluación</strong>
                                            <span class="badge bg-warning text-dark ms-2">En desarrollo</span>
                                        </label>
                                    </div>
                                    <div class="section-preview mt-2">
                                        Representaciones visuales de los datos (implementación futura).
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Opciones adicionales -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-gear"></i> 
                                Opciones del PDF
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="formato" class="form-label">Formato de página:</label>
                                    <select class="form-select" name="formato" id="formato">
                                        <option value="A4" selected>A4 (Estándar)</option>
                                        <option value="Letter">Carta</option>
                                        <option value="Legal">Legal</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="orientacion" class="form-label">Orientación:</label>
                                    <select class="form-select" name="orientacion" id="orientacion">
                                        <option value="portrait" selected>Vertical</option>
                                        <option value="landscape">Horizontal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-success btn-lg me-3">
                                <i class="bi bi-download"></i> 
                                Generar PDF
                            </button>
                            <a href="reportes.php?curso_grafico_id=<?php echo urlencode($curso_id); ?>&fecha_grafico=<?php echo urlencode($fecha); ?>&generar_graficos=1" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> 
                                Volver a Reportes
                            </a>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar que al menos una sección esté seleccionada
        document.getElementById('pdfForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="secciones[]"]:checked');
            
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('⚠️ Debes seleccionar al menos una sección para exportar.');
                return false;
            }
            
            // Mostrar indicador de carga
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando PDF...';
            submitBtn.disabled = true;
        });

        // Mejorar experiencia con checkboxes
        document.querySelectorAll('.section-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                }
            });
        });
    </script>
</body>
</html>
