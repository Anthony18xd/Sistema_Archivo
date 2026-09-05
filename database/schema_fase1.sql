-- ============================================================
-- SISTEMA DE ARCHIVO MUNICIPAL - FASE 1
-- Motor de Busqueda y Registro de Prestamos
-- Base de datos: MySQL / MariaDB
-- Collation: utf8mb4_unicode_ci (insensible a tildes/mayusculas)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Base de datos
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `archivo_municipal`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `archivo_municipal`;

-- =============================================================
-- TABLAS DEL SISTEMA EXISTENTE (se mantienen)
-- Roles, usuarios, areas, tipos_documento, ambientes, estantes,
-- niveles, cajas, historial_documento, auditoria, configuracion
-- =============================================================
-- (Se asume que schema.sql original ya fue ejecutado)

-- =============================================================
-- FASE 1: NUEVAS TABLAS
-- =============================================================

-- ------------------------------------------------------------
-- Tabla: tomos
-- Unidad principal de inventario. Cada tomo representa un
-- expediente fisico (carpeta, legajo, tomo encuadernado).
-- Campos de ubicacion son OPCIONALES (Fase 2).
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tomos`;
CREATE TABLE `tomos` (
  `id_tomo` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_tomo` VARCHAR(100) NOT NULL COMMENT 'Codigo identificador del tomo (ej: A-001, CARTAS-012)',
  `anio` YEAR DEFAULT NULL COMMENT 'Ano de creacion o gestion del tomo',
  `area` VARCHAR(200) DEFAULT NULL COMMENT 'Area o dependencia propietaria',
  `tipo_documento` VARCHAR(150) DEFAULT NULL COMMENT 'Tipo de documento (Oficio, Resolucion, Carta, etc.)',
  `cantidad_folios` INT DEFAULT NULL COMMENT 'Cantidad total de folios del tomo',
  `ubicacion_estado` ENUM('pendiente_asignacion', 'asignado', 'prestado', 'en_revision') NOT NULL DEFAULT 'pendiente_asignacion' COMMENT 'Estado de ubicacion fisica',
  `caja_id` INT UNSIGNED DEFAULT NULL COMMENT 'Ubicacion fisica: caja donde se guarda el tomo (Fase 2)',
  `fuente_importacion` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del archivo Excel de origen (trazabilidad ETL)',
  `usuario_registro_id` INT UNSIGNED DEFAULT NULL COMMENT 'Usuario que registro el tomo',
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo', 'archivado') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tomo`),
  UNIQUE KEY `uq_tomos_codigo` (`codigo_tomo`),
  KEY `idx_tomos_caja` (`caja_id`),
  KEY `idx_tomos_anio` (`anio`),
  KEY `idx_tomos_area` (`area`),
  KEY `idx_tomos_tipo` (`tipo_documento`),
  KEY `idx_tomos_estado_ubicacion` (`ubicacion_estado`),
  KEY `idx_tomos_estado` (`estado`),
  KEY `idx_tomos_fuente` (`fuente_importacion`),
  FULLTEXT KEY `ft_tomos_busqueda` (`codigo_tomo`, `area`, `tipo_documento`),
  CONSTRAINT `fk_tomos_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: documentos
-- Documentos individuales dentro de un tomo. Cada fila de
-- Excel que no es cabecera se registra aqui.
-- folios_texto: soporta valores irregulares (1-140, INDETERMINADO, SIN FOLIAR)
-- expediente_texto: cadena con uno o varios expedientes (1036|1342)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `documentos_fase1`;
CREATE TABLE `documentos_fase1` (
  `id_documento` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tomo` INT UNSIGNED NOT NULL COMMENT 'Referencia al tomo padre',
  `anio` YEAR DEFAULT NULL COMMENT 'Ano del documento',
  `solicitante` VARCHAR(250) DEFAULT NULL COMMENT 'Nombre del solicitante o titular',
  `folios_texto` VARCHAR(255) DEFAULT NULL COMMENT 'Folios como texto (1-140, INDETERMINADO, SIN FOLIAR)',
  `expediente_texto` TEXT DEFAULT NULL COMMENT 'Cadena de expedientes separados por pipe (1036|1342)',
  `asunto` TEXT DEFAULT NULL COMMENT 'Descripcion o asunto del documento',
  `hoja_origen` VARCHAR(100) DEFAULT NULL COMMENT 'Nombre de la pestaña Excel de origen (trazabilidad ETL)',
  `fila_origen` INT DEFAULT NULL COMMENT 'Numero de fila en el Excel de origen (trazabilidad ETL)',
  `estado` ENUM('activo', 'inactivo', 'prestado') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_documento`),
  KEY `fk_doc_fase1_tomo` (`id_tomo`),
  UNIQUE KEY `uq_doc_fase1_origen` (`id_tomo`, `hoja_origen`, `fila_origen`),
  KEY `idx_doc_fase1_anio` (`anio`),
  KEY `idx_doc_fase1_solicitante` (`solicitante`),
  KEY `idx_doc_fase1_estado` (`estado`),
  FULLTEXT KEY `ft_doc_fase1_busqueda` (`solicitante`, `asunto`, `expediente_texto`),
  CONSTRAINT `fk_doc_fase1_tomo` FOREIGN KEY (`id_tomo`) REFERENCES `tomos` (`id_tomo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: documento_expedientes
-- Tabla puente para soportar multiples expedientes por
-- documento. Se descompone la cadena "1036|1342" en filas.
-- Permite busqueda exacta por numero de expediente.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `documento_expedientes`;
CREATE TABLE `documento_expedientes` (
  `id_expediente` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_documento` INT UNSIGNED NOT NULL,
  `numero_expediente_unificado` VARCHAR(50) NOT NULL COMMENT 'Numero de expediente normalizado',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_expediente`),
  KEY `fk_expediente_documento` (`id_documento`),
  KEY `idx_expediente_numero` (`numero_expediente_unificado`),
  CONSTRAINT `fk_expediente_documento` FOREIGN KEY (`id_documento`) REFERENCES `documentos_fase1` (`id_documento`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: prestamos (Fase 1 simplificada)
-- Prestamos vinculados al TOMO (unidad principal).
-- La devolucion marca automaticamente el estado del tomo.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `prestamos_fase1`;
CREATE TABLE `prestamos_fase1` (
  `id_prestamo` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tomo` INT UNSIGNED NOT NULL,
  `solicitante_prestamo` VARCHAR(250) NOT NULL COMMENT 'Persona que retira el tomo',
  `area_destino` VARCHAR(200) DEFAULT NULL COMMENT 'Area a la que se lleva el tomo',
  `fecha_salida` DATE NOT NULL,
  `fecha_devolucion` DATE DEFAULT NULL COMMENT 'Fecha real de devolucion',
  `estado` ENUM('activo', 'devuelto', 'vencido') NOT NULL DEFAULT 'activo',
  `usuario_registro_id` INT UNSIGNED DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_prestamo`),
  KEY `fk_prestamo_fase1_tomo` (`id_tomo`),
  KEY `idx_prestamo_fase1_estado` (`estado`),
  KEY `idx_prestamo_fase1_fecha_salida` (`fecha_salida`),
  KEY `idx_prestamo_fase1_solicitante` (`solicitante_prestamo`),
  CONSTRAINT `fk_prestamo_fase1_tomo` FOREIGN KEY (`id_tomo`) REFERENCES `tomos` (`id_tomo`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: ubicaciones (Fase 2 - pasiva)
-- Estructura lista para cuando la municipalidad inventarie
-- fisicamente sus anaqueles. No se usa en Fase 1.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ubicaciones_fase2`;
CREATE TABLE `ubicaciones_fase2` (
  `id_ubicacion` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambiente` VARCHAR(100) DEFAULT NULL COMMENT 'Sala o deposito',
  `estante` VARCHAR(50) DEFAULT NULL COMMENT 'Codigo del estante',
  `nivel` VARCHAR(20) DEFAULT NULL COMMENT 'Nivel/Nivel del estante',
  `caja` VARCHAR(50) DEFAULT NULL COMMENT 'Numero o codigo de caja',
  `capacidad` INT DEFAULT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: log_importacion
-- Registro de cada ejecucion del ETL para auditoria.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `log_importacion`;
CREATE TABLE `log_importacion` (
  `id_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `archivo` VARCHAR(255) NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `total_tomos` INT NOT NULL DEFAULT 0,
  `total_documentos` INT NOT NULL DEFAULT 0,
  `total_expedientes` INT NOT NULL DEFAULT 0,
  `errores` INT NOT NULL DEFAULT 0,
  `detalle_errores` TEXT DEFAULT NULL,
  `duracion_segundos` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_importacion_fecha` (`created_at`),
  KEY `idx_log_importacion_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
