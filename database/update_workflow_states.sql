-- Script para actualizar estados del workflow
-- Ejecutar en orden

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Limpiar estados y transiciones existentes
TRUNCATE TABLE transiciones_permitidas;
DELETE FROM estados_workflow;
DELETE FROM bloques_workflow;

-- 2. Actualizar/Crear Bloques
INSERT INTO bloques_workflow (id, nombre, codigo, orden, sla_horas, requiere_aprobacion, roles_permitidos, descripcion, icono, color_hex, es_activo, created_at, updated_at) VALUES
(1, 'ESTADO TRAS PRIMERA REVISIÓN', 'REV1', 1, NULL, false, NULL, 'Primera revisión de cuentas', 'fa-search', '#6f42c1', true, NOW(), NOW()),
(2, 'ENVIADA A INGRESO MERCANCIA SAP', 'SAP', 2, NULL, false, NULL, 'Ingreso en sistema SAP', 'fa-database', '#6610f2', true, NOW(), NOW()),
(3, 'EN FACTURACIÓN', 'FAC', 3, NULL, false, NULL, 'Proceso de facturación', 'fa-file-invoice', '#28a745', true, NOW(), NOW()),
(4, 'FIRMA SECRETARIO', 'FIR', 4, NULL, true, NULL, 'Firma del secretario', 'fa-signature', '#fd7e14', true, NOW(), NOW()),
(5, 'EN HACIENDA', 'HAC', 5, NULL, false, NULL, 'Radicada en hacienda', 'fa-landmark', '#e83e8c', true, NOW(), NOW()),
(6, 'FINALIZADA', 'FIN', 6, NULL, false, NULL, 'Proceso completado', 'fa-check-circle', '#17a2b8', true, NOW(), NOW());

-- 3. Crear Estados para cada Bloque

-- BLOQUE 1: ESTADO TRAS PRIMERA REVISIÓN
INSERT INTO estados_workflow (bloque_id, nombre, codigo, tipo, es_inicial, es_final, permite_devolucion, color_hex, descripcion, es_activo, created_at, updated_at) VALUES
(1, 'PASA', 'REV1_PASA', 'APROBADO', true, true, false, '#28a745', 'Cuenta aprobada', true, NOW(), NOW()),
(1, 'DEVUELTA', 'REV1_DEV', 'DEVUELTO', false, false, true, '#dc3545', 'Cuenta devuelta', true, NOW(), NOW()),
(1, 'EN ESPERA FIRMA MONCALEANO', 'REV1_ESP_MONC', 'EN_PROCESO', false, false, false, '#ffc107', 'Esperando firma Moncaleano', true, NOW(), NOW()),
(1, 'EN REVISION', 'REV1_REV', 'EN_PROCESO', false, false, false, '#17a2b8', 'En proceso de revisión', true, NOW(), NOW()),
(1, 'POR ADJUDICARSE', 'REV1_ADJ', 'EN_PROCESO', false, false, false, '#6c757d', 'Pendiente de adjudicación', true, NOW(), NOW()),
(1, 'CUENTAS POR PAGAR', 'REV1_CPP', 'EN_PROCESO', false, false, false, '#fd7e14', 'En cuentas por pagar', true, NOW(), NOW()),
(1, 'RESERVA', 'REV1_RES', 'EN_PROCESO', false, false, false, '#6f42c1', 'En reserva', true, NOW(), NOW());

-- BLOQUE 2: ENVIADA A INGRESO MERCANCIA SAP
INSERT INTO estados_workflow (bloque_id, nombre, codigo, tipo, es_inicial, es_final, permite_devolucion, color_hex, descripcion, es_activo, created_at, updated_at) VALUES
(2, 'DEVUELTA', 'SAP_DEV', 'DEVUELTO', false, false, true, '#dc3545', 'Devuelta de SAP', true, NOW(), NOW()),
(2, 'EN ESPERA', 'SAP_ESP', 'EN_PROCESO', true, false, false, '#ffc107', 'En espera de ingreso', true, NOW(), NOW()),
(2, 'CON INGRESO CORRECTO', 'SAP_OK', 'APROBADO', false, true, false, '#28a745', 'Ingreso correcto en SAP', true, NOW(), NOW());

-- BLOQUE 3: EN FACTURACIÓN
INSERT INTO estados_workflow (bloque_id, nombre, codigo, tipo, es_inicial, es_final, permite_devolucion, color_hex, descripcion, es_activo, created_at, updated_at) VALUES
(3, 'EN ESPERA', 'FAC_ESP', 'EN_PROCESO', true, false, false, '#ffc107', 'Esperando facturación', true, NOW(), NOW()),
(3, 'FACTURA', 'FAC_OK', 'APROBADO', false, true, false, '#28a745', 'Factura generada', true, NOW(), NOW()),
(3, 'DEVUELTA', 'FAC_DEV', 'DEVUELTO', false, false, true, '#dc3545', 'Devuelta de facturación', true, NOW(), NOW());

-- BLOQUE 4: FIRMA SECRETARIO
INSERT INTO estados_workflow (bloque_id, nombre, codigo, tipo, es_inicial, es_final, permite_devolucion, color_hex, descripcion, es_activo, created_at, updated_at) VALUES
(4, 'EN ESPERA', 'FIR_ESP', 'EN_PROCESO', true, false, false, '#ffc107', 'Esperando firma', true, NOW(), NOW()),
(4, 'FIRMADA', 'FIR_OK', 'APROBADO', false, true, false, '#28a745', 'Documento firmado', true, NOW(), NOW());

-- BLOQUE 5: EN HACIENDA
INSERT INTO estados_workflow (bloque_id, nombre, codigo, tipo, es_inicial, es_final, permite_devolucion, color_hex, descripcion, es_activo, created_at, updated_at) VALUES
(5, 'EN ESPERA', 'HAC_ESP', 'EN_PROCESO', true, false, false, '#ffc107', 'Esperando en hacienda', true, NOW(), NOW()),
(5, 'DEVUELTA', 'HAC_DEV', 'DEVUELTO', false, false, true, '#dc3545', 'Devuelta de hacienda', true, NOW(), NOW()),
(5, 'SI', 'HAC_OK', 'APROBADO', false, true, false, '#28a745', 'Aprobada en hacienda', true, NOW(), NOW());

-- BLOQUE 6: FINALIZADA
INSERT INTO estados_workflow (bloque_id, nombre, codigo, tipo, es_inicial, es_final, permite_devolucion, color_hex, descripcion, es_activo, created_at, updated_at) VALUES
(6, 'COMPLETADA', 'FIN_COMP', 'FINAL', true, true, false, '#17a2b8', 'Proceso completado', true, NOW(), NOW());

-- 4. Crear Transiciones Permitidas (ejemplo básico - ajustar según necesidad)

-- Bloque 1 transiciones
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'APROBAR', 'Transición dentro del bloque 1', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.bloque_id = 1 AND e2.bloque_id = 1 AND e1.id != e2.id;

-- Transición de Bloque 1 PASA a Bloque 2
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'PASAR_BLOQUE', 'Pasar a SAP', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'REV1_PASA' AND e2.codigo = 'SAP_ESP';

-- Bloque 2 transiciones internas
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'CAMBIAR_ESTADO', 'Transición dentro del bloque 2', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.bloque_id = 2 AND e2.bloque_id = 2 AND e1.id != e2.id;

-- Transición de Bloque 2 OK a Bloque 3
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'PASAR_BLOQUE', 'Pasar a Facturación', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'SAP_OK' AND e2.codigo = 'FAC_ESP';

-- Bloque 3 transiciones internas
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'CAMBIAR_ESTADO', 'Transición dentro del bloque 3', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.bloque_id = 3 AND e2.bloque_id = 3 AND e1.id != e2.id;

-- Transición de Bloque 3 FACTURA a Bloque 4
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'PASAR_BLOQUE', 'Pasar a Firma', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'FAC_OK' AND e2.codigo = 'FIR_ESP';

-- Bloque 4 transiciones internas
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'CAMBIAR_ESTADO', 'Transición dentro del bloque 4', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.bloque_id = 4 AND e2.bloque_id = 4 AND e1.id != e2.id;

-- Transición de Bloque 4 FIRMADA a Bloque 5
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'PASAR_BLOQUE', 'Pasar a Hacienda', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'FIR_OK' AND e2.codigo = 'HAC_ESP';

-- Bloque 5 transiciones internas
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'CAMBIAR_ESTADO', 'Transición dentro del bloque 5', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.bloque_id = 5 AND e2.bloque_id = 5 AND e1.id != e2.id;

-- Transición de Bloque 5 SI a Bloque 6
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, false, false, 'FINALIZAR', 'Finalizar proceso', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'HAC_OK' AND e2.codigo = 'FIN_COMP';

-- Transiciones de devolución (de estados DEVUELTA al bloque anterior)
-- Devolver de Bloque 2 a Bloque 1
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, true, false, 'DEVOLVER', 'Devolver a revisión', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'SAP_DEV' AND e2.bloque_id = 1;

-- Devolver de Bloque 3 a Bloque 2
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, true, false, 'DEVOLVER', 'Devolver a SAP', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'FAC_DEV' AND e2.bloque_id = 2;

-- Devolver de Bloque 5 a Bloque 4
INSERT INTO transiciones_permitidas (estado_origen_id, estado_destino_id, requiere_comentario, requiere_documento, accion, descripcion, es_activa, created_at, updated_at)
SELECT e1.id, e2.id, true, false, 'DEVOLVER', 'Devolver a firma', true, NOW(), NOW()
FROM estados_workflow e1
CROSS JOIN estados_workflow e2
WHERE e1.codigo = 'HAC_DEV' AND e2.bloque_id = 4;

-- 5. Asegurar que las cuentas existentes tengan estados válidos
-- Asignar el primer estado del primer bloque a cualquier cuenta con estado inválido
UPDATE cuentas_cobro 
SET estado_actual_id = (SELECT id FROM estados_workflow WHERE codigo = 'REV1_REV' LIMIT 1),
    bloque_actual_id = 1
WHERE estado_actual_id NOT IN (SELECT id FROM estados_workflow)
   OR bloque_actual_id NOT IN (SELECT id FROM bloques_workflow);

SET FOREIGN_KEY_CHECKS = 1;
