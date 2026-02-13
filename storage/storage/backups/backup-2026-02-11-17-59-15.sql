-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: sistem_sgcc
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alertas`
--

DROP TABLE IF EXISTS `alertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alertas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_cobro_id` bigint(20) unsigned DEFAULT NULL,
  `nivel` varchar(20) DEFAULT NULL COMMENT 'INFO, WARNING, ERROR, CRITICAL',
  `tipo_alerta` varchar(50) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_destino_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alertas_usuario_destino_id_leida_index` (`usuario_destino_id`,`leida`),
  KEY `alertas_cuenta_cobro_id_index` (`cuenta_cobro_id`),
  CONSTRAINT `alertas_cuenta_cobro_id_foreign` FOREIGN KEY (`cuenta_cobro_id`) REFERENCES `cuentas_cobro` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertas_usuario_destino_id_foreign` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertas`
--

LOCK TABLES `alertas` WRITE;
/*!40000 ALTER TABLE `alertas` DISABLE KEYS */;
/*!40000 ALTER TABLE `alertas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditorias`
--

DROP TABLE IF EXISTS `auditorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditorias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) unsigned DEFAULT NULL,
  `tabla_afectada` varchar(60) NOT NULL,
  `registro_id` bigint(20) unsigned DEFAULT NULL,
  `accion` varchar(20) DEFAULT NULL COMMENT 'INSERT, UPDATE, DELETE',
  `payload_anterior` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_anterior`)),
  `payload_nuevo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_nuevo`)),
  `ip_origen` varchar(45) DEFAULT NULL,
  `user_agent` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `auditorias_tabla_afectada_index` (`tabla_afectada`),
  KEY `auditorias_created_at_index` (`created_at`),
  KEY `auditorias_usuario_id_index` (`usuario_id`),
  CONSTRAINT `auditorias_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditorias`
--

LOCK TABLES `auditorias` WRITE;
/*!40000 ALTER TABLE `auditorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `auditorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bloques_workflow`
--

DROP TABLE IF EXISTS `bloques_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bloques_workflow` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `orden` smallint(6) NOT NULL COMMENT 'Orden secuencial: 1, 2, 3, 4, 5',
  `sla_horas` int(11) DEFAULT NULL,
  `requiere_aprobacion` tinyint(1) NOT NULL DEFAULT 0,
  `roles_permitidos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roles_permitidos`)),
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bloques_workflow_codigo_unique` (`codigo`),
  UNIQUE KEY `bloques_workflow_orden_unique` (`orden`),
  KEY `bloques_workflow_orden_index` (`orden`),
  KEY `bloques_workflow_codigo_index` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bloques_workflow`
--

LOCK TABLES `bloques_workflow` WRITE;
/*!40000 ALTER TABLE `bloques_workflow` DISABLE KEYS */;
INSERT INTO `bloques_workflow` VALUES (1,'ESTADO TRAS PRIMERA REVISIÓN (SERGIO / CONSUELO)','REV1',1,NULL,0,NULL,'Primera fase de revisión',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(2,'ENVIADA A INGRESO MERCANCIA SAP','SAP',2,NULL,0,NULL,'Ingreso en sistema SAP',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,'EN FACTURACIÓN','FAC',3,NULL,0,NULL,'Proceso de facturación',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(4,'FIRMA SECRETARIO','FIR',4,NULL,0,NULL,'Firma de secretaría',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(5,'RADICADA EN HACIENDA','HAC',5,NULL,0,NULL,'Radicación final',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(6,'FINALIZADA','FIN',6,NULL,0,NULL,'Cuenta completada',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `bloques_workflow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conceptos`
--

DROP TABLE IF EXISTS `conceptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conceptos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conceptos_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conceptos`
--

LOCK TABLES `conceptos` WRITE;
/*!40000 ALTER TABLE `conceptos` DISABLE KEYS */;
INSERT INTO `conceptos` VALUES (1,'APOYO A LA GESTIÓN',NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `conceptos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuraciones`
--

DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuraciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo_dato` varchar(20) DEFAULT NULL COMMENT 'STRING, INT, BOOL, JSON',
  `descripcion` text DEFAULT NULL,
  `modificado_por_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `configuraciones_clave_unique` (`clave`),
  KEY `configuraciones_modificado_por_id_foreign` (`modificado_por_id`),
  CONSTRAINT `configuraciones_modificado_por_id_foreign` FOREIGN KEY (`modificado_por_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuraciones`
--

LOCK TABLES `configuraciones` WRITE;
/*!40000 ALTER TABLE `configuraciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `configuraciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratista_seguridad_social`
--

DROP TABLE IF EXISTS `contratista_seguridad_social`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratista_seguridad_social` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contratista_id` bigint(20) unsigned NOT NULL,
  `entidad_salud_id` bigint(20) unsigned DEFAULT NULL,
  `entidad_pension_id` bigint(20) unsigned DEFAULT NULL,
  `entidad_arl_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `es_vigente` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contratista_seguridad_social_entidad_salud_id_foreign` (`entidad_salud_id`),
  KEY `contratista_seguridad_social_entidad_pension_id_foreign` (`entidad_pension_id`),
  KEY `contratista_seguridad_social_entidad_arl_id_foreign` (`entidad_arl_id`),
  KEY `contratista_seguridad_social_contratista_id_index` (`contratista_id`),
  KEY `contratista_seguridad_social_es_vigente_index` (`es_vigente`),
  CONSTRAINT `contratista_seguridad_social_contratista_id_foreign` FOREIGN KEY (`contratista_id`) REFERENCES `contratistas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contratista_seguridad_social_entidad_arl_id_foreign` FOREIGN KEY (`entidad_arl_id`) REFERENCES `entidades_seguridad_social` (`id`),
  CONSTRAINT `contratista_seguridad_social_entidad_pension_id_foreign` FOREIGN KEY (`entidad_pension_id`) REFERENCES `entidades_seguridad_social` (`id`),
  CONSTRAINT `contratista_seguridad_social_entidad_salud_id_foreign` FOREIGN KEY (`entidad_salud_id`) REFERENCES `entidades_seguridad_social` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratista_seguridad_social`
--

LOCK TABLES `contratista_seguridad_social` WRITE;
/*!40000 ALTER TABLE `contratista_seguridad_social` DISABLE KEYS */;
/*!40000 ALTER TABLE `contratista_seguridad_social` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratistas`
--

DROP TABLE IF EXISTS `contratistas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratistas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(150) NOT NULL COMMENT 'CONTRATISTA del Excel (col 2)',
  `nit` varchar(20) NOT NULL COMMENT 'CEDULA del Excel (col 3)',
  `tipo_persona` enum('NATURAL','JURIDICA') NOT NULL DEFAULT 'NATURAL',
  `representante_legal` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion_fisica` text DEFAULT NULL,
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contratistas_nit_index` (`nit`),
  KEY `contratistas_razon_social_index` (`razon_social`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratistas`
--

LOCK TABLES `contratistas` WRITE;
/*!40000 ALTER TABLE `contratistas` DISABLE KEYS */;
INSERT INTO `contratistas` VALUES (1,'CONSTRUCCIONES','900123456-1','JURIDICA',NULL,NULL,NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 18:39:54'),(2,'TECNOLOGÍA Y DESARROLLO LTDA','800987654-2','JURIDICA',NULL,NULL,NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,'MARIA FERNANDA RODRIGUEZ','1020304050','NATURAL',NULL,NULL,NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(4,'CARLOS ANDRES GOMEZ','50607080','NATURAL',NULL,NULL,NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(5,'SERVICIOS INTEGRALES DE SALUD','901222333-0','JURIDICA',NULL,NULL,NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `contratistas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratos`
--

DROP TABLE IF EXISTS `contratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_proceso` varchar(50) DEFAULT NULL,
  `numero_contrato` varchar(50) NOT NULL COMMENT 'NUMERO DE CONTRATO del Excel (col 1)',
  `modalidad_id` bigint(20) unsigned DEFAULT NULL,
  `contratista_id` bigint(20) unsigned NOT NULL,
  `supervisor_id` bigint(20) unsigned DEFAULT NULL,
  `objeto` text DEFAULT NULL,
  `monto_total` decimal(19,2) NOT NULL,
  `planta_id` bigint(20) unsigned DEFAULT NULL,
  `concepto_id` bigint(20) unsigned DEFAULT NULL,
  `cdp_codigo` varchar(50) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL COMMENT 'FECHA DE INICIO del Excel (col 7)',
  `fecha_fin` date DEFAULT NULL COMMENT 'FECHA DE TERMINACIÓN del Excel (col 8)',
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contratos_numero_contrato_unique` (`numero_contrato`),
  KEY `contratos_modalidad_id_foreign` (`modalidad_id`),
  KEY `contratos_supervisor_id_foreign` (`supervisor_id`),
  KEY `contratos_planta_id_foreign` (`planta_id`),
  KEY `contratos_concepto_id_foreign` (`concepto_id`),
  KEY `contratos_numero_proceso_index` (`numero_proceso`),
  KEY `contratos_contratista_id_index` (`contratista_id`),
  CONSTRAINT `contratos_concepto_id_foreign` FOREIGN KEY (`concepto_id`) REFERENCES `conceptos` (`id`),
  CONSTRAINT `contratos_contratista_id_foreign` FOREIGN KEY (`contratista_id`) REFERENCES `contratistas` (`id`),
  CONSTRAINT `contratos_modalidad_id_foreign` FOREIGN KEY (`modalidad_id`) REFERENCES `modalidades` (`id`),
  CONSTRAINT `contratos_planta_id_foreign` FOREIGN KEY (`planta_id`) REFERENCES `plantas` (`id`),
  CONSTRAINT `contratos_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratos`
--

LOCK TABLES `contratos` WRITE;
/*!40000 ALTER TABLE `contratos` DISABLE KEYS */;
INSERT INTO `contratos` VALUES (1,NULL,'CONT-2026-001',1,1,4,NULL,6331664.00,1,1,NULL,'2026-01-11','2027-02-11',1,'2026-02-11 17:42:57','2026-02-11 18:39:54'),(2,NULL,'CONT-2026-002',1,2,2,NULL,6525020.00,1,1,NULL,'2025-12-11','2027-02-11',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,NULL,'CONT-2026-003',1,3,1,NULL,8226911.00,1,1,NULL,'2025-11-11','2027-02-11',1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `contratos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_cobro`
--

DROP TABLE IF EXISTS `cuentas_cobro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_cobro` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contrato_id` bigint(20) unsigned NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL COMMENT 'NUMERO DE CUENTA del Excel (col 10)',
  `valor_cobro` decimal(19,2) NOT NULL,
  `fecha_radicacion` datetime DEFAULT NULL COMMENT 'FECHA DE RADICACIÓN del Excel (col 19)',
  `numero_pagos_totales` int(11) DEFAULT NULL COMMENT 'NUMERO DE PAGOS TOTALES del Excel (col 11)',
  `numero_facturas_radicadas` int(11) NOT NULL DEFAULT 0 COMMENT 'N° FACTURAS RADICADAS del Excel (col 12)',
  `porcentaje_cuentas` decimal(5,2) DEFAULT NULL COMMENT 'PORCENTAJE DE CUENTAS del Excel (col 13)',
  `radicado_por` varchar(100) DEFAULT NULL COMMENT 'RADICADO POR del Excel (col 18)',
  `bloque_actual_id` bigint(20) unsigned NOT NULL,
  `estado_actual_id` bigint(20) unsigned NOT NULL,
  `finalizada` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE cuando completa bloque 5',
  `responsable_actual_id` bigint(20) unsigned DEFAULT NULL,
  `observaciones` text DEFAULT NULL COMMENT 'OBSERVACIONES del Excel (col 20)',
  `ultima_factura_hacienda` varchar(50) DEFAULT NULL,
  `fecha_radicacion_hacienda` datetime DEFAULT NULL,
  `observacion_hacienda` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cuentas_cobro_estado_actual_id_foreign` (`estado_actual_id`),
  KEY `cuentas_cobro_responsable_actual_id_foreign` (`responsable_actual_id`),
  KEY `cuentas_cobro_numero_cuenta_index` (`numero_cuenta`),
  KEY `cuentas_cobro_contrato_id_index` (`contrato_id`),
  KEY `cuentas_cobro_bloque_actual_id_estado_actual_id_index` (`bloque_actual_id`,`estado_actual_id`),
  KEY `cuentas_cobro_finalizada_index` (`finalizada`),
  CONSTRAINT `cuentas_cobro_bloque_actual_id_foreign` FOREIGN KEY (`bloque_actual_id`) REFERENCES `bloques_workflow` (`id`),
  CONSTRAINT `cuentas_cobro_contrato_id_foreign` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cuentas_cobro_estado_actual_id_foreign` FOREIGN KEY (`estado_actual_id`) REFERENCES `estados_workflow` (`id`),
  CONSTRAINT `cuentas_cobro_responsable_actual_id_foreign` FOREIGN KEY (`responsable_actual_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_cobro`
--

LOCK TABLES `cuentas_cobro` WRITE;
/*!40000 ALTER TABLE `cuentas_cobro` DISABLE KEYS */;
INSERT INTO `cuentas_cobro` VALUES (1,1,'2',527638.67,'2026-01-31 00:00:00',12,2,600.00,'admin',5,15,0,1,'Cuenta de prueba 1','2',NULL,'N/A','2026-02-11 17:42:57','2026-02-11 21:59:44'),(2,2,'1',543751.67,'2026-01-28 12:42:57',12,0,0.00,'admin',4,12,0,3,'Cuenta de prueba 2',NULL,NULL,NULL,'2026-02-11 17:42:57','2026-02-11 22:33:44'),(3,3,'2',685575.92,'2026-01-29 12:42:57',12,2,16.67,'admin',4,12,0,3,'Cuenta de prueba 3','1',NULL,NULL,'2026-02-11 17:42:57','2026-02-11 22:33:25');
/*!40000 ALTER TABLE `cuentas_cobro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contrato_id` bigint(20) unsigned DEFAULT NULL,
  `tipo_documento_id` bigint(20) unsigned DEFAULT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `url_almacenamiento` text DEFAULT NULL,
  `estado_validacion` varchar(50) NOT NULL DEFAULT 'PENDIENTE',
  `subido_por_id` bigint(20) unsigned DEFAULT NULL,
  `version` smallint(6) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documentos_subido_por_id_foreign` (`subido_por_id`),
  KEY `documentos_contrato_id_index` (`contrato_id`),
  KEY `documentos_tipo_documento_id_index` (`tipo_documento_id`),
  CONSTRAINT `documentos_contrato_id_foreign` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_subido_por_id_foreign` FOREIGN KEY (`subido_por_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `documentos_tipo_documento_id_foreign` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entidades_seguridad_social`
--

DROP TABLE IF EXISTS `entidades_seguridad_social`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entidades_seguridad_social` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('SALUD','PENSION','ARL') NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `es_activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entidades_seguridad_social_nombre_unique` (`nombre`),
  KEY `entidades_seguridad_social_tipo_nombre_index` (`tipo`,`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entidades_seguridad_social`
--

LOCK TABLES `entidades_seguridad_social` WRITE;
/*!40000 ALTER TABLE `entidades_seguridad_social` DISABLE KEYS */;
/*!40000 ALTER TABLE `entidades_seguridad_social` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado_bloque_cuenta`
--

DROP TABLE IF EXISTS `estado_bloque_cuenta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estado_bloque_cuenta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_cobro_id` bigint(20) unsigned NOT NULL,
  `bloque_id` bigint(20) unsigned NOT NULL,
  `estado_actual_id` bigint(20) unsigned NOT NULL,
  `responsable_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_ingreso_bloque` datetime NOT NULL COMMENT 'Cuándo entró a este bloque',
  `fecha_completado_bloque` datetime DEFAULT NULL COMMENT 'Cuándo completó el bloque (estado FINAL)',
  `fecha_ultima_actualizacion` datetime DEFAULT NULL,
  `numero_devoluciones` int(11) NOT NULL DEFAULT 0,
  `bloque_completado` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE cuando alcanza estado FINAL',
  `observaciones` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Datos específicos por bloque' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cuenta_bloque` (`cuenta_cobro_id`,`bloque_id`),
  KEY `estado_bloque_cuenta_estado_actual_id_foreign` (`estado_actual_id`),
  KEY `estado_bloque_cuenta_responsable_id_foreign` (`responsable_id`),
  KEY `estado_bloque_cuenta_cuenta_cobro_id_index` (`cuenta_cobro_id`),
  KEY `estado_bloque_cuenta_bloque_id_estado_actual_id_index` (`bloque_id`,`estado_actual_id`),
  KEY `estado_bloque_cuenta_bloque_completado_index` (`bloque_completado`),
  CONSTRAINT `estado_bloque_cuenta_bloque_id_foreign` FOREIGN KEY (`bloque_id`) REFERENCES `bloques_workflow` (`id`),
  CONSTRAINT `estado_bloque_cuenta_cuenta_cobro_id_foreign` FOREIGN KEY (`cuenta_cobro_id`) REFERENCES `cuentas_cobro` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estado_bloque_cuenta_estado_actual_id_foreign` FOREIGN KEY (`estado_actual_id`) REFERENCES `estados_workflow` (`id`),
  CONSTRAINT `estado_bloque_cuenta_responsable_id_foreign` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_bloque_cuenta`
--

LOCK TABLES `estado_bloque_cuenta` WRITE;
/*!40000 ALTER TABLE `estado_bloque_cuenta` DISABLE KEYS */;
INSERT INTO `estado_bloque_cuenta` VALUES (2,2,1,4,1,'2026-01-22 12:42:57','2026-01-24 12:42:57',NULL,0,1,NULL,NULL,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,2,2,7,1,'2026-02-11 17:29:21','2026-02-11 17:29:36','2026-02-11 17:29:36',0,1,NULL,NULL,'2026-02-11 17:42:57','2026-02-11 22:29:36'),(11,3,1,4,1,'2026-02-11 17:05:15','2026-02-11 17:05:26','2026-02-11 17:05:26',0,1,NULL,NULL,'2026-02-11 17:45:28','2026-02-11 22:05:26'),(12,3,2,7,1,'2026-02-11 17:29:17','2026-02-11 17:29:31','2026-02-11 17:29:31',0,1,NULL,NULL,'2026-02-11 17:45:41','2026-02-11 22:29:31'),(23,1,1,4,1,'2026-01-31 00:00:00','2026-02-11 00:00:00','2026-02-11 12:53:07',0,1,NULL,NULL,'2026-02-11 17:52:57','2026-02-11 18:39:54'),(24,1,2,7,1,'2026-02-11 00:00:00','2026-02-11 00:00:00','2026-02-11 13:00:37',0,1,NULL,NULL,'2026-02-11 17:53:07','2026-02-11 18:39:54'),(25,1,3,10,1,'2026-02-11 00:00:00','2026-02-11 00:00:00','2026-02-11 13:56:29',0,1,NULL,NULL,'2026-02-11 18:00:37','2026-02-11 21:59:44'),(26,1,4,13,1,'2026-02-11 00:00:00','2026-02-11 00:00:00','2026-02-11 13:56:34',0,1,NULL,NULL,'2026-02-11 18:56:29','2026-02-11 21:59:44'),(27,1,5,15,1,'2026-02-11 00:00:00',NULL,'2026-02-11 13:56:34',0,0,NULL,NULL,'2026-02-11 18:56:34','2026-02-11 21:59:44'),(28,2,3,10,3,'2026-02-11 17:29:36','2026-02-11 17:33:44','2026-02-11 17:33:44',0,1,NULL,NULL,'2026-02-11 19:18:33','2026-02-11 22:33:44'),(29,2,4,12,3,'2026-02-11 17:33:44',NULL,'2026-02-11 17:33:44',0,0,NULL,NULL,'2026-02-11 19:26:53','2026-02-11 22:33:44'),(30,3,3,10,3,'2026-02-11 17:29:31','2026-02-11 17:33:25','2026-02-11 17:33:25',0,1,NULL,NULL,'2026-02-11 20:10:40','2026-02-11 22:33:25'),(31,3,4,12,3,'2026-02-11 17:33:25',NULL,'2026-02-11 17:33:25',0,0,NULL,NULL,'2026-02-11 22:17:42','2026-02-11 22:33:25');
/*!40000 ALTER TABLE `estado_bloque_cuenta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados_workflow`
--

DROP TABLE IF EXISTS `estados_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_workflow` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bloque_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `tipo` enum('INICIAL','EN_PROCESO','APROBADO','DEVUELTO','FINAL') NOT NULL,
  `es_inicial` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Primer estado al entrar al bloque',
  `es_final` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Estado que completa el bloque',
  `permite_devolucion` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Permite regresar al bloque anterior',
  `color_hex` varchar(7) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `estados_workflow_codigo_unique` (`codigo`),
  KEY `estados_workflow_bloque_id_index` (`bloque_id`),
  KEY `estados_workflow_tipo_index` (`tipo`),
  KEY `estados_workflow_codigo_index` (`codigo`),
  CONSTRAINT `estados_workflow_bloque_id_foreign` FOREIGN KEY (`bloque_id`) REFERENCES `bloques_workflow` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estados_workflow`
--

LOCK TABLES `estados_workflow` WRITE;
/*!40000 ALTER TABLE `estados_workflow` DISABLE KEYS */;
INSERT INTO `estados_workflow` VALUES (1,1,'en revision','REV1_REV','INICIAL',1,0,0,'#17a2b8','en revision',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(2,1,'reserva','REV1_RES','EN_PROCESO',0,0,0,'#ffc107','reserva',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,1,'en espera firma jaime moncaleano','REV1_ESP_MON','EN_PROCESO',0,0,0,'#ffc107','en espera firma jaime moncaleano',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(4,1,'pasa','REV1_PASA','APROBADO',0,1,0,'#28a745','pasa',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(5,1,'devuelta','REV1_DEV','DEVUELTO',0,0,1,'#dc3545','devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(6,2,'en espera ingreso mercancia','SAP_ESP','INICIAL',1,0,0,'#17a2b8','en espera ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(7,2,'con ingreso mercancia','SAP_OK','APROBADO',0,1,0,'#28a745','con ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(8,2,'devuelta','SAP_DEV','DEVUELTO',0,0,1,'#dc3545','devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(9,3,'EN ESPERA EN FACTURACION','FAC_ESP','INICIAL',1,0,0,'#17a2b8','EN ESPERA EN FACTURACION',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(10,3,'FACTURADA','FAC_OK','APROBADO',0,1,0,'#28a745','FACTURADA',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(11,3,'devuelta','FAC_DEV','DEVUELTO',0,0,1,'#dc3545','devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(12,4,'En espera','FIR_ESP','INICIAL',1,0,0,'#17a2b8','En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(13,4,'Firmada','FIR_OK','APROBADO',0,1,0,'#28a745','Firmada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(14,4,'devuelta','FIR_DEV','DEVUELTO',0,0,1,'#dc3545','devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(15,5,'En espera','HAC_ESP','INICIAL',1,0,0,'#17a2b8','En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(16,5,'Radicada','HAC_OK','APROBADO',0,1,0,'#28a745','Radicada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(17,5,'devuelta','HAC_DEV','DEVUELTO',0,0,1,'#dc3545','devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(18,6,'Por Confirmar','FIN_PEND','INICIAL',1,0,0,'#17a2b8','Por Confirmar',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(19,6,'Finalizada','FIN_OK','APROBADO',0,1,0,'#28a745','Finalizada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `estados_workflow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_workflow`
--

DROP TABLE IF EXISTS `historial_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_workflow` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_cobro_id` bigint(20) unsigned NOT NULL,
  `bloque_id` bigint(20) unsigned NOT NULL,
  `estado_origen_id` bigint(20) unsigned DEFAULT NULL,
  `estado_destino_id` bigint(20) unsigned NOT NULL,
  `usuario_accion_id` bigint(20) unsigned NOT NULL,
  `fecha_transicion` timestamp NOT NULL DEFAULT current_timestamp(),
  `tiempo_en_estado_anterior_minutos` int(11) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL COMMENT 'APROBAR, RECHAZAR, DEVOLVER, PASAR_BLOQUE',
  `comentarios` text DEFAULT NULL,
  `documentos_adjuntos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documentos_adjuntos`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  KEY `historial_workflow_estado_origen_id_foreign` (`estado_origen_id`),
  KEY `historial_workflow_estado_destino_id_foreign` (`estado_destino_id`),
  KEY `historial_workflow_cuenta_cobro_id_index` (`cuenta_cobro_id`),
  KEY `historial_workflow_bloque_id_index` (`bloque_id`),
  KEY `historial_workflow_fecha_transicion_index` (`fecha_transicion`),
  KEY `historial_workflow_usuario_accion_id_index` (`usuario_accion_id`),
  CONSTRAINT `historial_workflow_bloque_id_foreign` FOREIGN KEY (`bloque_id`) REFERENCES `bloques_workflow` (`id`),
  CONSTRAINT `historial_workflow_cuenta_cobro_id_foreign` FOREIGN KEY (`cuenta_cobro_id`) REFERENCES `cuentas_cobro` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_workflow_estado_destino_id_foreign` FOREIGN KEY (`estado_destino_id`) REFERENCES `estados_workflow` (`id`),
  CONSTRAINT `historial_workflow_estado_origen_id_foreign` FOREIGN KEY (`estado_origen_id`) REFERENCES `estados_workflow` (`id`),
  CONSTRAINT `historial_workflow_usuario_accion_id_foreign` FOREIGN KEY (`usuario_accion_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_workflow`
--

LOCK TABLES `historial_workflow` WRITE;
/*!40000 ALTER TABLE `historial_workflow` DISABLE KEYS */;
INSERT INTO `historial_workflow` VALUES (1,1,1,1,4,1,'2026-02-11 17:43:12',NULL,NULL,NULL,NULL,NULL),(2,1,1,4,6,1,'2026-02-11 17:43:12',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(3,3,3,9,10,1,'2026-02-11 17:45:17',NULL,NULL,NULL,NULL,NULL),(4,3,3,10,12,1,'2026-02-11 17:45:17',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(5,3,4,12,13,1,'2026-02-11 17:45:20',0,NULL,NULL,NULL,NULL),(6,3,4,13,15,1,'2026-02-11 17:45:20',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(7,3,5,15,16,1,'2026-02-11 17:45:24',0,NULL,NULL,NULL,NULL),(8,3,5,16,18,1,'2026-02-11 17:45:24',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(9,3,6,18,19,1,'2026-02-11 17:45:28',0,NULL,NULL,NULL,NULL),(10,3,6,19,1,1,'2026-02-11 17:45:28',0,NULL,'Automatismo: Ciclo Finalizado. Cuenta #2 iniciada.',NULL,NULL),(11,3,1,1,4,1,'2026-02-11 17:45:41',0,NULL,NULL,NULL,NULL),(12,3,1,4,6,1,'2026-02-11 17:45:41',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(13,1,2,6,7,1,'2026-02-11 17:52:06',-9,NULL,NULL,NULL,NULL),(14,1,2,7,9,1,'2026-02-11 17:52:06',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(15,1,3,9,10,1,'2026-02-11 17:52:10',0,NULL,NULL,NULL,NULL),(16,1,3,10,12,1,'2026-02-11 17:52:10',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(17,1,4,12,13,1,'2026-02-11 17:52:13',0,NULL,NULL,NULL,NULL),(18,1,4,13,15,1,'2026-02-11 17:52:13',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(19,1,5,15,16,1,'2026-02-11 17:52:17',0,NULL,NULL,NULL,NULL),(20,1,5,16,18,1,'2026-02-11 17:52:17',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(21,1,6,18,19,1,'2026-02-11 17:52:21',0,NULL,NULL,NULL,NULL),(22,1,6,19,1,1,'2026-02-11 17:52:21',0,NULL,'Automatismo: Ciclo Finalizado. Cuenta #2 iniciada.',NULL,NULL),(23,1,1,1,4,1,'2026-02-11 17:52:28',0,NULL,NULL,NULL,NULL),(24,1,1,4,6,1,'2026-02-11 17:52:28',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(25,1,2,6,7,1,'2026-02-11 17:52:42',0,NULL,NULL,NULL,NULL),(26,1,2,7,9,1,'2026-02-11 17:52:42',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(27,1,3,9,10,1,'2026-02-11 17:52:46',0,NULL,NULL,NULL,NULL),(28,1,3,10,12,1,'2026-02-11 17:52:46',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(29,1,4,12,13,1,'2026-02-11 17:52:50',0,NULL,NULL,NULL,NULL),(30,1,4,13,15,1,'2026-02-11 17:52:50',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(31,1,5,15,16,1,'2026-02-11 17:52:54',0,NULL,NULL,NULL,NULL),(32,1,5,16,18,1,'2026-02-11 17:52:54',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(33,1,6,18,19,1,'2026-02-11 17:52:57',0,NULL,NULL,NULL,NULL),(34,1,6,19,1,1,'2026-02-11 17:52:57',0,NULL,'Automatismo: Ciclo Finalizado. Cuenta #3 iniciada.',NULL,NULL),(35,1,1,1,4,1,'2026-02-11 17:53:07',0,NULL,NULL,NULL,NULL),(36,1,1,4,6,1,'2026-02-11 17:53:07',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(37,1,2,6,7,1,'2026-02-11 18:00:37',-8,NULL,NULL,NULL,NULL),(38,1,2,7,9,1,'2026-02-11 18:00:37',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(39,1,3,9,10,1,'2026-02-11 18:56:29',-56,NULL,NULL,NULL,NULL),(40,1,3,10,12,1,'2026-02-11 18:56:29',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(41,1,4,12,13,1,'2026-02-11 18:56:34',0,NULL,NULL,NULL,NULL),(42,1,4,13,15,1,'2026-02-11 18:56:34',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(43,2,2,6,7,1,'2026-02-11 19:18:33',NULL,NULL,NULL,NULL,NULL),(44,2,2,7,9,1,'2026-02-11 19:18:33',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(45,2,3,9,8,3,'2026-02-11 19:23:43',-5,NULL,NULL,NULL,NULL),(46,2,2,8,7,1,'2026-02-11 19:26:41',-3,NULL,NULL,NULL,NULL),(47,2,2,7,9,1,'2026-02-11 19:26:42',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(48,2,3,9,10,3,'2026-02-11 19:26:52',0,NULL,NULL,NULL,NULL),(49,2,3,10,12,3,'2026-02-11 19:26:53',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(50,2,4,12,11,1,'2026-02-11 19:27:12',0,NULL,NULL,NULL,NULL),(51,2,3,11,9,3,'2026-02-11 19:27:23',0,NULL,NULL,NULL,NULL),(52,2,3,9,10,3,'2026-02-11 19:27:27',0,NULL,NULL,NULL,NULL),(53,2,3,10,12,3,'2026-02-11 19:27:27',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(54,3,2,6,7,1,'2026-02-11 20:10:40',-145,NULL,NULL,NULL,NULL),(55,3,2,7,9,1,'2026-02-11 20:10:40',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(56,3,3,9,8,1,'2026-02-11 20:10:51',0,NULL,NULL,NULL,NULL),(57,3,2,8,5,1,'2026-02-11 20:10:57',0,NULL,NULL,NULL,NULL),(58,3,1,5,4,1,'2026-02-11 22:00:09',-109,NULL,NULL,NULL,NULL),(59,3,1,4,6,1,'2026-02-11 22:00:09',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(60,3,2,6,5,1,'2026-02-11 22:05:15',-5,NULL,NULL,NULL,NULL),(61,3,1,5,4,1,'2026-02-11 22:05:26',0,NULL,NULL,NULL,NULL),(62,3,1,4,6,1,'2026-02-11 22:05:26',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(63,3,2,6,7,1,'2026-02-11 22:05:32',0,NULL,NULL,NULL,NULL),(64,3,2,7,9,1,'2026-02-11 22:05:32',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(65,3,3,9,10,3,'2026-02-11 22:17:42',-12,NULL,NULL,NULL,NULL),(66,3,3,10,12,3,'2026-02-11 22:17:42',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(67,2,4,12,11,1,'2026-02-11 22:17:59',-171,NULL,NULL,NULL,NULL),(68,3,4,12,11,1,'2026-02-11 22:18:03',0,NULL,NULL,NULL,NULL),(69,3,3,11,8,1,'2026-02-11 22:29:17',-11,NULL,NULL,NULL,NULL),(70,2,3,11,8,1,'2026-02-11 22:29:21',-11,NULL,NULL,NULL,NULL),(71,3,2,8,7,1,'2026-02-11 22:29:31',0,NULL,NULL,NULL,NULL),(72,3,2,7,9,1,'2026-02-11 22:29:31',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(73,2,2,8,7,1,'2026-02-11 22:29:36',0,NULL,NULL,NULL,NULL),(74,2,2,7,9,1,'2026-02-11 22:29:36',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(75,3,3,9,10,3,'2026-02-11 22:33:25',-4,NULL,NULL,NULL,NULL),(76,3,3,10,12,3,'2026-02-11 22:33:25',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL),(77,2,3,9,10,3,'2026-02-11 22:33:44',-4,NULL,NULL,NULL,NULL),(78,2,3,10,12,3,'2026-02-11 22:33:44',0,NULL,'Automatismo: PASAR_BLOQUE',NULL,NULL);
/*!40000 ALTER TABLE `historial_workflow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metricas_diarias`
--

DROP TABLE IF EXISTS `metricas_diarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metricas_diarias` (
  `fecha` date NOT NULL,
  `bloque_id` bigint(20) unsigned NOT NULL,
  `cantidad_procesada` int(11) NOT NULL DEFAULT 0,
  `cantidad_aprobada` int(11) NOT NULL DEFAULT 0,
  `cantidad_devuelta` int(11) NOT NULL DEFAULT 0,
  `promedio_tiempo_horas` decimal(10,2) DEFAULT NULL,
  `cumplimiento_sla_pct` int(11) DEFAULT NULL,
  PRIMARY KEY (`fecha`,`bloque_id`),
  KEY `metricas_diarias_bloque_id_foreign` (`bloque_id`),
  KEY `metricas_diarias_fecha_index` (`fecha`),
  CONSTRAINT `metricas_diarias_bloque_id_foreign` FOREIGN KEY (`bloque_id`) REFERENCES `bloques_workflow` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metricas_diarias`
--

LOCK TABLES `metricas_diarias` WRITE;
/*!40000 ALTER TABLE `metricas_diarias` DISABLE KEYS */;
/*!40000 ALTER TABLE `metricas_diarias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_01_01_000001_create_roles_table',1),(2,'2024_01_01_000002_create_usuarios_table',1),(3,'2024_01_01_000003_create_catalogos_tables',1),(4,'2024_01_01_000004_create_actores_tables',1),(5,'2024_01_01_000005_create_contratos_tables',1),(6,'2024_01_01_000006_create_workflow_tables',1),(7,'2024_01_01_000007_create_cuentas_cobro_table',1),(8,'2024_01_01_000008_create_cuentas_relacionadas_tables',1),(9,'2024_01_01_000009_create_historial_workflow_table',1),(10,'2024_01_01_000010_create_auditoria_metricas_tables',1),(11,'2026_02_08_035230_create_sessions_table',1),(12,'2026_02_08_154128_add_hacienda_fields_to_cuentas_cobro_table',1),(13,'2026_02_08_201550_fix_unique_on_cuentas_cobro',1),(14,'2026_02_11_134816_add_individual_permissions_to_usuarios_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modalidades`
--

DROP TABLE IF EXISTS `modalidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modalidades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `es_activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modalidades_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modalidades`
--

LOCK TABLES `modalidades` WRITE;
/*!40000 ALTER TABLE `modalidades` DISABLE KEYS */;
INSERT INTO `modalidades` VALUES (1,'PRESTACIÓN DE SERVICIOS',NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `modalidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planillas_seguridad_social`
--

DROP TABLE IF EXISTS `planillas_seguridad_social`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planillas_seguridad_social` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_cobro_id` bigint(20) unsigned NOT NULL,
  `mes_planilla` varchar(20) NOT NULL COMMENT 'PLANILLA SS del Excel (col 17)',
  `anio_planilla` year(4) DEFAULT NULL,
  `numero_planilla` varchar(50) DEFAULT NULL,
  `valor_total` decimal(19,2) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `es_ultima` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planillas_seguridad_social_cuenta_cobro_id_index` (`cuenta_cobro_id`),
  KEY `planillas_seguridad_social_mes_planilla_anio_planilla_index` (`mes_planilla`,`anio_planilla`),
  CONSTRAINT `planillas_seguridad_social_cuenta_cobro_id_foreign` FOREIGN KEY (`cuenta_cobro_id`) REFERENCES `cuentas_cobro` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planillas_seguridad_social`
--

LOCK TABLES `planillas_seguridad_social` WRITE;
/*!40000 ALTER TABLE `planillas_seguridad_social` DISABLE KEYS */;
INSERT INTO `planillas_seguridad_social` VALUES (1,1,'JANUARY',NULL,'PLAN-368125',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(2,2,'JANUARY',NULL,'PLAN-313216',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,3,'JANUARY',NULL,'PLAN-796186',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `planillas_seguridad_social` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plantas`
--

DROP TABLE IF EXISTS `plantas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plantas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `es_activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plantas_codigo_unique` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plantas`
--

LOCK TABLES `plantas` WRITE;
/*!40000 ALTER TABLE `plantas` DISABLE KEYS */;
INSERT INTO `plantas` VALUES (1,'P001','PLANTA CENTRAL',NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `plantas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registros_presupuestales`
--

DROP TABLE IF EXISTS `registros_presupuestales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registros_presupuestales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contrato_id` bigint(20) unsigned NOT NULL,
  `numero_rp` varchar(50) NOT NULL COMMENT 'RP del Excel (col 4)',
  `fecha_rp` date DEFAULT NULL COMMENT 'FECHA RP del Excel (col 5)',
  `valor_rp` decimal(19,2) DEFAULT NULL COMMENT 'VALOR RP del Excel (col 6)',
  `estado` varchar(50) NOT NULL DEFAULT 'ACTIVO',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registros_presupuestales_numero_rp_index` (`numero_rp`),
  KEY `registros_presupuestales_contrato_id_index` (`contrato_id`),
  CONSTRAINT `registros_presupuestales_contrato_id_foreign` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registros_presupuestales`
--

LOCK TABLES `registros_presupuestales` WRITE;
/*!40000 ALTER TABLE `registros_presupuestales` DISABLE KEYS */;
INSERT INTO `registros_presupuestales` VALUES (1,1,'RP-9117','2026-01-11',6331664.00,'ACTIVO',NULL,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(2,2,'RP-5337','2025-12-11',6525020.00,'ACTIVO',NULL,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,3,'RP-3287','2025-11-11',8226911.00,'ACTIVO',NULL,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `registros_presupuestales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL COMMENT 'Nombre del rol',
  `descripcion` text DEFAULT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Estructura de permisos' CHECK (json_valid(`permisos`)),
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_nombre_unique` (`nombre`),
  KEY `roles_nombre_index` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Acceso total y gestión de datos del sistema.','{\"contratos_ver\":true,\"contratos_editar\":true,\"cuentas_ver\":true,\"cuentas_editar\":true,\"usuarios_gestionar\":true,\"configuracion_sistema\":true,\"es_admin\":true,\"acceder_dashboard\":true,\"acceder_workflow\":true,\"responsable_sap\":true,\"responsable_facturacion\":true}',1,'2026-02-11 17:42:57','2026-02-11 18:37:19'),(2,'Visualizador','Consulta de información sin permisos de edición.','{\"contratos_ver\":true,\"contratos_editar\":false,\"cuentas_ver\":true,\"cuentas_editar\":false,\"usuarios_gestionar\":false,\"configuracion_sistema\":false,\"es_admin\":false,\"acceder_dashboard\":true,\"acceder_workflow\":true,\"responsable_sap\":false,\"responsable_facturacion\":false}',1,'2026-02-11 17:42:57','2026-02-11 18:37:19'),(4,'Personalizado','Permisos definidos individualmente por usuario.','[]',1,'2026-02-11 19:09:51','2026-02-11 19:09:51');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('5eWEqS8UlBWzp0Q4sCXADOAfjgviqGkzbQIe5KuG',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTUs2bGQwUm95NmNrdXRWSDVMVnB1TDB0SlpteUxhcXhwV2NmMnluVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC93b3JrZmxvdyI7czo1OiJyb3V0ZSI7czo4OiJ3b3JrZmxvdyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7fQ==',1770850737),('LFoGa2WyNPplkf8YBgfmoWFnZ3DEJp989Cb2FUxE',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoidTZZVUo1WlJ6VDF1akFHRlpWYnhNUzlyZnRxSmFYQWdzbUQ1bzJnWiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb25maWd1cmFjaW9uIjtzOjU6InJvdXRlIjtzOjE5OiJjb25maWd1cmFjaW9uLmluZGV4Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1770850706),('OyroZMEoQ7bdflWUc4oJZdaF8RUrDyoE52t27V1p',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYXhDaHd3SDFjOGhmN0ZkalNJME9TY0JRZmFocE9oVExIUWVCT1VFNyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzt9',1770845927);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supervisores`
--

DROP TABLE IF EXISTS `supervisores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supervisores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supervisores_nombres_apellidos_index` (`nombres`,`apellidos`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supervisores`
--

LOCK TABLES `supervisores` WRITE;
/*!40000 ALTER TABLE `supervisores` DISABLE KEYS */;
INSERT INTO `supervisores` VALUES (1,'SERGIO','MONCALEANO','JEFE DE AREA',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(2,'CONSUELO','MARTINEZ','COORDINADORA',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,'JUAN','PABLO DUARTE','SUPERVISOR TÉCNICO',NULL,NULL,1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(4,'SERGIO','','SUPERVISOR',NULL,NULL,1,'2026-02-11 18:39:54','2026-02-11 18:39:54');
/*!40000 ALTER TABLE `supervisores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_documento`
--

DROP TABLE IF EXISTS `tipos_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_documento` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `es_obligatorio` tinyint(1) NOT NULL DEFAULT 0,
  `categoria` varchar(50) DEFAULT NULL,
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_documento_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_documento`
--

LOCK TABLES `tipos_documento` WRITE;
/*!40000 ALTER TABLE `tipos_documento` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipos_documento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transiciones_permitidas`
--

DROP TABLE IF EXISTS `transiciones_permitidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transiciones_permitidas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `estado_origen_id` bigint(20) unsigned NOT NULL,
  `estado_destino_id` bigint(20) unsigned NOT NULL,
  `requiere_comentario` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_documento` tinyint(1) NOT NULL DEFAULT 0,
  `accion` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `es_activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transicion` (`estado_origen_id`,`estado_destino_id`),
  KEY `transiciones_permitidas_estado_destino_id_foreign` (`estado_destino_id`),
  CONSTRAINT `transiciones_permitidas_estado_destino_id_foreign` FOREIGN KEY (`estado_destino_id`) REFERENCES `estados_workflow` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transiciones_permitidas_estado_origen_id_foreign` FOREIGN KEY (`estado_origen_id`) REFERENCES `estados_workflow` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transiciones_permitidas`
--

LOCK TABLES `transiciones_permitidas` WRITE;
/*!40000 ALTER TABLE `transiciones_permitidas` DISABLE KEYS */;
INSERT INTO `transiciones_permitidas` VALUES (1,1,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en revision a reserva',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(2,1,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en revision a en espera firma jaime moncaleano',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(3,1,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en revision a pasa',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(4,2,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de reserva a en revision',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(5,2,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de reserva a en espera firma jaime moncaleano',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(6,2,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de reserva a pasa',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(7,3,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en espera firma jaime moncaleano a en revision',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(8,3,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en espera firma jaime moncaleano a reserva',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(9,3,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en espera firma jaime moncaleano a pasa',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(10,4,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de pasa a en revision',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(11,4,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de pasa a reserva',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(12,4,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de pasa a en espera firma jaime moncaleano',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(13,4,6,0,0,'PASAR_BLOQUE','PASAR_BLOQUE de pasa a en espera ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(14,5,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a en revision',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(15,5,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a reserva',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(16,5,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a en espera firma jaime moncaleano',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(17,5,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a pasa',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(18,6,7,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en espera ingreso mercancia a con ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(19,6,5,1,0,'DEVOLVER','DEVOLVER de en espera ingreso mercancia a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(20,7,6,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de con ingreso mercancia a en espera ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(21,7,9,0,0,'PASAR_BLOQUE','PASAR_BLOQUE de con ingreso mercancia a EN ESPERA EN FACTURACION',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(22,7,5,1,0,'DEVOLVER','DEVOLVER de con ingreso mercancia a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(23,8,6,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a en espera ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(24,8,7,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a con ingreso mercancia',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(25,8,5,1,0,'DEVOLVER','DEVOLVER de devuelta a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(26,9,10,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de EN ESPERA EN FACTURACION a FACTURADA',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(27,9,8,1,0,'DEVOLVER','DEVOLVER de EN ESPERA EN FACTURACION a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(28,10,9,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de FACTURADA a EN ESPERA EN FACTURACION',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(29,10,12,0,0,'PASAR_BLOQUE','PASAR_BLOQUE de FACTURADA a En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(30,10,8,1,0,'DEVOLVER','DEVOLVER de FACTURADA a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(31,11,9,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a EN ESPERA EN FACTURACION',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(32,11,10,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a FACTURADA',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(33,11,8,1,0,'DEVOLVER','DEVOLVER de devuelta a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(34,12,13,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de En espera a Firmada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(35,12,11,1,0,'DEVOLVER','DEVOLVER de En espera a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(36,13,12,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Firmada a En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(37,13,15,0,0,'PASAR_BLOQUE','PASAR_BLOQUE de Firmada a En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(38,13,11,1,0,'DEVOLVER','DEVOLVER de Firmada a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(39,14,12,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(40,14,13,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a Firmada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(41,14,11,1,0,'DEVOLVER','DEVOLVER de devuelta a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(42,15,16,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de En espera a Radicada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(43,15,14,1,0,'DEVOLVER','DEVOLVER de En espera a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(44,16,15,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Radicada a En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(45,16,18,0,0,'PASAR_BLOQUE','PASAR_BLOQUE de Radicada a Por Confirmar',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(46,16,14,1,0,'DEVOLVER','DEVOLVER de Radicada a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(47,17,15,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a En espera',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(48,17,16,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a Radicada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(49,17,14,1,0,'DEVOLVER','DEVOLVER de devuelta a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(50,18,19,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Por Confirmar a Finalizada',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(51,18,17,1,0,'DEVOLVER','DEVOLVER de Por Confirmar a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(52,19,18,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Finalizada a Por Confirmar',1,'2026-02-11 17:42:57','2026-02-11 17:42:57'),(53,19,17,1,0,'DEVOLVER','DEVOLVER de Finalizada a devuelta',1,'2026-02-11 17:42:57','2026-02-11 17:42:57');
/*!40000 ALTER TABLE `transiciones_permitidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `primer_nombre` varchar(50) NOT NULL,
  `segundo_nombre` varchar(50) DEFAULT NULL,
  `primer_apellido` varchar(50) NOT NULL,
  `segundo_apellido` varchar(50) DEFAULT NULL,
  `user` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` bigint(20) unsigned NOT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permisos`)),
  `es_activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuarios_user_unique` (`user`),
  KEY `usuarios_user_index` (`user`),
  KEY `usuarios_rol_id_index` (`rol_id`),
  CONSTRAINT `usuarios_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin',NULL,'Sistema',NULL,'admin','$2y$12$afQDmkh4hKe//Dq15xTGQegczO.H3u736iPmOLlBqXXBzD239cqOS',1,NULL,1,NULL,'2026-02-11 18:40:21',NULL,'2026-02-11 17:42:57','2026-02-11 18:40:21'),(2,'Juan',NULL,'Consulta',NULL,'viewer','$2y$12$pZkKFTd93v2INdRpID36EOnXVGxYg4ql0DzRvXmPipucHugCYBFYK',2,NULL,1,NULL,NULL,NULL,'2026-02-11 17:42:57','2026-02-11 20:10:30'),(3,'CONSUELO',NULL,'HIDALGO',NULL,'CONSUELO','$2y$12$xuJ2ffdWZqRPLJ6qgCphz.y4PpBGXr8a2HJGTw.48ZVRQbks4Mt7K',4,'{\"acceder_dashboard\":true,\"editar_dashboard\":true,\"acceder_workflow\":true,\"editar_workflow\":true,\"ver_solo_asignados\":true,\"responsable_facturacion\":true,\"acceder_consolidado\":false,\"es_admin\":false,\"responsable_sap\":false,\"bloques_permitidos\":[\"FAC\"]}',1,NULL,'2026-02-11 22:12:21',NULL,'2026-02-11 18:38:50','2026-02-11 22:34:34');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-11 17:59:15
