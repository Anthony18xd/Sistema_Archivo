-- ============================================================
-- SISTEMA INTEGRAL DE GESTION Y CONTROL DE ARCHIVO FISICO MUNICIPAL
-- Base de datos: MySQL / MariaDB
-- Version: 1.0
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

-- ------------------------------------------------------------
-- Tabla: roles
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `permisos` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rol_id` INT UNSIGNED NOT NULL,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_username` (`username`),
  UNIQUE KEY `uq_usuarios_dni` (`dni`),
  KEY `fk_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: areas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_areas_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: tipos_documento
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tipos_documento`;
CREATE TABLE `tipos_documento` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipos_documento_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: ambientes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ambientes`;
CREATE TABLE `ambientes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ambientes_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: estantes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `estantes`;
CREATE TABLE `estantes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambiente_id` INT UNSIGNED NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estantes_codigo` (`codigo`),
  KEY `fk_estantes_ambiente` (`ambiente_id`),
  CONSTRAINT `fk_estantes_ambiente` FOREIGN KEY (`ambiente_id`) REFERENCES `ambientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: niveles
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `niveles`;
CREATE TABLE `niveles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estante_id` INT UNSIGNED NOT NULL,
  `numero` INT NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_niveles_estante_numero` (`estante_id`, `numero`),
  KEY `fk_niveles_estante` (`estante_id`),
  CONSTRAINT `fk_niveles_estante` FOREIGN KEY (`estante_id`) REFERENCES `estantes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: cajas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cajas`;
CREATE TABLE `cajas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nivel_id` INT UNSIGNED NOT NULL,
  `numero` INT NOT NULL,
  `codigo` VARCHAR(50) DEFAULT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `capacidad` INT DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cajas_nivel_numero` (`nivel_id`, `numero`),
  KEY `fk_cajas_nivel` (`nivel_id`),
  CONSTRAINT `fk_cajas_nivel` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: documentos
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `documentos`;
CREATE TABLE `documentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(100) NOT NULL,
  `anio` YEAR NOT NULL,
  `area_emisora_id` INT UNSIGNED DEFAULT NULL,
  `area_custodio_id` INT UNSIGNED DEFAULT NULL,
  `tipo_documento_id` INT UNSIGNED DEFAULT NULL,
  `caja_id` INT UNSIGNED DEFAULT NULL,
  `num_folios` INT DEFAULT NULL,
  `asunto` TEXT NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('disponible','prestado','en_revision','inactivo') NOT NULL DEFAULT 'disponible',
  `fecha_registro` DATE NOT NULL,
  `usuario_registro_id` INT UNSIGNED NOT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_documentos_codigo` (`codigo`),
  KEY `fk_doc_area_emisora` (`area_emisora_id`),
  KEY `fk_doc_area_custodio` (`area_custodio_id`),
  KEY `fk_doc_tipo` (`tipo_documento_id`),
  KEY `fk_doc_caja` (`caja_id`),
  KEY `fk_doc_usuario_registro` (`usuario_registro_id`),
  KEY `idx_doc_anio` (`anio`),
  KEY `idx_doc_estado` (`estado`),
  KEY `idx_doc_fecha_registro` (`fecha_registro`),
  KEY `idx_doc_deleted` (`deleted_at`),
  FULLTEXT KEY `ft_doc_asunto` (`asunto`),
  CONSTRAINT `fk_doc_area_emisora` FOREIGN KEY (`area_emisora_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_area_custodio` FOREIGN KEY (`area_custodio_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_tipo` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_usuario_registro` FOREIGN KEY (`usuario_registro_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: prestamos
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `prestamos`;
CREATE TABLE `prestamos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `documento_id` INT UNSIGNED NOT NULL,
  `solicitante_nombre` VARCHAR(200) NOT NULL,
  `solicitante_dni` VARCHAR(20) DEFAULT NULL,
  `solicitante_area` VARCHAR(150) DEFAULT NULL,
  `motivo` TEXT DEFAULT NULL,
  `fecha_salida` DATE NOT NULL,
  `hora_salida` TIME NOT NULL,
  `fecha_devolucion_estimada` DATE NOT NULL,
  `fecha_devolucion_real` DATE DEFAULT NULL,
  `hora_devolucion_real` TIME DEFAULT NULL,
  `estado` ENUM('activo','devuelto','vencido') NOT NULL DEFAULT 'activo',
  `usuario_prestamo_id` INT UNSIGNED NOT NULL,
  `usuario_devolucion_id` INT UNSIGNED DEFAULT NULL,
  `estado_documento_salida` VARCHAR(100) DEFAULT NULL,
  `estado_documento_entrada` VARCHAR(100) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_prestamo_documento` (`documento_id`),
  KEY `fk_prestamo_usuario_salida` (`usuario_prestamo_id`),
  KEY `fk_prestamo_usuario_devolucion` (`usuario_devolucion_id`),
  KEY `idx_prestamo_estado` (`estado`),
  KEY `idx_prestamo_fecha_salida` (`fecha_salida`),
  KEY `idx_prestamo_fecha_devolucion` (`fecha_devolucion_estimada`),
  CONSTRAINT `fk_prestamo_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prestamo_usuario_salida` FOREIGN KEY (`usuario_prestamo_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prestamo_usuario_devolucion` FOREIGN KEY (`usuario_devolucion_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: historial_documento
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `historial_documento`;
CREATE TABLE `historial_documento` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `documento_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `valores_anteriores` JSON DEFAULT NULL,
  `valores_nuevos` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_historial_documento` (`documento_id`),
  KEY `fk_historial_usuario` (`usuario_id`),
  KEY `idx_historial_fecha` (`created_at`),
  CONSTRAINT `fk_historial_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: auditoria
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `tabla` VARCHAR(100) DEFAULT NULL,
  `registro_id` INT UNSIGNED DEFAULT NULL,
  `detalle` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_auditoria_usuario` (`usuario_id`),
  KEY `idx_auditoria_tabla_registro` (`tabla`, `registro_id`),
  KEY `idx_auditoria_fecha` (`created_at`),
  KEY `idx_auditoria_accion` (`accion`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: configuracion
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(100) NOT NULL,
  `valor` TEXT DEFAULT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configuracion_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
