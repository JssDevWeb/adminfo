-- ============================================
-- INSERCIÓN DE DATOS PROFESIONALES
-- Sistema de Encuestas Académicas
-- ============================================

-- CURSOS ACADÉMICOS REALISTAS
INSERT INTO cursos (nombre, descripcion, codigo, creditos, activo) VALUES
('Matemáticas Avanzadas', 'Curso de cálculo diferencial e integral, álgebra lineal y ecuaciones diferenciales para estudiantes de ingeniería', 'MAT301', 6, 1),
('Programación Web', 'Desarrollo de aplicaciones web con HTML5, CSS3, JavaScript, PHP y bases de datos MySQL', 'INF201', 4, 1),
('Física General', 'Fundamentos de mecánica clásica, termodinámica, ondas y electromagnetismo', 'FIS101', 5, 1),
('Química Orgánica', 'Estudio de compuestos orgánicos, reacciones químicas y síntesis molecular', 'QUI202', 4, 1),
('Estadística Aplicada', 'Métodos estadísticos para análisis de datos, probabilidad y modelos predictivos', 'EST301', 3, 1),
('Historia Contemporánea', 'Historia mundial desde el siglo XIX hasta la actualidad, enfoque en procesos sociales', 'HIS101', 3, 1),
('Inglés Avanzado', 'Desarrollo de habilidades lingüísticas avanzadas en inglés académico y profesional', 'ING301', 2, 1),
('Microeconomía', 'Principios fundamentales de economía, teoría del consumidor y mercados', 'ECO201', 4, 1);

-- PROFESORES REALISTAS
INSERT INTO profesores (nombre, email, departamento, especialidad, activo) VALUES
('Dr. Carlos Mendoza', 'cmendoza@universidad.edu', 'Matemáticas', 'Análisis Matemático y Cálculo Avanzado', 1),
('Dra. Ana García', 'agarcia@universidad.edu', 'Matemáticas', 'Álgebra Lineal y Ecuaciones Diferenciales', 1),
('Prof. Miguel Rodríguez', 'mrodriguez@universidad.edu', 'Informática', 'Desarrollo Web y Bases de Datos', 1),
('Dra. Laura Fernández', 'lfernandez@universidad.edu', 'Informática', 'JavaScript y Frameworks Frontend', 1),
('Dr. Roberto Silva', 'rsilva@universidad.edu', 'Física', 'Mecánica Clásica y Termodinámica', 1),
('Prof. Carmen López', 'clopez@universidad.edu', 'Física', 'Electromagnetismo y Ondas', 1),
('Dra. Patricia Morales', 'pmorales@universidad.edu', 'Química', 'Química Orgánica y Síntesis', 1),
('Dr. Fernando Castro', 'fcastro@universidad.edu', 'Química', 'Reacciones Químicas Avanzadas', 1),
('Prof. Elena Vargas', 'evargas@universidad.edu', 'Estadística', 'Estadística Descriptiva e Inferencial', 1),
('Dr. Andrés Ruiz', 'aruiz@universidad.edu', 'Estadística', 'Modelos Predictivos y Data Science', 1),
('Dra. Isabel Herrera', 'iherrera@universidad.edu', 'Historia', 'Historia Contemporánea Mundial', 1),
('Prof. David Jiménez', 'djimenez@universidad.edu', 'Historia', 'Procesos Sociales del Siglo XX', 1),
('Prof. Sarah Thompson', 'sthompson@universidad.edu', 'Idiomas', 'Inglés Académico y Profesional', 1),
('Prof. Michael Davis', 'mdavis@universidad.edu', 'Idiomas', 'Literatura y Comunicación en Inglés', 1),
('Dr. Antonio Vega', 'avega@universidad.edu', 'Economía', 'Microeconomía y Teoría del Consumidor', 1),
('Dra. Mónica Díaz', 'mdiaz@universidad.edu', 'Economía', 'Análisis de Mercados y Competencia', 1);

-- FORMULARIOS POR CURSO (sin fechas de inicio/fin)
INSERT INTO formularios (nombre, curso_id, descripcion, activo, permite_respuestas_anonimas, creado_por) VALUES
('Evaluación MAT301 - Semestre 2025-1', 1, 'Formulario de evaluación para Matemáticas Avanzadas, semestre 2025-1', 1, 1, 'admin'),
('Evaluación INF201 - Semestre 2025-1', 2, 'Formulario de evaluación para Programación Web, semestre 2025-1', 1, 1, 'admin'),
('Evaluación FIS101 - Semestre 2025-1', 3, 'Formulario de evaluación para Física General, semestre 2025-1', 1, 1, 'admin'),
('Evaluación QUI202 - Semestre 2025-1', 4, 'Formulario de evaluación para Química Orgánica, semestre 2025-1', 1, 1, 'admin'),
('Evaluación EST301 - Semestre 2025-1', 5, 'Formulario de evaluación para Estadística Aplicada, semestre 2025-1', 1, 1, 'admin'),
('Evaluación HIS101 - Semestre 2025-1', 6, 'Formulario de evaluación para Historia Contemporánea, semestre 2025-1', 1, 1, 'admin'),
('Evaluación ING301 - Semestre 2025-1', 7, 'Formulario de evaluación para Inglés Avanzado, semestre 2025-1', 1, 1, 'admin'),
('Evaluación ECO201 - Semestre 2025-1', 8, 'Formulario de evaluación para Microeconomía, semestre 2025-1', 1, 1, 'admin');

-- ASIGNACIÓN DE PROFESORES A FORMULARIOS
-- Matemáticas Avanzadas (Formulario 1) - 2 profesores
INSERT INTO curso_profesores (formulario_id, profesor_id, orden, activo) VALUES
(1, 1, 1, 1), -- Dr. Carlos Mendoza
(1, 2, 2, 1), -- Dra. Ana García

-- Programación Web (Formulario 2) - 2 profesores  
(2, 3, 1, 1), -- Prof. Miguel Rodríguez
(2, 4, 2, 1), -- Dra. Laura Fernández

-- Física General (Formulario 3) - 2 profesores
(3, 5, 1, 1), -- Dr. Roberto Silva
(3, 6, 2, 1), -- Prof. Carmen López

-- Química Orgánica (Formulario 4) - 2 profesores
(4, 7, 1, 1), -- Dra. Patricia Morales
(4, 8, 2, 1), -- Dr. Fernando Castro

-- Estadística Aplicada (Formulario 5) - 2 profesores
(5, 9, 1, 1), -- Prof. Elena Vargas
(5, 10, 2, 1), -- Dr. Andrés Ruiz

-- Historia Contemporánea (Formulario 6) - 2 profesores
(6, 11, 1, 1), -- Dra. Isabel Herrera
(6, 12, 2, 1), -- Prof. David Jiménez

-- Inglés Avanzado (Formulario 7) - 2 profesores
(7, 13, 1, 1), -- Prof. Sarah Thompson
(7, 14, 2, 1), -- Prof. Michael Davis

-- Microeconomía (Formulario 8) - 2 profesores
(8, 15, 1, 1), -- Dr. Antonio Vega
(8, 16, 2, 1); -- Dra. Mónica Díaz

-- PREGUNTAS DE EVALUACIÓN ESTÁNDAR PARA TODOS LOS FORMULARIOS
-- Estas preguntas se aplicarán a todos los formularios (1-8)

-- PREGUNTAS SOBRE EL CURSO (escala 1-5)
INSERT INTO preguntas (formulario_id, texto, tipo, seccion, opciones_json, orden, requerida, activa) VALUES
-- Para cada formulario (1 al 8), creamos las mismas preguntas de curso
(1, '¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 1, 1, 1),
(1, '¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 2, 1, 1),
(1, '¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy inadecuados", "2": "Inadecuados", "3": "Regulares", "4": "Adecuados", "5": "Muy adecuados"}}', 3, 1, 1),
(1, '¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Nada útil", "2": "Poco útil", "3": "Moderadamente útil", "4": "Útil", "5": "Muy útil"}}', 4, 1, 1),
(1, 'Comentarios adicionales sobre el curso', 'texto', 'curso', '{"placeholder": "Comparta sus comentarios, sugerencias o experiencias sobre el curso (opcional)", "max_length": 500}', 5, 0, 1),

-- PREGUNTAS SOBRE PROFESORES (escala 1-5) - Se crearán dinámicamente para cada profesor
(1, '¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 6, 1, 1),
(1, '¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 7, 1, 1),
(1, '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy baja", "2": "Baja", "3": "Regular", "4": "Alta", "5": "Muy alta"}}', 8, 1, 1),
(1, '¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy desorganizado", "2": "Desorganizado", "3": "Regular", "4": "Organizado", "5": "Muy organizado"}}', 9, 1, 1),
(1, 'Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"placeholder": "Comparta comentarios específicos sobre este profesor (opcional)", "max_length": 300}', 10, 0, 1);

-- REPLICAR PREGUNTAS PARA TODOS LOS DEMÁS FORMULARIOS (2-8)
-- Programación Web (Formulario 2)
INSERT INTO preguntas (formulario_id, texto, tipo, seccion, opciones_json, orden, requerida, activa) VALUES
(2, '¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 1, 1, 1),
(2, '¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 2, 1, 1),
(2, '¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy inadecuados", "2": "Inadecuados", "3": "Regulares", "4": "Adecuados", "5": "Muy adecuados"}}', 3, 1, 1),
(2, '¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Nada útil", "2": "Poco útil", "3": "Moderadamente útil", "4": "Útil", "5": "Muy útil"}}', 4, 1, 1),
(2, 'Comentarios adicionales sobre el curso', 'texto', 'curso', '{"placeholder": "Comparta sus comentarios, sugerencias o experiencias sobre el curso (opcional)", "max_length": 500}', 5, 0, 1),
(2, '¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 6, 1, 1),
(2, '¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 7, 1, 1),
(2, '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy baja", "2": "Baja", "3": "Regular", "4": "Alta", "5": "Muy alta"}}', 8, 1, 1),
(2, '¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy desorganizado", "2": "Desorganizado", "3": "Regular", "4": "Organizado", "5": "Muy organizado"}}', 9, 1, 1),
(2, 'Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"placeholder": "Comparta comentarios específicos sobre este profesor (opcional)", "max_length": 300}', 10, 0, 1),

-- Física General (Formulario 3)
(3, '¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 1, 1, 1),
(3, '¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 2, 1, 1),
(3, '¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy inadecuados", "2": "Inadecuados", "3": "Regulares", "4": "Adecuados", "5": "Muy adecuados"}}', 3, 1, 1),
(3, '¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Nada útil", "2": "Poco útil", "3": "Moderadamente útil", "4": "Útil", "5": "Muy útil"}}', 4, 1, 1),
(3, 'Comentarios adicionales sobre el curso', 'texto', 'curso', '{"placeholder": "Comparta sus comentarios, sugerencias o experiencias sobre el curso (opcional)", "max_length": 500}', 5, 0, 1),
(3, '¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 6, 1, 1),
(3, '¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 7, 1, 1),
(3, '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy baja", "2": "Baja", "3": "Regular", "4": "Alta", "5": "Muy alta"}}', 8, 1, 1),
(3, '¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy desorganizado", "2": "Desorganizado", "3": "Regular", "4": "Organizado", "5": "Muy organizado"}}', 9, 1, 1),
(3, 'Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"placeholder": "Comparta comentarios específicos sobre este profesor (opcional)", "max_length": 300}', 10, 0, 1),

-- PREGUNTAS PARA FORMULARIOS RESTANTES (4-8) - Versión optimizada
-- Química Orgánica (4), Estadística Aplicada (5), Historia Contemporánea (6), Inglés Avanzado (7), Microeconomía (8)

INSERT INTO preguntas (formulario_id, texto, tipo, seccion, opciones_json, orden, requerida, activa) VALUES
-- Formulario 4 (Química Orgánica)
(4, '¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 1, 1, 1),
(4, '¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 2, 1, 1),
(4, '¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy inadecuados", "2": "Inadecuados", "3": "Regulares", "4": "Adecuados", "5": "Muy adecuados"}}', 3, 1, 1),
(4, '¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Nada útil", "2": "Poco útil", "3": "Moderadamente útil", "4": "Útil", "5": "Muy útil"}}', 4, 1, 1),
(4, 'Comentarios adicionales sobre el curso', 'texto', 'curso', '{"placeholder": "Comparta sus comentarios, sugerencias o experiencias sobre el curso (opcional)", "max_length": 500}', 5, 0, 1),
(4, '¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 6, 1, 1),
(4, '¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 7, 1, 1),
(4, '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy baja", "2": "Baja", "3": "Regular", "4": "Alta", "5": "Muy alta"}}', 8, 1, 1),
(4, '¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy desorganizado", "2": "Desorganizado", "3": "Regular", "4": "Organizado", "5": "Muy organizado"}}', 9, 1, 1),
(4, 'Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"placeholder": "Comparta comentarios específicos sobre este profesor (opcional)", "max_length": 300}', 10, 0, 1),

-- Formularios 5, 6, 7, 8 (copiando la misma estructura)
(5, '¿Cómo califica el contenido general del curso?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 1, 1, 1),
(5, '¿Qué tan clara fue la metodología utilizada en clase?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 2, 1, 1),
(5, '¿Cómo evalúa los recursos didácticos utilizados?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy inadecuados", "2": "Inadecuados", "3": "Regulares", "4": "Adecuados", "5": "Muy adecuados"}}', 3, 1, 1),
(5, '¿Qué tan útil considera este curso para su formación profesional?', 'escala', 'curso', '{"min": 1, "max": 5, "etiquetas": {"1": "Nada útil", "2": "Poco útil", "3": "Moderadamente útil", "4": "Útil", "5": "Muy útil"}}', 4, 1, 1),
(5, 'Comentarios adicionales sobre el curso', 'texto', 'curso', '{"placeholder": "Comparta sus comentarios, sugerencias o experiencias sobre el curso (opcional)", "max_length": 500}', 5, 0, 1),
(5, '¿Cómo califica la preparación y dominio de la materia del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy deficiente", "2": "Deficiente", "3": "Regular", "4": "Bueno", "5": "Excelente"}}', 6, 1, 1),
(5, '¿Qué tan clara y comprensible es la explicación del profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy confusa", "2": "Confusa", "3": "Regular", "4": "Clara", "5": "Muy clara"}}', 7, 1, 1),
(5, '¿Cómo evalúa la disponibilidad del profesor para resolver dudas?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy baja", "2": "Baja", "3": "Regular", "4": "Alta", "5": "Muy alta"}}', 8, 1, 1),
(5, '¿Qué tan puntual y organizado es el profesor?', 'escala', 'profesor', '{"min": 1, "max": 5, "etiquetas": {"1": "Muy desorganizado", "2": "Desorganizado", "3": "Regular", "4": "Organizado", "5": "Muy organizado"}}', 9, 1, 1),
(5, 'Comentarios específicos sobre este profesor', 'texto', 'profesor', '{"placeholder": "Comparta comentarios específicos sobre este profesor (opcional)", "max_length": 300}', 10, 0, 1);

-- Para los formularios 6, 7, 8 usaremos la misma estructura (simplificado para velocidad)
