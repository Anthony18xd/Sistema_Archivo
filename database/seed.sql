-- ============================================================
-- DATOS INICIALES - SISTEMA DE ARCHIVO MUNICIPAL
-- ============================================================

USE `archivo_municipal`;

-- ------------------------------------------------------------
-- Roles iniciales
-- ------------------------------------------------------------
INSERT INTO `roles` (`nombre`, `descripcion`, `permisos`) VALUES
('Administrador', 'Acceso total al sistema', '{"all": true}'),
('Archivista', 'Gestion de documentos, prestamos y reportes', '{"documentos": ["crear","editar","buscar","ver"], "prestamos": ["crear","devolver","ver"], "reportes": ["ver"], "historial": ["ver"]}'),
('Consulta', 'Solo consulta de documentos e informacion', '{"documentos": ["buscar","ver"], "reportes": ["ver"], "historial": ["ver"]}');

-- ------------------------------------------------------------
-- Usuario administrador por defecto
-- Password: admin123 (hash generado con password_hash)
-- ------------------------------------------------------------
INSERT INTO `usuarios` (`rol_id`, `nombres`, `apellidos`, `dni`, `email`, `username`, `password`, `estado`) VALUES
(1, 'Administrador', 'Sistema', '00000000', 'admin@municipalidad.gob.pe', 'admin', '$2y$10$DxCJ4mfZvS6E7ILqD5ewlenF2M2lyLqfoMI8n8jqhKJJs35HpNp1.', 1);

-- ------------------------------------------------------------
-- Areas de ejemplo
-- ------------------------------------------------------------
INSERT INTO `areas` (`nombre`, `descripcion`) VALUES
('Secretaria General', 'Oficina central de administracion general'),
('Direccion de Administracion y Finanzas', 'Gestion administrativa y financiera'),
('Direccion de Planeamiento y Presupuesto', 'Planificacion estrategica y presupuesto municipal'),
('Direccion de Obras Publicas', 'Infraestructura y obras publicas'),
('Direccion de Desarrollo Social', 'Programas sociales y atencion a la poblacion'),
('Direccion de Seguridad Ciudadana', 'Seguridad y orden publico'),
('Direccion de Salud Publica', 'Programas de salud municipal'),
('Direccion de Educacion', 'Gestion educativa municipal'),
('Direccion de Agricultura', 'Desarrollo agricola y pecuario'),
('Archivo Central', 'Resguardo y custodia de documentos municipales'),
('Atencion al Publico', 'Ventanilla unica de atencion ciudadana'),
('Juridica', 'Asesoria legal municipal'),
('Registro Civil', 'Registro de nacimientos, defunciones y matrimonios'),
('Tesoreria', 'Manejo de fondos y cuentas municipales'),
('Contabilidad', 'Registro contable y financiero');

-- ------------------------------------------------------------
-- Tipos de documento
-- ------------------------------------------------------------
INSERT INTO `tipos_documento` (`nombre`, `descripcion`) VALUES
('Resolucion', 'Resoluciones de alcaldia y directivas'),
('Oficio', 'Oficios de comunicacion interna y externa'),
('Expediente', 'Expedientes administrativos completos'),
('Tomo', 'Tomo con documentos fisicos archivados'),
('Acta', 'Actas de sesiones y reuniones'),
('Informe', 'Informes tecnicos y de gestion'),
('Contrato', 'Contratos y convenios'),
('Solicitud', 'Solicitudes ciudadanas o internas'),
('Memo', 'Memorandums internos'),
('Dictamen', 'Dictamenes tecnicos o juridicos'),
('Pliego', 'Pliegos presupuestales'),
('Orden de Pago', 'Ordenes de pago y documentos financieros'),
('Nota', 'Notas de fifo o internas'),
('Anexo', 'Anexos a documentos principales'),
('Lote', 'Conjunto de documentos relacionados');

-- ------------------------------------------------------------
-- Ambientes del archivo
-- ------------------------------------------------------------
INSERT INTO `ambientes` (`nombre`, `descripcion`) VALUES
('Archivo General', 'Sala principal de archivo fisico municipal'),
('Archivo Reserva', 'Documentos clasificados o de reserva'),
('Archivo Temporal', 'Documentos en proceso de disposicion final');

-- ------------------------------------------------------------
-- Estantes del Archivo General
-- ------------------------------------------------------------
INSERT INTO `estantes` (`ambiente_id`, `codigo`, `nombre`, `descripcion`) VALUES
(1, 'EA', 'Estante A', 'Estante A - Seccion documentos administrativos'),
(1, 'EB', 'Estante B', 'Estante B - Seccion resoluciones'),
(1, 'EC', 'Estante C', 'Estante C - Seccion contratos'),
(1, 'ED', 'Estante D', 'Estante D - Seccion expedientes'),
(1, 'EE', 'Estante E', 'Estante E - Seccion actas e informes'),
(1, 'EF', 'Estante F', 'Estante F - Seccion documentos varios');

-- ------------------------------------------------------------
-- Niveles por estante (5 niveles por estante)
-- ------------------------------------------------------------
INSERT INTO `niveles` (`estante_id`, `numero`, `descripcion`) VALUES
(1, 1, 'Nivel 1 - Base'), (1, 2, 'Nivel 2'), (1, 3, 'Nivel 3'), (1, 4, 'Nivel 4'), (1, 5, 'Nivel 5 - Superior'),
(2, 1, 'Nivel 1 - Base'), (2, 2, 'Nivel 2'), (2, 3, 'Nivel 3'), (2, 4, 'Nivel 4'), (2, 5, 'Nivel 5 - Superior'),
(3, 1, 'Nivel 1 - Base'), (3, 2, 'Nivel 2'), (3, 3, 'Nivel 3'), (3, 4, 'Nivel 4'), (3, 5, 'Nivel 5 - Superior'),
(4, 1, 'Nivel 1 - Base'), (4, 2, 'Nivel 2'), (4, 3, 'Nivel 3'), (4, 4, 'Nivel 4'), (4, 5, 'Nivel 5 - Superior'),
(5, 1, 'Nivel 1 - Base'), (5, 2, 'Nivel 2'), (5, 3, 'Nivel 3'), (5, 4, 'Nivel 4'), (5, 5, 'Nivel 5 - Superior'),
(6, 1, 'Nivel 1 - Base'), (6, 2, 'Nivel 2'), (6, 3, 'Nivel 3'), (6, 4, 'Nivel 4'), (6, 5, 'Nivel 5 - Superior');

-- ------------------------------------------------------------
-- Cajas ejemplo (3 cajas por nivel, primeros 5 estantes)
-- ------------------------------------------------------------
INSERT INTO `cajas` (`nivel_id`, `numero`, `codigo`, `descripcion`, `capacidad`) VALUES
(1, 1, 'C-01', 'Caja 1', 50), (1, 2, 'C-02', 'Caja 2', 50), (1, 3, 'C-03', 'Caja 3', 50),
(2, 1, 'C-01', 'Caja 1', 50), (2, 2, 'C-02', 'Caja 2', 50), (2, 3, 'C-03', 'Caja 3', 50),
(3, 1, 'C-01', 'Caja 1', 50), (3, 2, 'C-02', 'Caja 2', 50), (3, 3, 'C-03', 'Caja 3', 50),
(4, 1, 'C-01', 'Caja 1', 50), (4, 2, 'C-02', 'Caja 2', 50), (4, 3, 'C-03', 'Caja 3', 50),
(5, 1, 'C-01', 'Caja 1', 50), (5, 2, 'C-02', 'Caja 2', 50), (5, 3, 'C-03', 'Caja 3', 50),
(6, 1, 'C-01', 'Caja 1', 50), (6, 2, 'C-02', 'Caja 2', 50), (6, 3, 'C-03', 'Caja 3', 50),
(7, 1, 'C-01', 'Caja 1', 50), (7, 2, 'C-02', 'Caja 2', 50), (7, 3, 'C-03', 'Caja 3', 50),
(8, 1, 'C-01', 'Caja 1', 50), (8, 2, 'C-02', 'Caja 2', 50), (8, 3, 'C-03', 'Caja 3', 50),
(9, 1, 'C-01', 'Caja 1', 50), (9, 2, 'C-02', 'Caja 2', 50), (9, 3, 'C-03', 'Caja 3', 50),
(10, 1, 'C-01', 'Caja 1', 50), (10, 2, 'C-02', 'Caja 2', 50), (10, 3, 'C-03', 'Caja 3', 50),
(11, 1, 'C-01', 'Caja 1', 50), (11, 2, 'C-02', 'Caja 2', 50), (11, 3, 'C-03', 'Caja 3', 50),
(12, 1, 'C-01', 'Caja 1', 50), (12, 2, 'C-02', 'Caja 2', 50), (12, 3, 'C-03', 'Caja 3', 50),
(13, 1, 'C-01', 'Caja 1', 50), (13, 2, 'C-02', 'Caja 2', 50), (13, 3, 'C-03', 'Caja 3', 50),
(14, 1, 'C-01', 'Caja 1', 50), (14, 2, 'C-02', 'Caja 2', 50), (14, 3, 'C-03', 'Caja 3', 50),
(15, 1, 'C-01', 'Caja 1', 50), (15, 2, 'C-02', 'Caja 2', 50), (15, 3, 'C-03', 'Caja 3', 50),
(16, 1, 'C-01', 'Caja 1', 50), (16, 2, 'C-02', 'Caja 2', 50), (16, 3, 'C-03', 'Caja 3', 50),
(17, 1, 'C-01', 'Caja 1', 50), (17, 2, 'C-02', 'Caja 2', 50), (17, 3, 'C-03', 'Caja 3', 50),
(18, 1, 'C-01', 'Caja 1', 50), (18, 2, 'C-02', 'Caja 2', 50), (18, 3, 'C-03', 'Caja 3', 50),
(19, 1, 'C-01', 'Caja 1', 50), (19, 2, 'C-02', 'Caja 2', 50), (19, 3, 'C-03', 'Caja 3', 50),
(20, 1, 'C-01', 'Caja 1', 50), (20, 2, 'C-02', 'Caja 2', 50), (20, 3, 'C-03', 'Caja 3', 50),
(21, 1, 'C-01', 'Caja 1', 50), (21, 2, 'C-02', 'Caja 2', 50), (21, 3, 'C-03', 'Caja 3', 50),
(22, 1, 'C-01', 'Caja 1', 50), (22, 2, 'C-02', 'Caja 2', 50), (22, 3, 'C-03', 'Caja 3', 50),
(23, 1, 'C-01', 'Caja 1', 50), (23, 2, 'C-02', 'Caja 2', 50), (23, 3, 'C-03', 'Caja 3', 50),
(24, 1, 'C-01', 'Caja 1', 50), (24, 2, 'C-02', 'Caja 2', 50), (24, 3, 'C-03', 'Caja 3', 50),
(25, 1, 'C-01', 'Caja 1', 50), (25, 2, 'C-02', 'Caja 2', 50), (25, 3, 'C-03', 'Caja 3', 50),
(26, 1, 'C-01', 'Caja 1', 50), (26, 2, 'C-02', 'Caja 2', 50), (26, 3, 'C-03', 'Caja 3', 50),
(27, 1, 'C-01', 'Caja 1', 50), (27, 2, 'C-02', 'Caja 2', 50), (27, 3, 'C-03', 'Caja 3', 50),
(28, 1, 'C-01', 'Caja 1', 50), (28, 2, 'C-02', 'Caja 2', 50), (28, 3, 'C-03', 'Caja 3', 50),
(29, 1, 'C-01', 'Caja 1', 50), (29, 2, 'C-02', 'Caja 2', 50), (29, 3, 'C-03', 'Caja 3', 50),
(30, 1, 'C-01', 'Caja 1', 50), (30, 2, 'C-02', 'Caja 2', 50), (30, 3, 'C-03', 'Caja 3', 50);

-- ------------------------------------------------------------
-- Configuracion del sistema
-- ------------------------------------------------------------
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('nombre_municipalidad', 'Municipalidad Provincial', 'Nombre de la municipalidad'),
('nombre_sistema', 'Sistema Integral de Gestion y Control de Archivo Fisico Municipal', 'Nombre del sistema'),
('version_sistema', '1.0.0', 'Version actual del sistema'),
('dias_prestamo_default', '15', 'Dias por defecto para devolucion de prestamos'),
('max_dias_prestamo', '30', 'Maximo de dias para un prestamo'),
('telefono_archivo', '', 'Telefono de contacto del archivo'),
('email_archivo', '', 'Email de contacto del archivo'),
('direccion_archivo', '', 'Direccion del archivo municipal');
