-- =====================================================
-- SCRIPT: Corrección de Codificación de Caracteres
-- PROPÓSITO: Eliminar y reinsertar preguntas con UTF-8 correcto
-- FECHA: 20 junio 2025
-- =====================================================

-- Eliminar preguntas con codificación incorrecta
DELETE FROM asignacion_preguntas;
DELETE FROM preguntas;

-- Reinsertar preguntas con codificación UTF-8 correcta
INSERT INTO preguntas (id, seccion, pregunta, tipo_respuesta, obligatoria, orden, activa) VALUES
(1, 'curso', '¿Cómo califica el contenido general del curso?', 'escala', 1, 1, 1),
(2, 'curso', '¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 1, 2, 1),
(3, 'curso', '¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 1, 3, 1),
(4, 'curso', '¿Qué tan útil considera este curso para su formación profesional?', 'escala', 1, 4, 1),
(5, 'curso', 'Comentarios adicionales sobre el curso', 'texto', 0, 5, 1),

(6, 'profesor', '¿Cómo califica la puntualidad del profesor?', 'escala', 1, 1, 1),
(7, 'profesor', '¿Qué tan claro es el profesor al explicar los conceptos?', 'escala', 1, 2, 1),
(8, 'profesor', '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 1, 3, 1),
(9, 'profesor', '¿Recomendaría este profesor a otros estudiantes?', 'escala', 1, 4, 1),
(10, 'profesor', 'Comentarios adicionales sobre el profesor', 'texto', 0, 5, 1);

-- Asignar todas las preguntas a todos los formularios
INSERT INTO asignacion_preguntas (formulario_id, pregunta_id, activa) 
SELECT f.id, p.id, 1
FROM formularios f
CROSS JOIN preguntas p
WHERE f.activo = 1 AND p.activa = 1;

-- Verificar inserción
SELECT 'Preguntas insertadas correctamente' as estado, COUNT(*) as total FROM preguntas;
SELECT 'Asignaciones creadas correctamente' as estado, COUNT(*) as total FROM asignacion_preguntas;
