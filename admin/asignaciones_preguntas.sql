-- ASIGNACIONES Y PREGUNTAS (SIN DATOS DUPLICADOS)

-- ASIGNACIÓN DE PROFESORES A FORMULARIOS
INSERT INTO curso_profesores (formulario_id, profesor_id, orden, activo) VALUES
(1, 1, 1, 1), -- Dr. Carlos Mendoza - MAT301
(1, 2, 2, 1), -- Dra. Ana García - MAT301
(2, 3, 1, 1), -- Prof. Miguel Rodríguez - INF201
(2, 4, 2, 1), -- Dra. Laura Fernández - INF201
(3, 5, 1, 1), -- Dr. Roberto Silva - FIS101
(3, 6, 2, 1), -- Prof. Carmen López - FIS101
(4, 7, 1, 1), -- Dra. Patricia Morales - QUI202
(4, 8, 2, 1), -- Dr. Fernando Castro - QUI202
(5, 9, 1, 1), -- Prof. Elena Vargas - EST301
(5, 10, 2, 1), -- Dr. Andrés Ruiz - EST301
(6, 11, 1, 1), -- Dra. Isabel Herrera - HIS101
(6, 12, 2, 1), -- Prof. David Jiménez - HIS101
(7, 13, 1, 1), -- Prof. Sarah Thompson - ING301
(7, 14, 2, 1), -- Prof. Michael Davis - ING301
(8, 15, 1, 1), -- Dr. Antonio Vega - ECO201
(8, 16, 2, 1); -- Dra. Mónica Díaz - ECO201

-- PREGUNTAS ESTÁNDAR PARA TODOS LOS FORMULARIOS
INSERT INTO preguntas (texto, tipo, seccion, opciones, orden, es_obligatoria, activa) VALUES
-- Matemáticas Avanzadas (Formulario 1)
('¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 1, 1, 1),
('¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 2, 1, 1),
('¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy inadecuados", "2": "Inadecuados", "3": "Regulares", "4": "Adecuados", "5": "Muy adecuados"}}', 3, 1, 1),
('¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Nada útil", "2": "Poco útil", "3": "Moderadamente útil", "4": "Útil", "5": "Muy útil"}}', 4, 1, 1),
('Comentarios adicionales sobre el curso', 'texto', 'curso', '{"placeholder": "Comparta sus comentarios, sugerencias o experiencias sobre el curso (opcional)", "max_length": 500}', 5, 0, 1),
('¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 6, 1, 1),
('¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 7, 1, 1),
('¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy baja", "2": "Baja", "3": "Regular", "4": "Alta", "5": "Muy alta"}}', 8, 1, 1),
('¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy desorganizado", "2": "Desorganizado", "3": "Regular", "4": "Organizado", "5": "Muy organizado"}}', 9, 1, 1),
('Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"placeholder": "Comparta comentarios específicos sobre este profesor (opcional)", "max_length": 300}', 10, 0, 1);
