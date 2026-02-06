<?php

namespace App\Imports;

use App\Models\CuentaCobro;
use App\Models\Contrato;
use App\Models\Contratista;
use App\Models\Supervisor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Exception;

class CuentasImport implements ToModel, WithHeadingRow
{
    /**
     * Mapea cada fila del Excel a un modelo de CuentaCobro
     * 
     * Esta función se ejecuta por cada fila del archivo Excel (excepto la fila de encabezados).
     * Los encabezados del Excel se convierten automáticamente a claves del array $row.
     * 
     * IMPORTANTE: WithHeadingRow convierte los encabezados a snake_case y minúsculas.
     * Por ejemplo: "NUMERO DE CONTRATO" se convierte en "numero_de_contrato"
     * 
     * @param array $row Fila del Excel con los datos
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // ========================================
            // PASO 1: BUSCAR O CREAR CONTRATISTA
            // ========================================

            // Verificar que existe el NIT/Cédula del contratista
            if (empty($row['cedula'])) {
                Log::warning('Fila sin cédula de contratista', ['row' => $row]);
                return null;
            }

            // Buscar contratista por NIT/Cédula, o crear uno nuevo
            $contratista = Contratista::firstOrCreate(
                // Condición de búsqueda: busca por el NIT
                ['nit' => $row['cedula']],

                // Datos a crear si no existe
                [
                    'razon_social' => $row['contratista'] ?? null,
                    'entidad_salud' => $row['entidad_salud'] ?? null,
                    'entidad_pension' => $row['entidad_pension'] ?? null,
                    'entidad_arl' => $row['entidad_arl'] ?? null,
                ]
            );

            // Si el contratista ya existía, actualizar las entidades de seguridad social
            // (pueden cambiar con el tiempo)
            if (!$contratista->wasRecentlyCreated) {
                $contratista->update([
                    'entidad_salud' => $row['entidad_salud'] ?? $contratista->entidad_salud,
                    'entidad_pension' => $row['entidad_pension'] ?? $contratista->entidad_pension,
                    'entidad_arl' => $row['entidad_arl'] ?? $contratista->entidad_arl,
                ]);
            }

            // ========================================
            // PASO 2: BUSCAR O CREAR SUPERVISOR
            // ========================================

            $supervisor = null;
            if (!empty($row['supervisor'])) {
                // Intentar separar nombre y apellido del supervisor
                // Asumimos formato: "Nombre Apellido"
                $nombreCompleto = trim($row['supervisor']);
                $partes = explode(' ', $nombreCompleto, 2);

                $nombres = $partes[0] ?? '';
                $apellidos = $partes[1] ?? '';

                // Buscar supervisor por nombres y apellidos
                $supervisor = Supervisor::firstOrCreate(
                    [
                        'nombres' => $nombres,
                        'apellidos' => $apellidos
                    ],
                    [
                        'es_activo' => true
                    ]
                );
            }

            // ========================================
            // PASO 3: BUSCAR O CREAR CONTRATO
            // ========================================

            // Verificar que existe el número de contrato
            if (empty($row['numero_de_contrato'])) {
                Log::warning('Fila sin número de contrato', ['row' => $row]);
                return null;
            }

            // Preparar datos del contrato
            $contratoData = [
                'numero_contrato' => $row['numero_de_contrato'],
            ];

            // Datos adicionales del contrato (si vienen en el Excel)
            $contratoDataUpdate = [
                'contratista_id' => $contratista->id,
                'supervisor_id' => $supervisor?->id,
                'rp' => $row['rp'] ?? null,
                'fecha_rp' => $this->parseDate($row['fecha_rp'] ?? null),
                'valor_rp' => $this->parseDecimal($row['valor_rp'] ?? null),
                'fecha_inicio' => $this->parseDate($row['fecha_de_inicio'] ?? null),
                'fecha_fin' => $this->parseDate($row['fecha_de_terminacion'] ?? null),
            ];

            // Buscar o crear el contrato
            $contrato = Contrato::firstOrCreate(
                $contratoData,
                array_merge($contratoDataUpdate, ['monto_total' => 0]) // monto_total es requerido
            );

            // Actualizar datos del contrato si ya existía
            if (!$contrato->wasRecentlyCreated) {
                $contrato->update($contratoDataUpdate);
            }

            // ========================================
            // PASO 4: CREAR O ACTUALIZAR CUENTA DE COBRO
            // ========================================

            // Verificar que existe el número de cuenta
            if (empty($row['numero_de_cuenta_en_proceso_de_cuentas'])) {
                Log::warning('Fila sin número de cuenta', ['row' => $row]);
                return null;
            }

            // Preparar los datos de la cuenta de cobro
            $cuentaData = [
                'contrato_id' => $contrato->id,
                'numero_cuenta' => $row['numero_de_cuenta_en_proceso_de_cuentas'],
                'valor_cobro' => $this->parseDecimal($row['valor_rp'] ?? 0),
                'fecha_radicacion' => $this->parseDateTime($row['fecha_de_radicacion_tanto_inicial_como_sus_correciones'] ?? null),
                'numero_pagos_totales' => (int) ($row['numero_de_pagos_totales'] ?? 0),
                'numero_facturas_radicadas' => (int) ($row['n_de_facturas_radicada_hacienda'] ?? 0),
                'porcentaje_cuentas' => $this->parseDecimal($row['_de_cuentas'] ?? 0), // "_de_cuentas" porque % se convierte
                'mes_planilla_seguridad_social' => $row['mes_planilla_seguridad_social_ultima_cuenta'] ?? null,
                'radicado_por' => $row['radicado_por'] ?? null,
                'observaciones' => $row['observaciones'] ?? null,

                // Nuevos campos agregados
                'diferencia_cuentas' => (int) ($row['diferencia_cuentas_totales_vs_cuentas_radicadas'] ?? 0),
                'ultima_factura_hacienda' => $row['ultima_factura_radicada_hacienda'] ?? null,

                // Estado de finalización según si está radicada en hacienda
                'finalizada' => $this->parseBoolean($row['radicada_en_hacienda'] ?? 'No'),
            ];

            // Buscar si ya existe la cuenta de cobro por su número único
            $cuentaCobro = CuentaCobro::where('numero_cuenta', $cuentaData['numero_cuenta'])->first();

            if ($cuentaCobro) {
                // Si existe, actualizar los datos
                $cuentaCobro->update($cuentaData);
                Log::info('Cuenta de cobro actualizada', ['numero_cuenta' => $cuentaData['numero_cuenta']]);
            } else {
                // Si no existe, crear una nueva
                $cuentaCobro = CuentaCobro::create($cuentaData);
                Log::info('Cuenta de cobro creada', ['numero_cuenta' => $cuentaData['numero_cuenta']]);
            }

            // ========================================
            // NOTA: CAMPOS DE WORKFLOW
            // ========================================

            /*
             * Los siguientes campos del Excel se manejan a través del sistema de workflow:
             * - ESTADO TRAS PRIMERA REVISIÓN
             * - FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP
             * - ENVIADA A INGRESO MERCANCIA SAP
             * - RESPONSABLE (facturación)
             * - FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES
             * - EN FACTURACIÓN
             * - FECHA EN QUE SE GENERA FACURACIÓN
             * - FIRMA SECRETARIO
             * - FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO
             * - RADICADA EN HACIENDA
             * - FECHA DE RADICACIÓN (hacienda)
             * - OBSERVACIÓN DEVOLUCIÓN HACIENDA
             * 
             * Estos datos se guardarán en las tablas:
             * - estado_workflow: Define los estados posibles
             * - transicion_estado: Guarda el historial de cambios de estado
             * 
             * TODO: Implementar la creación de transiciones de estado según los datos del Excel
             * Por ahora solo creamos/actualizamos la cuenta de cobro con los datos básicos.
             */

            return $cuentaCobro;
        } catch (Exception $e) {
            // Si hay cualquier error, registrarlo y continuar con la siguiente fila
            Log::error('Error al importar fila de cuenta de cobro', [
                'error' => $e->getMessage(),
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Convierte una fecha en formato string a formato DATE de MySQL
     * 
     * @param string|null $date
     * @return string|null Fecha en formato Y-m-d o null
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Intentar parsear diferentes formatos de fecha
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return null;
            }

            return date('Y-m-d', $timestamp);
        } catch (Exception $e) {
            Log::warning('Error parseando fecha', ['date' => $date, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convierte una fecha/hora en formato string a formato DATETIME de MySQL
     * 
     * @param string|null $datetime
     * @return string|null Fecha/hora en formato Y-m-d H:i:s o null
     */
    private function parseDateTime($datetime)
    {
        if (empty($datetime)) {
            return null;
        }

        try {
            $timestamp = strtotime($datetime);
            if ($timestamp === false) {
                return null;
            }

            return date('Y-m-d H:i:s', $timestamp);
        } catch (Exception $e) {
            Log::warning('Error parseando fecha/hora', ['datetime' => $datetime, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convierte un valor decimal del Excel a formato numérico
     * 
     * @param mixed $value
     * @return float|null
     */
    private function parseDecimal($value)
    {
        if (empty($value)) {
            return null;
        }

        // Remover separadores de miles y convertir coma decimal a punto
        $cleaned = str_replace([',', ' '], ['', ''], $value);
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Convierte valores Si/No del Excel a boolean
     * 
     * @param string|null $value
     * @return bool
     */
    private function parseBoolean($value)
    {
        if (empty($value)) {
            return false;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['si', 'sí', 'yes', '1', 'true', 'verdadero']);
    }
}
