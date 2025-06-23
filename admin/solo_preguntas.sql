-- SOLO PREGUNTAS (SIN DUPLICADOS)
INSERT INTO preguntas (texto, tipo, seccion, opciones, orden, es_obligatoria, activa) VALUES
('¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5}', 1, 1, 1),
('¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5}', 2, 1, 1),
('¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5}', 3, 1, 1),
('¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5}', 4, 1, 1),
('Comentarios adicionales sobre el curso', 'texto', 'curso', '{"max_length": 500}', 5, 0, 1),
('¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5}', 6, 1, 1),
('¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5}', 7, 1, 1),
('¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5}', 8, 1, 1),
('¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5}', 9, 1, 1),
('Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"max_length": 300}', 10, 0, 1);
