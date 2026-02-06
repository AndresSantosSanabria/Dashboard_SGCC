<?php

namespace App\Imports;

use App\Models\CuentaCobro;
use App\Models\Contrato;
use App\Models\Contratista;
use App\Models\Supervisor;
use App\Models\EstadoWorkflow;
use App\Models\TransicionEstado;
use App\Models\Usuario;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class CuentasImport implements ToCollection, WithCalculatedFormulas
{
    // Variables para contar registros
    public $totalFilas = 0;
    public $filasExitosas = 0;
    public $filasFallidas = 0;
    public $erroresDetalles = [];
    
    // Cache en memoria para evitar queries repetidas
    private $cacheContratistas = [];
    private $cacheSupervisores = [];
    private $cacheContratos = [];
    
    /**
     * Procesa la colección completa del Excel
     * NO usa WithHeadingRow para evitar problemas con columnas duplicadas
     */
    public function collection(Collection $rows)
    {
        // La primera fila son los encabezados
        $headers = $rows->first();
        
        Log::info('Encabezados RAW del Excel', [
            'total_columnas' => $headers->count(),
            'columnas' => $headers->toArray()
        ]);
        
        // Procesar cada fila (saltando la primera que es header)
        foreach ($rows->skip(1) as $index => $row) {
            $this->totalFilas++;
            
            try {
                $this->procesarFila($row, $index + 2); // +2 porque: +1 por skip, +1 por header
            } catch (Exception $e) {
                $this->filasFallidas++;
                
                Log::error('Error al importar fila', [
                    'fila_numero' => $index + 2,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->erroresDetalles[] = [
                    'fila' => $index + 2,
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    /**
     * Procesa una fila individual del Excel
     * Accede por índice numérico para evitar problemas con columnas duplicadas
     */
    private function procesarFila($row, $numeroFila)
    {
        // Mapear valores por índice CORREGIDO según Excel de 35 columnas
        $data = [
            'numero_de_contrato' => $row[0] ?? null,
            'contratista' => $row[1] ?? null,
            'cedula' => $row[2] ?? null,
            'rp' => $row[3] ?? null,
            'fecha_rp' => $row[4] ?? null,
            'valor_rp' => $row[5] ?? null,
            'fecha_de_inicio' => $row[6] ?? null,
            'fecha_de_terminacion' => $row[7] ?? null,
            'supervisor' => $row[8] ?? null,
            'numero_de_cuenta_en_proceso_de_cuentas' => $row[9] ?? null,
            'numero_de_pagos_totales' => $row[10] ?? null,
            'n_de_facturas_radicada_hacienda' => $row[11] ?? null,
            '_de_cuentas' => $row[12] ?? null,
            'entidad_salud' => $row[13] ?? null,
            'entidad_pension' => $row[14] ?? null,
            'entidad_arl' => $row[15] ?? null,
            'mes_planilla_seguridad_social_ultima_cuenta' => $row[16] ?? null,
            'radicado_por' => $row[17] ?? null,
            'fecha_de_radicacion_tanto_inicial_como_sus_correciones' => $row[18] ?? null,
            'observaciones' => $row[19] ?? null,
            'estado_tras_primera_revision' => $row[20] ?? null,
            'fecha_devuelta_revision_o_enviada_a_sap' => $row[21] ?? null,
            'enviada_a_ingreso_mercancia_sap' => $row[22] ?? null,
            
            // ✅ PRIMER RESPONSABLE (columna 24 en Excel - índice 23)
            'responsable' => $row[23] ?? null,
            
            // ✅ FECHA ENVÍO A FACTURACIÓN (columna 25 en Excel - índice 24)
            'fecha_envio_a_facturacion_o_devuelta' => $row[24] ?? null,
            
            // ✅ EN FACTURACIÓN (columna 26 en Excel - índice 25)
            'en_facturacion' => $row[25] ?? null,
            
            // ✅ SEGUNDO RESPONSABLE - FACTURACIÓN (columna 27 en Excel - índice 26)
            'responsable_facturacion' => $row[26] ?? null,
            
            // ✅ FECHA GENERACIÓN FACTURACIÓN (columna 28 en Excel - índice 27)
            'fecha_en_que_se_genera_facuracion' => $row[27] ?? null,
            
            // ✅ FIRMA SECRETARIO (columna 29 en Excel - índice 28)
            'firma_secretario' => $row[28] ?? null,
            
            // ✅ FECHA FIRMA SECRETARIO (columna 30 en Excel - índice 29)
            'fecha_en_que_se_dejan_para_firma_del_secretario' => $row[29] ?? null,
            
            'radicada_en_hacienda' => $row[30] ?? null,
            'fecha_de_radicacion_hacienda' => $row[31] ?? null,
            'ultima_factura_radicada_hacienda' => $row[32] ?? null,
            'observacion_devolucion_hacienda' => $row[33] ?? null,
            'diferencia_cuentas_totales_vs_cuentas_radicadas' => $row[34] ?? null,
        ];
        
        // Log de verificación para cada fila
        Log::info('Verificación de mapeo - Fila ' . $numeroFila, [
            'responsable' => $data['responsable'],
            'fecha_facturacion' => $data['fecha_envio_a_facturacion_o_devuelta'],
            'en_facturacion' => $data['en_facturacion'],
            'responsable_facturacion' => $data['responsable_facturacion'],
            'fecha_generacion' => $data['fecha_en_que_se_genera_facuracion'],
            'firma_secretario' => $data['firma_secretario'],
            'fecha_firma' => $data['fecha_en_que_se_dejan_para_firma_del_secretario'],
        ]);
        
        return DB::transaction(function () use ($data, $numeroFila) {
            
            // PASO 1: BUSCAR O CREAR CONTRATISTA
            if (empty($data['cedula'])) {
                Log::warning('Fila sin cédula', ['fila' => $numeroFila]);
                return null;
            }

            $cedulaNit = $data['cedula'];
            if (!isset($this->cacheContratistas[$cedulaNit])) {
                $contratista = Contratista::firstOrCreate(
                    ['nit' => $cedulaNit],
                    [
                        'razon_social' => $data['contratista'] ?? null,
                        'entidad_salud' => $data['entidad_salud'] ?? null,
                        'entidad_pension' => $data['entidad_pension'] ?? null,
                        'entidad_arl' => $data['entidad_arl'] ?? null,
                    ]
                );

                if (!$contratista->wasRecentlyCreated) {
                    $contratista->update([
                        'entidad_salud' => $data['entidad_salud'] ?? $contratista->entidad_salud,
                        'entidad_pension' => $data['entidad_pension'] ?? $contratista->entidad_pension,
                        'entidad_arl' => $data['entidad_arl'] ?? $contratista->entidad_arl,
                    ]);
                }
                
                $this->cacheContratistas[$cedulaNit] = $contratista;
            } else {
                $contratista = $this->cacheContratistas[$cedulaNit];
            }

            // PASO 2: BUSCAR O CREAR SUPERVISOR
            $supervisor = null;
            if (!empty($data['supervisor'])) {
                $nombreCompleto = trim($data['supervisor']);
                
                if (!isset($this->cacheSupervisores[$nombreCompleto])) {
                    $partes = explode(' ', $nombreCompleto, 2);
                    $nombres = $partes[0] ?? '';
                    $apellidos = $partes[1] ?? '';

                    $supervisor = Supervisor::firstOrCreate(
                        [
                            'nombres' => $nombres,
                            'apellidos' => $apellidos
                        ],
                        ['es_activo' => true]
                    );
                    $this->cacheSupervisores[$nombreCompleto] = $supervisor;
                } else {
                    $supervisor = $this->cacheSupervisores[$nombreCompleto];
                }
            }

            // PASO 3: BUSCAR O CREAR CONTRATO
            if (empty($data['numero_de_contrato'])) {
                Log::warning('Fila sin número de contrato', ['fila' => $numeroFila]);
                return null;
            }

            $numeroContrato = $data['numero_de_contrato'];
            if (!isset($this->cacheContratos[$numeroContrato])) {
                $contratoData = [
                    'numero_contrato' => $numeroContrato,
                ];

                $contratoDataUpdate = [
                    'contratista_id' => $contratista->id,
                    'supervisor_id' => $supervisor?->id,
                    'rp' => $data['rp'] ?? null,
                    'fecha_rp' => $this->parseDate($data['fecha_rp'] ?? null),
                    'valor_rp' => $this->parseDecimal($data['valor_rp'] ?? null),
                    'fecha_inicio' => $this->parseDate($data['fecha_de_inicio'] ?? null),
                    'fecha_fin' => $this->parseDate($data['fecha_de_terminacion'] ?? null),
                ];

                $contrato = Contrato::firstOrCreate(
                    $contratoData,
                    array_merge($contratoDataUpdate, ['monto_total' => 0])
                );

                if (!$contrato->wasRecentlyCreated) {
                    $contrato->update($contratoDataUpdate);
                }
                
                $this->cacheContratos[$numeroContrato] = $contrato;
            } else {
                $contrato = $this->cacheContratos[$numeroContrato];
            }

            // PASO 4: CREAR O ACTUALIZAR CUENTA DE COBRO
            if (empty($data['numero_de_cuenta_en_proceso_de_cuentas'])) {
                Log::warning('Fila sin número de cuenta', ['fila' => $numeroFila]);
                return null;
            }

            $cuentaData = [
                'contrato_id' => $contrato->id,
                'numero_cuenta' => $data['numero_de_cuenta_en_proceso_de_cuentas'],
                'valor_cobro' => $this->parseDecimal($data['valor_rp'] ?? 0),
                'fecha_radicacion' => $this->parseDateTime($data['fecha_de_radicacion_tanto_inicial_como_sus_correciones'] ?? null),
                'numero_pagos_totales' => $this->parseInteger($data['numero_de_pagos_totales'] ?? 0),
                'numero_facturas_radicadas' => $this->parseInteger($data['n_de_facturas_radicada_hacienda'] ?? 0),
                'porcentaje_cuentas' => $this->parseDecimal($data['_de_cuentas'] ?? 0),
                'mes_planilla_seguridad_social' => $data['mes_planilla_seguridad_social_ultima_cuenta'] ?? null,
                'radicado_por' => $data['radicado_por'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'diferencia_cuentas' => $this->parseInteger($data['diferencia_cuentas_totales_vs_cuentas_radicadas'] ?? 0),
                'ultima_factura_hacienda' => $data['ultima_factura_radicada_hacienda'] ?? null,
                'finalizada' => $this->parseBoolean($data['radicada_en_hacienda'] ?? 'No'),
            ];

            $numOriginal = (string) $cuentaData['numero_cuenta'];
            $existingGlobal = CuentaCobro::where('numero_cuenta', $numOriginal)->first();
            if ($existingGlobal && $existingGlobal->contrato_id != $contrato->id) {
                $cuentaData['numero_cuenta'] = $numOriginal . '-' . $contrato->id;
            }

            $cuentaCobro = CuentaCobro::where('contrato_id', $contrato->id)
                ->where('numero_cuenta', $cuentaData['numero_cuenta'])
                ->first();

            if ($cuentaCobro) {
                $cuentaCobro->update($cuentaData);
            } else {
                $cuentaCobro = CuentaCobro::create($cuentaData);
            }

            // PROCESAR ESTADOS Y TRANSICIONES
            if (!empty($data['estado_tras_primera_revision'])) {
                $valorEstado = trim((string) $data['estado_tras_primera_revision']);

                $estado = EstadoWorkflow::whereRaw('LOWER(nombre) = ?', [strtolower($valorEstado)])
                    ->orWhere('nombre', 'LIKE', "%{$valorEstado}%")
                    ->first();

                if (!$estado) {
                    $codigo = Str::upper(Str::slug($valorEstado, '_'));
                    $estado = EstadoWorkflow::create([
                        'bloque_id' => null,
                        'nombre' => $valorEstado,
                        'codigo' => $codigo,
                        'tipo' => null,
                        'es_inicial' => false,
                        'es_final' => false,
                        'permite_rechazo' => false,
                        'color_hex' => null,
                    ]);
                }

                $existe = $cuentaCobro->transiciones()
                    ->where('estado_destino_id', $estado->id)
                    ->exists();

                if (!$existe) {
                    $fechaTrans = $this->parseDateTime($data['fecha_devuelta_revision_o_enviada_a_sap'] ?? null) ?? now()->format('Y-m-d H:i:s');

                    TransicionEstado::create([
                        'cuenta_cobro_id' => $cuentaCobro->id,
                        'estado_origen_id' => null,
                        'estado_destino_id' => $estado->id,
                        'usuario_accion_id' => null,
                        'comentarios' => 'Importado desde Excel: estado tras primera revisión',
                        'accion' => 'import',
                        'created_at' => $fechaTrans,
                        'updated_at' => $fechaTrans,
                    ]);
                }
            }

            // Procesar otros estados
            $this->procesarOtrosEstadosYResponsables($cuentaCobro, $data);

            $this->filasExitosas++;
            return $cuentaCobro;
        });
    }
    
    /**
     * Obtener resumen de la importación
     */
    public function getResumen()
    {
        return [
            'total' => $this->totalFilas,
            'exitosas' => $this->filasExitosas,
            'fallidas' => $this->filasFallidas,
            'errores' => $this->erroresDetalles
        ];
    }

    private function findUsuarioByName($nombre)
    {
        if (empty($nombre)) return null;

        $nombre = trim((string)$nombre);

        $user = Usuario::where('usuario', $nombre)->first();
        if ($user) return $user;

        $user = Usuario::whereRaw("CONCAT_WS(' ', primer_nombre, segundo_nombre, primer_apellido, segundo_apellido) LIKE ?", ["%{$nombre}%"])->first();
        if ($user) return $user;

        $parts = preg_split('/\s+/', $nombre);
        if (count($parts) >= 2) {
            $first = $parts[0];
            $last = end($parts);
            $user = Usuario::where('primer_nombre', 'LIKE', "%{$first}%")
                ->where('primer_apellido', 'LIKE', "%{$last}%")
                ->first();
            if ($user) return $user;
        }

        $user = Usuario::where('primer_nombre', 'LIKE', "%{$nombre}%")
            ->orWhere('primer_apellido', 'LIKE', "%{$nombre}%")
            ->first();
        if ($user) return $user;

        return null;
    }

    private function procesarOtrosEstadosYResponsables($cuentaCobro, $data)
    {
        // INGRESO SAP - usa primer RESPONSABLE (índice 23)
        if (!empty($data['enviada_a_ingreso_mercancia_sap'])) {
            $valor = trim((string)$data['enviada_a_ingreso_mercancia_sap']);
            $fecha = $this->parseDateTime($data['fecha_devuelta_revision_o_enviada_a_sap'] ?? null) ?? null;
            $responsable = $data['responsable'] ?? null;
            
            Log::info('Procesando INGRESO SAP', [
                'estado' => $valor,
                'responsable' => $responsable,
                'fecha' => $fecha
            ]);
            
            $this->crearTransicionConResponsableSiNoExiste($cuentaCobro, $valor, $fecha, $responsable, 'import - ingreso');
        }

        // FACTURACIÓN - usa SEGUNDO RESPONSABLE (índice 26)
        if (!empty($data['en_facturacion'])) {
            $valor = trim((string)$data['en_facturacion']);
            $fecha = $this->parseDateTime($data['fecha_envio_a_facturacion_o_devuelta'] ?? null) ?? null;
            $responsable = $data['responsable_facturacion'] ?? $data['responsable'] ?? null;
            
            Log::info('Procesando FACTURACIÓN', [
                'estado' => $valor,
                'responsable_facturacion' => $responsable,
                'fecha' => $fecha
            ]);
            
            $this->crearTransicionConResponsableSiNoExiste($cuentaCobro, $valor, $fecha, $responsable, 'import - facturacion');
        }

        // FIRMA SECRETARIO - usa SEGUNDO RESPONSABLE (índice 26)
        if (!empty($data['firma_secretario'])) {
            $valor = trim((string)$data['firma_secretario']);
            $fecha = $this->parseDateTime($data['fecha_en_que_se_dejan_para_firma_del_secretario'] ?? null) ?? null;
            $responsable = $data['responsable_facturacion'] ?? $data['responsable'] ?? null;
            
            Log::info('Procesando FIRMA', [
                'estado' => $valor,
                'responsable_facturacion' => $responsable,
                'fecha' => $fecha
            ]);
            
            $this->crearTransicionConResponsableSiNoExiste($cuentaCobro, $valor, $fecha, $responsable, 'import - firma');
        }
    }

    private function crearTransicionConResponsableSiNoExiste($cuentaCobro, $valorEstado, $fechaTrans = null, $nombreResponsable = null, $comentarios = '')
    {
        if (empty($valorEstado)) return;

        $estado = EstadoWorkflow::whereRaw('LOWER(nombre) = ?', [strtolower($valorEstado)])
            ->orWhere('nombre', 'LIKE', "%{$valorEstado}%")
            ->first();

        if (!$estado) {
            $codigo = Str::upper(Str::slug($valorEstado, '_'));
            $estado = EstadoWorkflow::create([
                'bloque_id' => null,
                'nombre' => $valorEstado,
                'codigo' => $codigo,
                'tipo' => null,
                'es_inicial' => false,
                'es_final' => false,
                'permite_rechazo' => false,
                'color_hex' => null,
            ]);
        }

        $existe = $cuentaCobro->transiciones()->where('estado_destino_id', $estado->id)->exists();
        if ($existe) return;

        $fecha = $fechaTrans ?? now()->format('Y-m-d H:i:s');
        $usuario = $this->findUsuarioByName($nombreResponsable);
        $usuarioId = $usuario?->id ?? null;

        $trans = TransicionEstado::create([
            'cuenta_cobro_id' => $cuentaCobro->id,
            'estado_origen_id' => null,
            'estado_destino_id' => $estado->id,
            'usuario_accion_id' => $usuarioId,
            'comentarios' => 'Importado desde Excel: ' . $comentarios,
            'accion' => 'import',
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);

        Log::info('Transición creada', [
            'estado' => $estado->nombre,
            'usuario' => $usuario?->usuario ?? 'Sin asignar',
            'nombre_responsable' => $nombreResponsable
        ]);
    }

    // Funciones de parseo
    
    private function parseDate($date)
    {
        if (empty($date)) return null;

        try {
            if ($date instanceof \DateTime || $date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
            
            if (is_string($date)) {
                $date = str_replace('/', '-', trim($date));
                $timestamp = strtotime($date);
                if ($timestamp === false) return null;
                return date('Y-m-d', $timestamp);
            }

            if (is_numeric($date)) {
                $excelEpoch = new \DateTime('1899-12-30');
                $excelEpoch->modify("+{$date} days");
                return $excelEpoch->format('Y-m-d');
            }

            return null;
        } catch (Exception $e) {
            Log::warning('Error parseando fecha', ['date' => $date]);
            return null;
        }
    }

    private function parseDateTime($datetime)
    {
        if (empty($datetime)) return null;

        try {
            if ($datetime instanceof \DateTime || $datetime instanceof \Carbon\Carbon) {
                return $datetime->format('Y-m-d H:i:s');
            }
            
            if (is_string($datetime)) {
                $datetime = trim($datetime);
                $hasTime = preg_match('/\d{1,2}:\d{1,2}/', $datetime);
                $datetime = str_replace('/', '-', $datetime);
                $timestamp = strtotime($datetime);
                
                if ($timestamp === false) return null;
                
                if (!$hasTime) {
                    return date('Y-m-d 00:00:00', $timestamp);
                }
                
                return date('Y-m-d H:i:s', $timestamp);
            }

            if (is_numeric($datetime)) {
                $excelEpoch = new \DateTime('1899-12-30');
                $days = floor($datetime);
                $timeFraction = $datetime - $days;
                
                $excelEpoch->modify("+{$days} days");
                $seconds = round($timeFraction * 86400);
                $excelEpoch->modify("+{$seconds} seconds");
                
                return $excelEpoch->format('Y-m-d H:i:s');
            }

            return null;
            
        } catch (Exception $e) {
            Log::warning('Error parseando fecha/hora', ['datetime' => $datetime]);
            return null;
        }
    }

    private function parseDecimal($value)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float) $value;

        $v = trim((string) $value);
        $v = str_replace(['$', '€', '%', 'USD', 'COP', ' '], '', $v);

        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            if (strrpos($v, ',') > strrpos($v, '.')) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } else {
                $v = str_replace(',', '', $v);
            }
        } else {
            $commaCount = substr_count($v, ',');
            $dotCount = substr_count($v, '.');

            if ($commaCount > 1) {
                $v = str_replace(',', '', $v);
            } elseif ($commaCount === 1 && preg_match('/,\d{3}$/', $v)) {
                $v = str_replace(',', '', $v);
            } else {
                $v = str_replace(',', '.', $v);
            }

            if ($dotCount > 1) {
                $v = str_replace('.', '', $v);
            }
        }

        $v = preg_replace('/[^0-9\.\-]/', '', $v);
        return is_numeric($v) ? (float) $v : null;
    }

    private function parseBoolean($value)
    {
        if ($value === null || $value === '') return false;
        $value = strtolower(trim((string)$value));
        return in_array($value, ['si', 'sí', 'yes', '1', 'true', 'verdadero']);
    }

    private function parseInteger($value)
    {
        if ($value === null || $value === '') return 0;
        $digits = preg_replace('/[^\d\-]/', '', (string)$value);
        return is_numeric($digits) ? (int) $digits : 0;
    }
}