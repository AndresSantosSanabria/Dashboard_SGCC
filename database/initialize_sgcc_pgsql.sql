-- PostgreSQL dump
--
-- Host: 127.0.0.1 Database: sistem_sgcc
-- ------------------------------------------------------
-- Motor: PostgreSQL 15+
-- Reescrito con orden correcto de dependencias

SET client_encoding = 'UTF8';

-- ============================================================
-- 1. TABLAS BASE (sin dependencias externas)
-- ============================================================

DROP TABLE IF EXISTS roles CASCADE;
CREATE TABLE roles (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(50) NOT NULL,
  descripcion text DEFAULT NULL,
  tipo VARCHAR(20) CHECK (tipo IN ('SISTEMA','PERSONALIZADO')) NOT NULL DEFAULT 'SISTEMA',
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS permisos CASCADE;
CREATE TABLE permisos (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  slug varchar(100) NOT NULL,
  descripcion text DEFAULT NULL,
  modulo varchar(50) DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (slug)
);

DROP TABLE IF EXISTS bloques_workflow CASCADE;
CREATE TABLE bloques_workflow (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  codigo varchar(20) NOT NULL,
  orden SMALLINT NOT NULL,
  sla_horas INTEGER DEFAULT NULL,
  requiere_aprobacion SMALLINT NOT NULL DEFAULT 0,
  roles_permitidos TEXT DEFAULT NULL,
  descripcion text DEFAULT NULL,
  icono varchar(50) DEFAULT NULL,
  color_hex varchar(7) DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (codigo),
  UNIQUE (orden)
);

DROP TABLE IF EXISTS modalidades CASCADE;
CREATE TABLE modalidades (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  descripcion text DEFAULT NULL,
  es_activa SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS conceptos CASCADE;
CREATE TABLE conceptos (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(200) NOT NULL,
  descripcion text DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS plantas CASCADE;
CREATE TABLE plantas (
  id BIGSERIAL PRIMARY KEY,
  codigo varchar(50) NOT NULL,
  nombre varchar(150) NOT NULL,
  descripcion text DEFAULT NULL,
  es_activa SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (codigo)
);

DROP TABLE IF EXISTS tipos_contratista CASCADE;
CREATE TABLE tipos_contratista (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS tipos_documento CASCADE;
CREATE TABLE tipos_documento (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  es_obligatorio SMALLINT NOT NULL DEFAULT 0,
  categoria varchar(50) DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS estados_contrato_secop CASCADE;
CREATE TABLE estados_contrato_secop (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS festivos CASCADE;
CREATE TABLE festivos (
  id BIGSERIAL PRIMARY KEY,
  fecha date NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (fecha)
);

DROP TABLE IF EXISTS migrations CASCADE;
CREATE TABLE migrations (
  id SERIAL PRIMARY KEY,
  migration varchar(255) NOT NULL,
  batch INTEGER NOT NULL
);

DROP TABLE IF EXISTS cache CASCADE;
CREATE TABLE cache (
  "key" varchar(255) NOT NULL PRIMARY KEY,
  value TEXT NOT NULL,
  expiration INTEGER NOT NULL
);

DROP TABLE IF EXISTS cache_locks CASCADE;
CREATE TABLE cache_locks (
  "key" varchar(255) NOT NULL PRIMARY KEY,
  owner varchar(255) NOT NULL,
  expiration INTEGER NOT NULL
);

DROP TABLE IF EXISTS entidades_seguridad_social CASCADE;
CREATE TABLE entidades_seguridad_social (
  id BIGSERIAL PRIMARY KEY,
  nombre varchar(100) NOT NULL,
  tipo VARCHAR(20) CHECK (tipo IN ('SALUD','PENSION','ARL')) NOT NULL,
  codigo varchar(20) DEFAULT NULL,
  es_activa SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (nombre)
);

DROP TABLE IF EXISTS supervisores CASCADE;
CREATE TABLE supervisores (
  id BIGSERIAL PRIMARY KEY,
  nombres varchar(100) NOT NULL,
  apellidos varchar(100) NOT NULL,
  cargo varchar(100) DEFAULT NULL,
  email varchar(100) DEFAULT NULL,
  telefono varchar(20) DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

-- ============================================================
-- 2. TABLAS QUE DEPENDEN DE roles
-- ============================================================

DROP TABLE IF EXISTS usuarios CASCADE;
CREATE TABLE usuarios (
  id BIGSERIAL PRIMARY KEY,
  primer_nombre varchar(50) NOT NULL,
  segundo_nombre varchar(50) DEFAULT NULL,
  primer_apellido varchar(50) NOT NULL,
  segundo_apellido varchar(50) DEFAULT NULL,
  "user" varchar(150) NOT NULL,
  password varchar(255) NOT NULL,
  rol_id BIGINT NOT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  fecha_inactivacion TIMESTAMP NULL,
  ultimo_login TIMESTAMP NULL,
  remember_token varchar(100) DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE ("user"),
  CONSTRAINT usuarios_rol_id_foreign FOREIGN KEY (rol_id) REFERENCES roles (id)
);

DROP TABLE IF EXISTS rol_permiso CASCADE;
CREATE TABLE rol_permiso (
  rol_id BIGINT NOT NULL,
  permiso_id BIGINT NOT NULL,
  CONSTRAINT rol_permiso_permiso_id_foreign FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE,
  CONSTRAINT rol_permiso_rol_id_foreign FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS usuario_permiso CASCADE;
CREATE TABLE usuario_permiso (
  usuario_id BIGINT NOT NULL,
  permiso_id BIGINT NOT NULL,
  CONSTRAINT usuario_permiso_permiso_id_foreign FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE,
  CONSTRAINT usuario_permiso_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
);

-- ============================================================
-- 3. TABLAS QUE DEPENDEN DE bloques_workflow
-- ============================================================

DROP TABLE IF EXISTS estados_workflow CASCADE;
CREATE TABLE estados_workflow (
  id BIGSERIAL PRIMARY KEY,
  bloque_id BIGINT NOT NULL,
  nombre varchar(100) NOT NULL,
  codigo varchar(30) NOT NULL,
  tipo VARCHAR(20) CHECK (tipo IN ('INICIAL','EN_PROCESO','APROBADO','DEVUELTO','FINAL')) NOT NULL,
  es_inicial SMALLINT NOT NULL DEFAULT 0,
  es_final SMALLINT NOT NULL DEFAULT 0,
  permite_devolucion SMALLINT NOT NULL DEFAULT 0,
  contabiliza_tiempo SMALLINT NOT NULL DEFAULT 1,
  afecta_indicadores SMALLINT NOT NULL DEFAULT 1,
  color_hex varchar(7) DEFAULT NULL,
  descripcion text DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (codigo),
  CONSTRAINT estados_workflow_bloque_id_foreign FOREIGN KEY (bloque_id) REFERENCES bloques_workflow (id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS metricas_diarias CASCADE;
CREATE TABLE metricas_diarias (
  fecha date NOT NULL,
  bloque_id BIGINT NOT NULL,
  cantidad_procesada INTEGER NOT NULL DEFAULT 0,
  cantidad_aprobada INTEGER NOT NULL DEFAULT 0,
  cantidad_devuelta INTEGER NOT NULL DEFAULT 0,
  promedio_tiempo_horas decimal(10,2) DEFAULT NULL,
  cumplimiento_sla_pct INTEGER DEFAULT NULL,
  PRIMARY KEY (fecha, bloque_id),
  CONSTRAINT metricas_diarias_bloque_id_foreign FOREIGN KEY (bloque_id) REFERENCES bloques_workflow (id)
);

-- ============================================================
-- 4. TABLAS QUE DEPENDEN DE estados_workflow
-- ============================================================

DROP TABLE IF EXISTS transiciones_permitidas CASCADE;
CREATE TABLE transiciones_permitidas (
  id BIGSERIAL PRIMARY KEY,
  estado_origen_id BIGINT NOT NULL,
  estado_destino_id BIGINT NOT NULL,
  requiere_comentario SMALLINT NOT NULL DEFAULT 0,
  requiere_documento SMALLINT NOT NULL DEFAULT 0,
  accion varchar(50) DEFAULT NULL,
  descripcion text DEFAULT NULL,
  es_activa SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (estado_origen_id, estado_destino_id),
  CONSTRAINT transiciones_permitidas_estado_destino_id_foreign FOREIGN KEY (estado_destino_id) REFERENCES estados_workflow (id) ON DELETE CASCADE,
  CONSTRAINT transiciones_permitidas_estado_origen_id_foreign FOREIGN KEY (estado_origen_id) REFERENCES estados_workflow (id) ON DELETE CASCADE
);

-- ============================================================
-- 5. CONTRATISTAS Y CONTRATOS
-- ============================================================

DROP TABLE IF EXISTS contratistas CASCADE;
CREATE TABLE contratistas (
  id BIGSERIAL PRIMARY KEY,
  razon_social varchar(150) NOT NULL,
  nit varchar(20) NOT NULL,
  tipo_persona VARCHAR(20) CHECK (tipo_persona IN ('NATURAL','JURIDICA')) NOT NULL DEFAULT 'NATURAL',
  representante_legal varchar(150) DEFAULT NULL,
  telefono varchar(20) DEFAULT NULL,
  email varchar(100) DEFAULT NULL,
  direccion_fisica text DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

DROP TABLE IF EXISTS contratos CASCADE;
CREATE TABLE contratos (
  id BIGSERIAL PRIMARY KEY,
  numero_proceso varchar(50) DEFAULT NULL,
  numero_contrato varchar(50) NOT NULL,
  modalidad_id BIGINT DEFAULT NULL,
  contratista_id BIGINT NOT NULL,
  tipo_contratista_id BIGINT DEFAULT NULL,
  supervisor_id BIGINT DEFAULT NULL,
  objeto text DEFAULT NULL,
  monto_total decimal(19,2) NOT NULL,
  planta_id BIGINT DEFAULT NULL,
  no_planta varchar(255) DEFAULT NULL,
  concepto_id BIGINT DEFAULT NULL,
  concepto_precontractual varchar(255) DEFAULT NULL,
  cdp_codigo varchar(50) DEFAULT NULL,
  link_secop varchar(255) DEFAULT NULL,
  fecha_inicio date DEFAULT NULL,
  fecha_fin date DEFAULT NULL,
  plazo_ejecucion varchar(255) DEFAULT NULL,
  estado_secop_id BIGINT DEFAULT NULL,
  aprobado_y_pagado varchar(255) DEFAULT NULL,
  modificaciones_y_cierre varchar(255) DEFAULT NULL,
  saldo decimal(19,2) NOT NULL DEFAULT 0.00,
  observacion_1_razon text DEFAULT NULL,
  observacion_2_accion text DEFAULT NULL,
  razon_no_liquidacion text DEFAULT NULL,
  abogado_user_id BIGINT DEFAULT NULL,
  contador_user_id BIGINT DEFAULT NULL,
  ops_user_id BIGINT DEFAULT NULL,
  es_activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (numero_contrato),
  CONSTRAINT contratos_abogado_user_id_foreign FOREIGN KEY (abogado_user_id) REFERENCES usuarios (id),
  CONSTRAINT contratos_concepto_id_foreign FOREIGN KEY (concepto_id) REFERENCES conceptos (id),
  CONSTRAINT contratos_contador_user_id_foreign FOREIGN KEY (contador_user_id) REFERENCES usuarios (id),
  CONSTRAINT contratos_contratista_id_foreign FOREIGN KEY (contratista_id) REFERENCES contratistas (id),
  CONSTRAINT contratos_estado_secop_id_foreign FOREIGN KEY (estado_secop_id) REFERENCES estados_contrato_secop (id),
  CONSTRAINT contratos_modalidad_id_foreign FOREIGN KEY (modalidad_id) REFERENCES modalidades (id),
  CONSTRAINT contratos_ops_user_id_foreign FOREIGN KEY (ops_user_id) REFERENCES usuarios (id),
  CONSTRAINT contratos_planta_id_foreign FOREIGN KEY (planta_id) REFERENCES plantas (id),
  CONSTRAINT contratos_supervisor_id_foreign FOREIGN KEY (supervisor_id) REFERENCES supervisores (id),
  CONSTRAINT contratos_tipo_contratista_id_foreign FOREIGN KEY (tipo_contratista_id) REFERENCES tipos_contratista (id)
);

DROP TABLE IF EXISTS registros_presupuestales CASCADE;
CREATE TABLE registros_presupuestales (
  id BIGSERIAL PRIMARY KEY,
  contrato_id BIGINT NOT NULL,
  numero_rp varchar(50) NOT NULL,
  fecha_rp date DEFAULT NULL,
  valor_rp decimal(19,2) DEFAULT NULL,
  estado varchar(50) NOT NULL DEFAULT 'ACTIVO',
  observaciones text DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT registros_presupuestales_contrato_id_foreign FOREIGN KEY (contrato_id) REFERENCES contratos (id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS seguimiento_mensual CASCADE;
CREATE TABLE seguimiento_mensual (
  id BIGSERIAL PRIMARY KEY,
  contrato_id BIGINT NOT NULL,
  mes varchar(20) NOT NULL,
  anio SMALLINT NOT NULL,
  valor_pago decimal(19,2) DEFAULT NULL,
  actividades_realizadas text DEFAULT NULL,
  observaciones text DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (contrato_id, mes, anio),
  CONSTRAINT seguimiento_mensual_contrato_id_foreign FOREIGN KEY (contrato_id) REFERENCES contratos (id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS seguimiento_requisitos CASCADE;
CREATE TABLE seguimiento_requisitos (
  id BIGSERIAL PRIMARY KEY,
  contrato_id BIGINT NOT NULL,
  nombre_requisito varchar(200) NOT NULL,
  cumple SMALLINT NOT NULL DEFAULT 0,
  fecha_verificacion date DEFAULT NULL,
  observaciones text DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT seguimiento_requisitos_contrato_id_foreign FOREIGN KEY (contrato_id) REFERENCES contratos (id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS documentos CASCADE;
CREATE TABLE documentos (
  id BIGSERIAL PRIMARY KEY,
  contrato_id BIGINT DEFAULT NULL,
  tipo_documento_id BIGINT DEFAULT NULL,
  nombre_archivo varchar(255) DEFAULT NULL,
  url_almacenamiento text DEFAULT NULL,
  estado_validacion varchar(50) NOT NULL DEFAULT 'PENDIENTE',
  subido_por_id BIGINT DEFAULT NULL,
  version SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT documentos_contrato_id_foreign FOREIGN KEY (contrato_id) REFERENCES contratos (id) ON DELETE CASCADE,
  CONSTRAINT documentos_subido_por_id_foreign FOREIGN KEY (subido_por_id) REFERENCES usuarios (id),
  CONSTRAINT documentos_tipo_documento_id_foreign FOREIGN KEY (tipo_documento_id) REFERENCES tipos_documento (id)
);

-- ============================================================
-- 6. CUENTAS COBRO (dependen de contratos, bloques y estados_workflow)
-- ============================================================

DROP TABLE IF EXISTS cuentas_cobro CASCADE;
CREATE TABLE cuentas_cobro (
  id BIGSERIAL PRIMARY KEY,
  contrato_id BIGINT NOT NULL,
  numero_cuenta varchar(50) NOT NULL,
  valor_cobro decimal(19,2) NOT NULL,
  fecha_radicacion TIMESTAMP,
  numero_pagos_totales INTEGER DEFAULT NULL,
  numero_facturas_radicadas INTEGER NOT NULL DEFAULT 0,
  porcentaje_cuentas decimal(5,2) DEFAULT NULL,
  radicado_por varchar(100) DEFAULT NULL,
  bloque_actual_id BIGINT NOT NULL,
  estado_actual_id BIGINT NOT NULL,
  finalizada SMALLINT NOT NULL DEFAULT 0,
  responsable_actual_id BIGINT DEFAULT NULL,
  observaciones text DEFAULT NULL,
  ultima_factura_hacienda varchar(50) DEFAULT NULL,
  fecha_radicacion_hacienda TIMESTAMP,
  observacion_hacienda text DEFAULT NULL,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT cuentas_cobro_bloque_actual_id_foreign FOREIGN KEY (bloque_actual_id) REFERENCES bloques_workflow (id),
  CONSTRAINT cuentas_cobro_contrato_id_foreign FOREIGN KEY (contrato_id) REFERENCES contratos (id) ON DELETE CASCADE,
  CONSTRAINT cuentas_cobro_estado_actual_id_foreign FOREIGN KEY (estado_actual_id) REFERENCES estados_workflow (id),
  CONSTRAINT cuentas_cobro_responsable_actual_id_foreign FOREIGN KEY (responsable_actual_id) REFERENCES usuarios (id)
);

DROP TABLE IF EXISTS estado_bloque_cuenta CASCADE;
CREATE TABLE estado_bloque_cuenta (
  id BIGSERIAL PRIMARY KEY,
  cuenta_cobro_id BIGINT NOT NULL,
  bloque_id BIGINT NOT NULL,
  estado_actual_id BIGINT NOT NULL,
  responsable_id BIGINT DEFAULT NULL,
  fecha_ingreso_bloque TIMESTAMP NOT NULL,
  fecha_completado_bloque TIMESTAMP,
  fecha_ultima_actualizacion TIMESTAMP,
  numero_devoluciones INTEGER NOT NULL DEFAULT 0,
  bloque_completado SMALLINT NOT NULL DEFAULT 0,
  observaciones text DEFAULT NULL,
  metadata TEXT DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (cuenta_cobro_id, bloque_id),
  CONSTRAINT estado_bloque_cuenta_bloque_id_foreign FOREIGN KEY (bloque_id) REFERENCES bloques_workflow (id),
  CONSTRAINT estado_bloque_cuenta_cuenta_cobro_id_foreign FOREIGN KEY (cuenta_cobro_id) REFERENCES cuentas_cobro (id) ON DELETE CASCADE,
  CONSTRAINT estado_bloque_cuenta_estado_actual_id_foreign FOREIGN KEY (estado_actual_id) REFERENCES estados_workflow (id),
  CONSTRAINT estado_bloque_cuenta_responsable_id_foreign FOREIGN KEY (responsable_id) REFERENCES usuarios (id)
);

DROP TABLE IF EXISTS historial_workflow CASCADE;
CREATE TABLE historial_workflow (
  id BIGSERIAL PRIMARY KEY,
  cuenta_cobro_id BIGINT NOT NULL,
  bloque_id BIGINT NOT NULL,
  estado_origen_id BIGINT DEFAULT NULL,
  estado_destino_id BIGINT NOT NULL,
  usuario_accion_id BIGINT NOT NULL,
  fecha_transicion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tiempo_en_estado_anterior_minutos INTEGER DEFAULT NULL,
  accion varchar(50) DEFAULT NULL,
  comentarios text DEFAULT NULL,
  documentos_adjuntos TEXT DEFAULT NULL,
  metadata TEXT DEFAULT NULL,
  CONSTRAINT historial_workflow_bloque_id_foreign FOREIGN KEY (bloque_id) REFERENCES bloques_workflow (id),
  CONSTRAINT historial_workflow_cuenta_cobro_id_foreign FOREIGN KEY (cuenta_cobro_id) REFERENCES cuentas_cobro (id) ON DELETE CASCADE,
  CONSTRAINT historial_workflow_estado_destino_id_foreign FOREIGN KEY (estado_destino_id) REFERENCES estados_workflow (id),
  CONSTRAINT historial_workflow_estado_origen_id_foreign FOREIGN KEY (estado_origen_id) REFERENCES estados_workflow (id),
  CONSTRAINT historial_workflow_usuario_accion_id_foreign FOREIGN KEY (usuario_accion_id) REFERENCES usuarios (id)
);

DROP TABLE IF EXISTS planillas_seguridad_social CASCADE;
CREATE TABLE planillas_seguridad_social (
  id BIGSERIAL PRIMARY KEY,
  cuenta_cobro_id BIGINT NOT NULL,
  mes_planilla varchar(20) NOT NULL,
  anio_planilla SMALLINT DEFAULT NULL,
  numero_planilla varchar(50) DEFAULT NULL,
  valor_total decimal(19,2) DEFAULT NULL,
  fecha_pago date DEFAULT NULL,
  es_ultima SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT planillas_seguridad_social_cuenta_cobro_id_foreign FOREIGN KEY (cuenta_cobro_id) REFERENCES cuentas_cobro (id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS alertas CASCADE;
CREATE TABLE alertas (
  id BIGSERIAL PRIMARY KEY,
  cuenta_cobro_id BIGINT DEFAULT NULL,
  nivel varchar(20) DEFAULT NULL,
  tipo_alerta varchar(50) DEFAULT NULL,
  mensaje text DEFAULT NULL,
  leida SMALLINT NOT NULL DEFAULT 0,
  usuario_destino_id BIGINT DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (cuenta_cobro_id, usuario_destino_id, tipo_alerta, nivel),
  CONSTRAINT alertas_cuenta_cobro_id_foreign FOREIGN KEY (cuenta_cobro_id) REFERENCES cuentas_cobro (id) ON DELETE CASCADE,
  CONSTRAINT alertas_usuario_destino_id_foreign FOREIGN KEY (usuario_destino_id) REFERENCES usuarios (id)
);

DROP TABLE IF EXISTS alerta_destinatarios CASCADE;
CREATE TABLE alerta_destinatarios (
  id BIGSERIAL PRIMARY KEY,
  alerta_codigo varchar(100) NOT NULL,
  tipo_destinatario varchar(20) NOT NULL,
  destinatario_id BIGINT NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

-- ============================================================
-- 7. SEGURIDAD SOCIAL
-- ============================================================

DROP TABLE IF EXISTS contratista_seguridad_social CASCADE;
CREATE TABLE contratista_seguridad_social (
  id BIGSERIAL PRIMARY KEY,
  contratista_id BIGINT NOT NULL,
  entidad_salud_id BIGINT DEFAULT NULL,
  entidad_pension_id BIGINT DEFAULT NULL,
  entidad_arl_id BIGINT DEFAULT NULL,
  fecha_inicio date DEFAULT NULL,
  fecha_fin date DEFAULT NULL,
  es_vigente SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT contratista_seguridad_social_contratista_id_foreign FOREIGN KEY (contratista_id) REFERENCES contratistas (id) ON DELETE CASCADE,
  CONSTRAINT contratista_seguridad_social_entidad_arl_id_foreign FOREIGN KEY (entidad_arl_id) REFERENCES entidades_seguridad_social (id),
  CONSTRAINT contratista_seguridad_social_entidad_pension_id_foreign FOREIGN KEY (entidad_pension_id) REFERENCES entidades_seguridad_social (id),
  CONSTRAINT contratista_seguridad_social_entidad_salud_id_foreign FOREIGN KEY (entidad_salud_id) REFERENCES entidades_seguridad_social (id)
);

-- ============================================================
-- 8. LARAVEL (sessions, auditorias, configuraciones)
-- ============================================================

DROP TABLE IF EXISTS sessions CASCADE;
CREATE TABLE sessions (
  id varchar(255) NOT NULL PRIMARY KEY,
  user_id BIGINT DEFAULT NULL,
  ip_address varchar(45) DEFAULT NULL,
  user_agent text DEFAULT NULL,
  payload TEXT NOT NULL,
  last_activity INTEGER NOT NULL
);

DROP TABLE IF EXISTS auditorias CASCADE;
CREATE TABLE auditorias (
  id BIGSERIAL PRIMARY KEY,
  usuario_id BIGINT DEFAULT NULL,
  tabla_afectada varchar(60) NOT NULL,
  registro_id BIGINT DEFAULT NULL,
  accion varchar(20) DEFAULT NULL,
  payload_anterior TEXT DEFAULT NULL,
  payload_nuevo TEXT DEFAULT NULL,
  ip_origen varchar(45) DEFAULT NULL,
  user_agent varchar(200) DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT auditorias_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
);

DROP TABLE IF EXISTS configuraciones CASCADE;
CREATE TABLE configuraciones (
  id BIGSERIAL PRIMARY KEY,
  clave varchar(100) NOT NULL,
  valor text DEFAULT NULL,
  tipo_dato varchar(20) DEFAULT NULL,
  descripcion text DEFAULT NULL,
  modificado_por_id BIGINT DEFAULT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE (clave),
  CONSTRAINT configuraciones_modificado_por_id_foreign FOREIGN KEY (modificado_por_id) REFERENCES usuarios (id)
);

-- ============================================================
-- DATOS INICIALES
-- ============================================================

INSERT INTO bloques_workflow VALUES (1,'ESTADO TRAS PRIMERA REVISIÓN (SERGIO / CONSUELO)','REV1',1,NULL,0,NULL,'Primera fase de revisión',NULL,NULL,1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(2,'ENVIADA A INGRESO MERCANCIA SAP','SAP',2,NULL,0,NULL,'Ingreso en sistema SAP',NULL,NULL,1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(3,'EN FACTURACIÓN','FAC',3,NULL,0,NULL,'Proceso de facturación',NULL,NULL,1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(4,'FIRMA SECRETARIO','FIR',4,NULL,0,NULL,'Firma de secretaría',NULL,NULL,1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(5,'RADICADA EN HACIENDA','HAC',5,NULL,0,NULL,'Radicación final',NULL,NULL,1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(6,'FINALIZADA','FIN',6,NULL,0,NULL,'Cuenta completada',NULL,NULL,1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09');

INSERT INTO estados_workflow VALUES (1,1,'Sin tramite','REV1_SIN','INICIAL',1,0,0,0,0,'#17a2b8','Sin tramite',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(2,1,'en revision','REV1_REV','EN_PROCESO',0,0,0,1,1,'#ffc107','en revision',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(3,1,'en espera firma jaime moncaleano','REV1_ESP_MON','EN_PROCESO',0,0,0,1,1,'#ffc107','en espera firma jaime moncaleano',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(4,1,'pasa','REV1_PASA','APROBADO',0,1,0,1,1,'#28a745','pasa',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(5,1,'devuelta','REV1_DEV','DEVUELTO',0,0,1,1,1,'#dc3545','devuelta',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(6,2,'en espera ingreso mercancia','SAP_ESP','INICIAL',1,0,0,1,1,'#17a2b8','en espera ingreso mercancia',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(7,2,'con ingreso mercancia','SAP_OK','APROBADO',0,1,0,1,1,'#28a745','con ingreso mercancia',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(8,2,'devuelta','SAP_DEV','DEVUELTO',0,0,1,1,1,'#dc3545','devuelta',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(9,3,'EN ESPERA EN FACTURACION','FAC_ESP','INICIAL',1,0,0,1,1,'#17a2b8','EN ESPERA EN FACTURACION',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(10,3,'FACTURADA','FAC_OK','APROBADO',0,1,0,1,1,'#28a745','FACTURADA',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(11,3,'devuelta','FAC_DEV','DEVUELTO',0,0,1,1,1,'#dc3545','devuelta',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(12,4,'En espera','FIR_ESP','INICIAL',1,0,0,1,1,'#17a2b8','En espera',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(13,4,'Firmada','FIR_OK','APROBADO',0,1,0,1,1,'#28a745','Firmada',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(14,4,'devuelta','FIR_DEV','DEVUELTO',0,0,1,1,1,'#dc3545','devuelta',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(15,5,'En espera','HAC_ESP','INICIAL',1,0,0,1,1,'#17a2b8','En espera',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(16,5,'Radicada','HAC_OK','APROBADO',0,1,0,1,1,'#28a745','Radicada',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(17,5,'devuelta','HAC_DEV','DEVUELTO',0,0,1,1,1,'#dc3545','devuelta',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(18,6,'Por Confirmar','FIN_PEND','INICIAL',1,0,0,1,1,'#17a2b8','Por Confirmar',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(19,6,'Finalizada','FIN_OK','APROBADO',0,1,0,0,1,'#28a745','Finalizada',1,NULL,'2026-03-05 16:27:09','2026-03-05 16:32:08');

INSERT INTO roles VALUES (1,'Administrador','Acceso total y gestión de datos del sistema.','SISTEMA',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(2,'Visualizador','Consulta de información sin permisos de edición.','SISTEMA',1,'2026-03-05 16:27:09','2026-03-05 16:27:09');

INSERT INTO permisos VALUES (1,'Admin de Sistema','es_admin','Acceso total','SISTEMA','2026-03-05 16:27:09','2026-03-05 16:27:09'),(2,'Acceder Dashboard','acceder_dashboard','Ver pantalla de inicio','GENERAL','2026-03-05 16:27:09','2026-03-05 16:27:09'),(3,'Editar Dashboard','editar_dashboard','Cargar datos y editar configuración','GENERAL','2026-03-05 16:27:09','2026-03-05 16:27:09'),(4,'Acceder Workflow','acceder_workflow','Ver procesos','GENERAL','2026-03-05 16:27:09','2026-03-05 16:27:09'),(5,'Editar Workflow','editar_workflow','Editar estructura workflow','GENERAL','2026-03-05 16:27:09','2026-03-05 16:27:09'),(6,'Acceder Analítica','acceder_analitica','Ver estadísticas','BI','2026-03-05 16:27:09','2026-03-05 16:27:09'),(7,'Acceder Consolidado','acceder_consolidado','Vista de solo lectura','GENERAL','2026-03-05 16:27:09','2026-03-05 16:27:09'),(8,'Responsable SAP','responsable_sap','Rol responsable SAP','CARGO','2026-03-05 16:27:09','2026-03-05 16:27:09'),(9,'Responsable Facturación','responsable_facturacion','Rol responsable Facturación','CARGO','2026-03-05 16:27:09','2026-03-05 16:27:09'),(10,'Ver Solo Asignados','ver_solo_asignados','Restringir vista a asignados','SISTEMA','2026-03-05 16:27:09','2026-03-05 16:27:09'),(11,'Exportar Reportes','reportes_exportar','Exportar a PDF/Excel','GENERAL','2026-03-05 16:27:09','2026-03-05 16:27:09'),(12,'Ver Logs','logs_ver','Ver auditoría','SISTEMA','2026-03-05 16:27:09','2026-03-05 16:27:09');

INSERT INTO rol_permiso VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(2,4),(2,7);

INSERT INTO usuarios VALUES (1,'Admin',NULL,'Sistema',NULL,'admin','$2y$12$SjJ9B6L2DDo3oYt3mpVEAeIAYoLtfLyAPPN5yYTfJQLPybmmF0bfa',1,1,NULL,'2026-03-05 20:08:10',NULL,'2026-03-05 16:27:09','2026-03-05 20:08:10');

INSERT INTO configuraciones VALUES (1,'ALERTA_ESTANCAMIENTO_MINUTOS','20','INT','Tiempo límite en minutos para considerar un contrato estancado en un estado.',NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(2,'HORARIO_LABORAL_INICIO','08:00','STRING','Hora de inicio de la jornada laboral (HH:mm).',NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(3,'HORARIO_LABORAL_FIN','17:00','STRING','Hora de fin de la jornada laboral (HH:mm).',NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(4,'ALERTA_ESTANCAMIENTO_ACTIVA','1','BOOL','Activa o desactiva el sistema de alertas por estancamiento.',NULL,'2026-03-05 16:27:09','2026-03-05 18:22:22'),(5,'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS','10','INT','Tiempo de pre-aviso en minutos antes de llegar al límite crítico.',NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(6,'ALERTA_ESTANCAMIENTO_MSG_WARNING','PRE-AVISO: El contrato {numero_contrato} ({contratista}) lleva {tiempo} laborables estancado en {estado}.','STRING','Plantilla para mensaje de alerta preventiva.',NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(7,'ALERTA_ESTANCAMIENTO_MSG_DANGER','ALERTA CRITICA: El contrato {numero_contrato} ({contratista}) lleva {tiempo} laborables estancado en {estado}.','STRING','Plantilla para mensaje de alerta de tiempo cumplido.',NULL,'2026-03-05 16:27:09','2026-03-05 16:27:09');

INSERT INTO entidades_seguridad_social VALUES (497,'FAMISANAR','SALUD',NULL,1,'2026-03-05 16:30:48','2026-03-05 16:30:48'),(498,'PROTECCION','PENSION',NULL,1,'2026-03-05 16:30:48','2026-03-05 16:30:48'),(499,'POSITIVA','ARL',NULL,1,'2026-03-05 16:30:48','2026-03-05 16:30:48'),(500,'SANITAS','SALUD',NULL,1,'2026-03-05 16:30:48','2026-03-05 16:30:48'),(501,'COLPENSIONES','PENSION',NULL,1,'2026-03-05 16:30:48','2026-03-05 16:30:48'),(502,'SALUD TOTAL','SALUD',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(503,'COFONDOS','PENSION',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(504,'COMPENSAR','SALUD',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(505,'ALIANSALUD','SALUD',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(506,'PORVENIR','PENSION',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(507,'SURA','SALUD',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(508,'SKANDIA','PENSION',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(509,'NUEVA EPS','SALUD',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(510,'PROTECCION POSITIVA','PENSION',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(511,'PENSION DE VEJEZ','PENSION',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(512,'SAUD TOTAL','SALUD',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(513,'PAGADA POR LA GOBERNACION RIESGO 5','ARL',NULL,1,'2026-03-05 16:30:49','2026-03-05 16:30:49'),(514,'POSTIVIA','ARL',NULL,1,'2026-03-05 16:30:50','2026-03-05 16:30:50'),(515,'EPS SURA','SALUD',NULL,1,'2026-03-05 16:30:50','2026-03-05 16:30:50'),(516,'COLFONDOS','PENSION',NULL,1,'2026-03-05 16:30:50','2026-03-05 16:30:50'),(517,'COMPENSAR2','SALUD',NULL,1,'2026-03-05 16:30:51','2026-03-05 16:30:51'),(518,'CERTIFICADO PENSION','PENSION',NULL,1,'2026-03-05 16:30:51','2026-03-05 16:30:51');

INSERT INTO transiciones_permitidas VALUES (1,1,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Sin tramite a en revision',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(2,1,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Sin tramite a en espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(3,1,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de Sin tramite a pasa',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(4,1,5,1,0,'DEVOLVER','DEVOLVER de Sin tramite a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(5,2,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en revision a Sin tramite',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(6,2,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en revision a en espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(7,2,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de en revision a pasa',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(8,2,5,1,0,'DEVOLVER','DEVOLVER de en revision a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(9,3,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Sin tramite',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(10,3,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a en revision',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(11,3,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a pasa',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(12,3,5,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(13,4,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de pasa a Sin tramite',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(14,4,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de pasa a en revision',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(15,4,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de pasa a en espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(16,4,5,1,0,'DEVOLVER','DEVOLVER de pasa a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(17,4,6,0,0,'PASAR_BLOQUE','PASAR_BLOQUE de pasa a en espera ingreso mercancia',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(18,5,1,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a Sin tramite',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(19,5,2,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a en revision',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(20,5,3,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a en espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(21,5,4,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO de devuelta a pasa',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(22,6,7,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a con ingreso mercancia',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(23,6,5,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(24,7,6,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a en espera ingreso mercancia',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(25,7,9,0,0,'PASAR_BLOQUE','PASAR_BLOQUE a EN ESPERA EN FACTURACION',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(26,7,5,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(27,8,6,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a en espera ingreso mercancia',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(28,8,7,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a con ingreso mercancia',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(29,8,5,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(30,9,10,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a FACTURADA',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(31,9,8,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(32,10,9,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a EN ESPERA EN FACTURACION',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(33,10,12,0,0,'PASAR_BLOQUE','PASAR_BLOQUE a En espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(34,10,8,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(35,11,9,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a EN ESPERA EN FACTURACION',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(36,11,10,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a FACTURADA',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(37,11,8,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(38,12,13,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Firmada',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(39,12,11,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(40,13,12,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a En espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(41,13,15,0,0,'PASAR_BLOQUE','PASAR_BLOQUE a En espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(42,13,11,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(43,14,12,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a En espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(44,14,13,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Firmada',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(45,14,11,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(46,15,16,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Radicada',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(47,15,14,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(48,16,15,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a En espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(49,16,18,0,0,'PASAR_BLOQUE','PASAR_BLOQUE a Por Confirmar',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(50,16,14,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(51,17,15,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a En espera',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(52,17,16,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Radicada',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(53,17,14,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(54,18,19,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Finalizada',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(55,18,17,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(56,19,18,0,0,'CAMBIAR_ESTADO','CAMBIAR_ESTADO a Por Confirmar',1,'2026-03-05 16:27:09','2026-03-05 16:27:09'),(57,19,17,1,0,'DEVOLVER','DEVOLVER a devuelta',1,'2026-03-05 16:27:09','2026-03-05 16:27:09');

INSERT INTO migrations VALUES (1,'2024_01_01_000000_create_laravel_base_tables',1),(2,'2024_01_01_000001_create_roles_table',1),(3,'2024_01_01_000002_create_usuarios_table',1),(4,'2024_01_01_000003_create_catalogos_tables',1),(5,'2024_01_01_000004_create_actores_tables',1),(6,'2024_01_01_000005_create_contratos_tables',1),(7,'2024_01_01_000006_create_workflow_tables',1),(8,'2024_01_01_000007_create_cuentas_cobro_table',1),(9,'2024_01_01_000008_create_cuentas_relacionadas_tables',1),(10,'2024_01_01_000009_create_historial_workflow_table',1),(11,'2024_01_01_000010_create_auditoria_metricas_tables',1),(12,'2024_01_01_000011_create_seguimiento_normalized_tables',1),(13,'2026_03_05_132814_add_unique_index_to_alertas_table',2),(14,'2026_03_05_144224_add_performance_indexes_to_alertas_table',3),(15,'2026_03_05_152058_simplify_alertas_unique_index',4);

INSERT INTO sessions VALUES ('oXFHz2YZVgNxc3lDr1rOBXaVGLT999HTdEji71do',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZGE3VXFUZHRTZ3NBeEFFNEY5MnBIeEdiT1ZyRTJFN0hvQWVacFNxQiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb25maWd1cmFjaW9uL2F1ZGl0b3JpYSI7czo1OiJyb3V0ZSI7czoyOToiY29uZmlndXJhY2lvbi5hdWRpdG9yaWEuaW5kZXgiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1772744512);

-- Ajustar secuencias para que no colisionen con IDs insertados manualmente
SELECT setval('bloques_workflow_id_seq', (SELECT MAX(id) FROM bloques_workflow));
SELECT setval('estados_workflow_id_seq', (SELECT MAX(id) FROM estados_workflow));
SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles));
SELECT setval('permisos_id_seq', (SELECT MAX(id) FROM permisos));
SELECT setval('usuarios_id_seq', (SELECT MAX(id) FROM usuarios));
SELECT setval('configuraciones_id_seq', (SELECT MAX(id) FROM configuraciones));
SELECT setval('entidades_seguridad_social_id_seq', (SELECT MAX(id) FROM entidades_seguridad_social));
SELECT setval('transiciones_permitidas_id_seq', (SELECT MAX(id) FROM transiciones_permitidas));
SELECT setval('migrations_id_seq', (SELECT MAX(id) FROM migrations));
