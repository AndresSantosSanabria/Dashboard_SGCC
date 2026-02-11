<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CuentaCobro;
use App\Models\Contrato;
use App\Models\Contratista;
use App\Models\Supervisor;
use App\Models\RegistroPresupuestal;
use App\Models\PlanillaSeguridadSocial;
use App\Models\BloqueWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\EstadoBloqueCuenta;
use App\Models\Modalidad;
use App\Models\Concepto;
use App\Models\Planta;
use App\Models\EntidadSeguridadSocial;
use App\Models\ContratistaSeguridadSocial;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CuentaCobroController extends Controller
{
    private $entidadesCache = [];
    private $blocksCache = [];
    private $statesCache = [];

    private function getBlockIdByCode(string $code): int
    {
        if (isset($this->blocksCache[$code])) return $this->blocksCache[$code];
        $id = BloqueWorkflow::where('codigo', $code)->value('id');
        if (!$id) throw new \Exception("Bloque con código '$code' no encontrado en la base de datos.");
        return $this->blocksCache[$code] = $id;
    }

    private function getStateIdByCode(string $code): int
    {
        if (isset($this->statesCache[$code])) return $this->statesCache[$code];
        $id = EstadoWorkflow::where('codigo', $code)->value('id');
        if (!$id) throw new \Exception("Estado con código '$code' no encontrado en la base de datos.");
        return $this->statesCache[$code] = $id;
    }
    public function index(Request $request)
    {
        $query = CuentaCobro::query()->with([
            'contrato.contratista.seguridadSocialVigente.entidadSalud',
            'contrato.contratista.seguridadSocialVigente.entidadPension',
            'contrato.contratista.seguridadSocialVigente.entidadArl',
            'contrato.supervisor',
            'contrato.registrosPresupuestales',
            'planillasSeguridadSocial' => function ($query) {
                $query->where('es_ultima', true);
            },
            'estadosBloques.bloque',
            'estadosBloques.responsable',
            'responsableActual',
            'estadoActual',
            'bloqueActual'
        ]);

        // Nivel 1: Barra de Búsqueda Superior
        if ($request->filled('searchContrato')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('numero_contrato', 'like', '%' . $request->searchContrato . '%');
            });
        }

        if ($request->filled('searchContratista')) {
            $query->whereHas('contrato.contratista', function ($q) use ($request) {
                $q->where('razon_social', 'like', '%' . $request->searchContratista . '%')
                    ->orWhere('representante_legal', 'like', '%' . $request->searchContratista . '%');
            });
        }

        if ($request->filled('searchCedula')) {
            $query->whereHas('contrato.contratista', function ($q) use ($request) {
                $q->where('nit', 'like', '%' . $request->searchCedula . '%');
            });
        }

        // Nivel 2: Panel de Filtros Avanzados
        if ($request->filled('filterContrato')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('numero_contrato', $request->filterContrato);
            });
        }

        if ($request->filled('filterSupervisor')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->filterSupervisor);
            });
        }

        if ($request->filled('filterEstadosRevision')) {
            $query->whereHas('estadosBloques', function ($q) use ($request) {
                $q->whereHas('bloque', function ($bq) {
                    $bq->where('codigo', 'REV1');
                })->whereIn('estado_actual_id', (array) $request->filterEstadosRevision);
            });
        }

        if ($request->filled('filterRadicadaHacienda')) {
            if ($request->filterRadicadaHacienda === 'SI') {
                $query->where('finalizada', true);
            } elseif ($request->filterRadicadaHacienda === 'NO') {
                $query->where('finalizada', false);
            }
        }

        if ($request->filled('filterEnFacturacion')) {
            $query->whereHas('estadosBloques', function ($q) use ($request) {
                $q->whereHas('bloque', function ($bq) {
                    $bq->where('codigo', 'FAC');
                });
                if ($request->filterEnFacturacion === 'SI') {
                    $q->whereNotNull('fecha_ingreso_bloque')->whereNull('fecha_completado_bloque');
                } else {
                    $q->whereNull('fecha_ingreso_bloque');
                }
            });
        }

        $cuentas = $query->latest()->paginate(20)->appends($request->all());

        $supervisores = Supervisor::orderBy('nombres')->get();
        $estadosRevision = EstadoWorkflow::whereHas('bloque', function ($q) {
            $q->where('codigo', 'REV1');
        })->get();

        // Para los dropdowns del modal de carga manual
        $todosLosEstados = EstadoWorkflow::where('es_activo', true)
            ->with('bloque')
            ->get()
            ->groupBy('bloque.codigo');

        if ($request->ajax()) {
            return response(view('partials.cuentas_table', compact('cuentas'))->render())
                ->header('X-Total-Count', $cuentas->total());
        }

        return view('dashboard', compact('cuentas', 'supervisores', 'estadosRevision', 'todosLosEstados'));
    }

    public function importExcel(Request $request)
    {
        try {
            $request->validate([
                'inputFile' => 'required|mimes:xlsx,xls,csv,xlsm|max:10240',
            ]);

            $file = $request->file('inputFile');
            $extension = $file->getClientOriginalExtension();
            $rows = SimpleExcelReader::create($file->getRealPath(), $extension)->getRows();

            if ($rows->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo Excel está vacío o no tiene el formato correcto.',
                ], 422);
            }

            $importCount = 0;
            $skippedCount = 0;
            $errors = [];
            $skippedReasons = [];

            $bloqueRad = BloqueWorkflow::where('codigo', 'REV1')->first();
            if (!$bloqueRad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración: No se encontró el bloque inicial (REV1).',
                ], 500);
            }

            $estadoRad = $bloqueRad->estadoInicial;
            if (!$estadoRad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración: No se encontró el estado inicial para el bloque RAD.',
                ], 500);
            }

            foreach ($rows as $index => $row) {
                $filaActual = $index + 1;
                Log::info("--- Procesando fila $filaActual ---", ['data' => $row]);

                try {
                    // Normalizar llaves a MAYÚSCULAS
                    $data = array_change_key_case($row, CASE_UPPER);

                    // 1. EXTRAER Y VALIDAR NÚMERO DE CONTRATO
                    $numContrato = $data['NUMERO DE CONTRATO'] ?? $data['N° CONTRATO'] ?? $data['CONTRATO'] ?? null;

                    if (empty($numContrato)) {
                        $msg = "Fila $filaActual saltada: El NÚMERO DE CONTRATO no se encontró o está vacío.";
                        Log::warning($msg);
                        $skippedCount++;
                        $skippedReasons[] = $msg;
                        continue;
                    }

                    // 2. VALIDAR UNICIDAD DEL CONTRATO (REGLA DE NEGOCIO: N° CONTRATO ÚNICO)
                    $contratoExistente = Contrato::where('numero_contrato', $numContrato)->first();
                    if ($contratoExistente) {
                        $msg = "Fila $filaActual saltada: El contrato '$numContrato' ya existe en el sistema.";
                        Log::info($msg);
                        $skippedCount++;
                        $skippedReasons[] = $msg;
                        continue;
                    }

                    DB::transaction(function () use ($data, &$importCount, $bloqueRad, $estadoRad, $filaActual, $numContrato) {

                        // 3. CONTRATISTA - NIT/CÉDULA
                        $nit = $data['CEDULA'] ?? $data['NIT'] ?? $data['DOCUMENTO'] ?? null;
                        if (empty($nit)) {
                            throw new \Exception("La cédula/NIT del contratista no se encontró. Verifique la columna 'CEDULA' o 'NIT'");
                        }

                        Log::info("Fila $filaActual: Creando/Buscando contratista NIT $nit");
                        $contratista = Contratista::firstOrCreate(
                            ['nit' => $nit],
                            [
                                'razon_social' => $data['CONTRATISTA'] ?? $data['NOMBRE'] ?? 'SIN NOMBRE',
                                'tipo_persona' => (strlen($nit) > 10) ? 'JURIDICA' : 'NATURAL',
                            ]
                        );

                        // 4. SUPERVISOR
                        $supervisorNombre = $data['SUPERVISOR'] ?? 'PENDIENTE';
                        $supervisor = Supervisor::firstOrCreate(
                            ['nombres' => $supervisorNombre, 'apellidos' => ''],
                            ['cargo' => 'SUPERVISOR']
                        );

                        // 5. CATÁLOGOS
                        $modalidad = Modalidad::firstOrCreate(['nombre' => $data['MODALIDAD'] ?? 'PRESTACIÓN DE SERVICIOS']);
                        $concepto = Concepto::firstOrCreate(['nombre' => $data['CONCEPTO'] ?? 'APOYO A LA GESTIÓN']);
                        $planta = Planta::firstOrCreate(['codigo' => 'P001'], ['nombre' => 'PLANTA CENTRAL']);

                        // 6. CREAR CONTRATO
                        Log::info("Fila $filaActual: Creando nuevo contrato $numContrato");
                        $contrato = Contrato::create([
                            'numero_contrato' => $numContrato,
                            'contratista_id' => $contratista->id,
                            'supervisor_id' => $supervisor->id,
                            'modalidad_id' => $modalidad->id,
                            'planta_id' => $planta->id,
                            'concepto_id' => $concepto->id,
                            'fecha_inicio' => $this->parseDate($data['FECHA DE INICIO'] ?? $data['FECHA INICIO'] ?? null),
                            'fecha_fin' => $this->parseDate($data['FECHA DE TERMINACIÓN'] ?? $data['FECHA FIN'] ?? null),
                            'monto_total' => $this->parseAmount($data['VALOR RP'] ?? $data['VALOR CONTRATO'] ?? 0),
                        ]);

                        // 7. REGISTRO PRESUPUESTAL
                        $rpNum = $data['RP'] ?? null;
                        if ($rpNum) {
                            RegistroPresupuestal::create([
                                'numero_rp' => $rpNum,
                                'contrato_id' => $contrato->id,
                                'fecha_rp' => $this->parseDate($data['FECHA RP'] ?? null),
                                'valor_rp' => $this->parseAmount($data['VALOR RP'] ?? 0),
                            ]);
                        }

                        // 8. SEGURIDAD SOCIAL
                        $this->crearSeguridadSocial($data, $contratista);

                        // 9. CUENTA DE COBRO
                        Log::info("Fila $filaActual: Creando cuenta de cobro");

                        $numeroCuenta = $data['NUMERO DE CUENTA EN PROCESO DE CUENTAS'] ?? $data['N° CUENTA'] ?? $data['NUMERO CUENTA'] ?? 1;
                        $valorRP = $this->parseAmount($data['VALOR RP'] ?? 0);
                        $pagosTotales = (int)($data['NUMERO DE PAGOS TOTALES'] ?? $data['TOTAL PAGOS'] ?? 12);
                        if ($pagosTotales <= 0) $pagosTotales = 12;

                        // Determinar bloque actual basado en datos históricos
                        $radHacienda = strtoupper($data['RADICADA EN HACIENDA'] ?? '');
                        $estaFinalizada = ($radHacienda === 'SI' || $radHacienda === 'SÍ');

                        $bloqueId = $this->getBlockIdByCode('REV1');
                        $estadoId = $this->getStateIdByCode('REV1_REV'); // EN REVISION

                        if ($estaFinalizada) {
                            $bloqueId = $this->getBlockIdByCode('FIN');
                            $estadoId = $this->getStateIdByCode('FIN_COMP');
                        } elseif (!empty($data['RADICADA EN HACIENDA']) && ($radHacienda === 'SI' || $radHacienda === 'SÍ')) {
                            $bloqueId = $this->getBlockIdByCode('HAC');
                            $estadoId = $this->getStateIdByCode('HAC_OK');
                        } elseif (!empty($data['FIRMA SECRETARIO'])) {
                            $bloqueId = $this->getBlockIdByCode('FIR');
                            $estadoId = $this->getStateIdByCode('FIR_ESP');
                        } elseif (!empty($data['EN FACTURACIÓN'])) {
                            $bloqueId = $this->getBlockIdByCode('FAC');
                            $estadoId = $this->getStateIdByCode('FAC_ESP');
                        } elseif (!empty($data['ENVIADA A INGRESO MERCANCIA SAP']) || !empty($data['ENVIADA SAP'])) {
                            $bloqueId = $this->getBlockIdByCode('SAP');
                            $estadoId = $this->getStateIdByCode('SAP_ESP');
                        }

                        $cuenta = CuentaCobro::create([
                            'contrato_id' => $contrato->id,
                            'numero_cuenta' => $numeroCuenta,
                            'valor_cobro' => $valorRP / $pagosTotales,
                            'fecha_radicacion' => $this->parseDate($data['FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES'] ?? $data['FECHA RADICACION'] ?? null) ?? now(),
                            'numero_pagos_totales' => $pagosTotales,
                            'numero_facturas_radicadas' => $data['N° DE FACTURAS RADICADA HACIENDA'] ?? $data['FACTURAS RADICADAS'] ?? 0,
                            'porcentaje_cuentas' => $data['PORCENTAJE DE CUENTAS'] ?? $data['% EJECUCIÓN'] ?? 0,
                            'radicado_por' => $data['RADICADO POR'] ?? Auth::user()->user ?? 'SISTEMA',
                            'bloque_actual_id' => $bloqueId,
                            'estado_actual_id' => $estadoId,
                            'responsable_actual_id' => Auth::id(),
                            'finalizada' => $estaFinalizada,
                            'ultima_factura_hacienda' => $data['ULTIMA FACTURA RADICADA HACIENDA'] ?? $data['RADICADA EN HACIENDA'] ?? null,
                            'fecha_radicacion_hacienda' => $this->parseDate($data['FECHA DE RADICACIÓN'] ?? null),
                            'observacion_hacienda' => $data['OBSERVACIÓN DEVOLUCIÓN HACIENDA'] ?? null,
                            'observaciones' => $data['OBSERVACIONES'] ?? null,
                        ]);

                        // 10. PROCESAR BLOQUES HISTÓRICOS
                        $this->procesarBloquesHistoricos($cuenta, $data);

                        // 11. PLANILLA SEGURIDAD SOCIAL
                        $mesPlanilla = $data['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA'] ?? $data['MES PLANILLA'] ?? null;
                        if ($mesPlanilla && !empty($mesPlanilla)) {
                            PlanillaSeguridadSocial::create([
                                'cuenta_cobro_id' => $cuenta->id,
                                'numero_planilla' => 'PLANILLA-' . $numeroCuenta . '-' . $filaActual,
                                'mes_planilla' => strtoupper($mesPlanilla),
                                'es_ultima' => true
                            ]);
                        }

                        $importCount++;
                    });
                } catch (\Exception $e) {
                    $msgError = "Fila $filaActual error: " . $e->getMessage();
                    Log::error($msgError);
                    $errors[] = $msgError;
                }
            }

            $summary = "Importación finalizada.\n" .
                "✅ Éxito: $importCount\n" .
                "⏭️ Saltados (Duplicados/Vacíos): $skippedCount\n" .
                "❌ Errores: " . count($errors);

            if (!empty($skippedReasons)) {
                Log::info("Resumen de filas saltadas:\n" . implode("\n", $skippedReasons));
            }

            if ($importCount === 0 && count($errors) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se pudo importar nada. Principales errores:\n" . implode("\n", array_slice($errors, 0, 3)),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $summary,
                'detalles' => [
                    'importados' => $importCount,
                    'saltados' => $skippedCount,
                    'errores' => count($errors),
                    'log_saltados' => array_slice($skippedReasons, 0, 10) // Enviar solo 10 para no saturar
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en importación de Excel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Error crítico: " . $e->getMessage()
            ], 500);
        }
    }

    public function storeManual(Request $request)
    {
        try {
            // Normalizar keys: PHP convierte espacios en guiones bajos (_) en los nombres de los campos
            $data = [];
            foreach ($request->all() as $key => $value) {
                $normalizedKey = str_replace('_', ' ', strtoupper($key));
                $data[$normalizedKey] = $value;
            }

            // 1. Validar obligatorio
            $numContrato = $data['NUMERO DE CONTRATO'] ?? null;
            if (empty($numContrato)) {
                return response()->json(['success' => false, 'message' => 'El Número de Contrato es obligatorio.'], 422);
            }

            // 2. Validar unicidad
            if (Contrato::where('numero_contrato', $numContrato)->exists()) {
                return response()->json(['success' => false, 'message' => "El contrato '$numContrato' ya existe."], 422);
            }

            // 3. Bloques y Estados
            $bloqueRad = BloqueWorkflow::where('codigo', 'REV1')->first();
            $estadoRad = $bloqueRad?->estadoInicial;

            if (!$bloqueRad || !$estadoRad) {
                return response()->json(['success' => false, 'message' => 'Error de configuración de workflow (REV1).'], 500);
            }

            DB::transaction(function () use ($data, $bloqueRad, $estadoRad, $numContrato) {
                // 1. Contratista
                $nit = $data['CEDULA'] ?? $data['NIT'] ?? '0';
                $contratista = Contratista::firstOrCreate(
                    ['nit' => $nit],
                    [
                        'razon_social' => $data['CONTRATISTA'] ?? 'SIN NOMBRE',
                        'tipo_persona' => (strlen($nit) > 10) ? 'JURIDICA' : 'NATURAL',
                    ]
                );

                // 2. Supervisor
                $supervisor = Supervisor::firstOrCreate(
                    ['nombres' => $data['SUPERVISOR'] ?? 'PENDIENTE', 'apellidos' => ''],
                    ['cargo' => 'SUPERVISOR']
                );

                // 3. Catálogos
                $modalidad = Modalidad::firstOrCreate(['nombre' => $data['MODALIDAD'] ?? 'PRESTACIÓN DE SERVICIOS']);
                $concepto = Concepto::firstOrCreate(['nombre' => $data['CONCEPTO'] ?? 'APOYO A LA GESTIÓN']);
                $planta = Planta::firstOrCreate(['codigo' => 'P001'], ['nombre' => 'PLANTA CENTRAL']);

                // 4. Contrato
                $contrato = Contrato::create([
                    'numero_contrato' => $numContrato,
                    'contratista_id' => $contratista->id,
                    'supervisor_id' => $supervisor->id,
                    'modalidad_id' => $modalidad->id,
                    'planta_id' => $planta->id,
                    'concepto_id' => $concepto->id,
                    'fecha_inicio' => $this->parseDate($data['FECHA DE INICIO'] ?? null),
                    'fecha_fin' => $this->parseDate($data['FECHA DE TERMINACIÓN'] ?? null),
                    'monto_total' => $this->parseAmount($data['VALOR RP'] ?? 0),
                    'es_activo' => true
                ]);

                // 5. Registro Presupuestal
                if (!empty($data['RP'])) {
                    RegistroPresupuestal::create([
                        'numero_rp' => $data['RP'],
                        'contrato_id' => $contrato->id,
                        'fecha_rp' => $this->parseDate($data['FECHA RP'] ?? null),
                        'valor_rp' => $this->parseAmount($data['VALOR RP'] ?? 0),
                    ]);
                }

                // 6. Seguridad Social
                $this->crearSeguridadSocial($data, $contratista);

                // 7. Cuenta de Cobro
                $numeroCuenta = $data['NUMERO DE CUENTA EN PROCESO DE CUENTAS'] ?? 1;
                $valorRP = $this->parseAmount($data['VALOR RP'] ?? 0);
                $pagosTotales = (int)($data['NUMERO DE PAGOS TOTALES'] ?? 12);
                if ($pagosTotales <= 0) $pagosTotales = 12;

                // Determinar bloque y estado actual basado en el formulario (de mayor a menor importancia)
                $bloqueId = $this->getBlockIdByCode('REV1');
                $estadoId = $this->getStateIdByCode('REV1_REV'); // Default inicial

                if (!empty($data['RADICADA EN HACIENDA'])) {
                    $bloqueId = $this->getBlockIdByCode('HAC');
                    $estadoId = EstadoWorkflow::where('nombre', $data['RADICADA EN HACIENDA'])
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('HAC_ESP');
                } elseif (!empty($data['FIRMA SECRETARIO'])) {
                    $bloqueId = $this->getBlockIdByCode('FIR');
                    $estadoId = EstadoWorkflow::where('nombre', $data['FIRMA SECRETARIO'])
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('FIR_ESP');
                } elseif (!empty($data['EN FACTURACIÓN'])) {
                    $bloqueId = $this->getBlockIdByCode('FAC');
                    $estadoId = EstadoWorkflow::where('nombre', $data['EN FACTURACIÓN'])
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('FAC_ESP');
                } elseif (!empty($data['ENVIADA A INGRESO MERCANCIA SAP'])) {
                    $bloqueId = $this->getBlockIdByCode('SAP');
                    $estadoId = EstadoWorkflow::where('nombre', $data['ENVIADA A INGRESO MERCANCIA SAP'])
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('SAP_ESP');
                } elseif (!empty($data['ESTADO TRAS PRIMERA REVISIÓN'])) {
                    $bloqueId = $this->getBlockIdByCode('REV1');
                    $estadoId = EstadoWorkflow::where('nombre', $data['ESTADO TRAS PRIMERA REVISIÓN'])
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('REV1_REV');
                }

                $estaFinalizada = ($bloqueId == $this->getBlockIdByCode('FIN') || ($bloqueId == $this->getBlockIdByCode('HAC') && ($data['RADICADA EN HACIENDA'] ?? '') === 'SI'));

                $cuenta = CuentaCobro::create([
                    'contrato_id' => $contrato->id,
                    'numero_cuenta' => $numeroCuenta,
                    'valor_cobro' => $valorRP / $pagosTotales,
                    'fecha_radicacion' => $this->parseDate($data['FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES'] ?? null) ?? now(),
                    'numero_pagos_totales' => $pagosTotales,
                    'numero_facturas_radicadas' => $data['N° DE FACTURAS RADICADA HACIENDA'] ?? 0,
                    'porcentaje_cuentas' => $data['PORCENTAJE DE CUENTAS'] ?? 0,
                    'radicado_por' => $data['RADICADO POR'] ?? Auth::user()->user ?? 'SISTEMA',
                    'bloque_actual_id' => $bloqueId,
                    'estado_actual_id' => $estadoId,
                    'responsable_actual_id' => Auth::id(),
                    'finalizada' => $estaFinalizada,
                    'observaciones' => $data['OBSERVACIONES'] ?? null,
                    'ultima_factura_hacienda' => $data['ULTIMA FACTURA RADICADA HACIENDA'] ?? $data['RADICADA EN HACIENDA'] ?? null,
                    'fecha_radicacion_hacienda' => $this->parseDate($data['FECHA DE RADICACIÓN'] ?? null),
                    'observacion_hacienda' => $data['OBSERVACIÓN DEVOLUCIÓN HACIENDA'] ?? null,
                ]);

                // 8. Procesar Bloques Históricos
                $this->procesarBloquesHistoricos($cuenta, $data);

                // 9. Planilla
                $mesPlanilla = $data['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA'] ?? null;
                if ($mesPlanilla) {
                    PlanillaSeguridadSocial::create([
                        'cuenta_cobro_id' => $cuenta->id,
                        'numero_planilla' => 'MANUAL-' . $numeroCuenta . '-' . uniqid(),
                        'mes_planilla' => strtoupper($mesPlanilla),
                        'es_ultima' => true
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Registro cargado correctamente a la base de datos.']);
        } catch (\Exception $e) {
            Log::error('Error en carga manual: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => "Error: " . $e->getMessage()], 500);
        }
    }

    public function exportTemplate()
    {
        $headers = [
            'NUMERO DE CONTRATO',
            'CONTRATISTA',
            'CEDULA',
            'RP',
            'FECHA RP',
            'VALOR RP',
            'FECHA DE INICIO',
            'FECHA DE TERMINACIÓN',
            'SUPERVISOR',
            'NUMERO DE CUENTA EN PROCESO DE CUENTAS',
            'NUMERO DE PAGOS TOTALES',
            'N° DE FACTURAS RADICADA HACIENDA',
            'PORCENTAJE DE CUENTAS',
            'ENTIDAD SALUD',
            'ENTIDAD PENSIÓN',
            'ENTIDAD ARL',
            'PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA',
            'RADICADO POR',
            'FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES',
            'OBSERVACIONES',
            'ESTADO TRAS PRIMERA REVISIÓN (SERGIO / CONSUELO)',
            'FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP',
            'ENVIADA A INGRESO MERCANCIA SAP',
            'RESPONSABLE',
            'FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES',
            'EN FACTURACIÓN',
            'RESPONSABLE',
            'FECHA EN QUE SE GENERA FACURACIÓN',
            'FIRMA SECRETARIO',
            'FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO',
            'RADICADA EN HACIENDA',
            'FECHA DE RADICACIÓN',
            'ULTIMA FACTURA RADICADA HACIENDA',
            'OBSERVACIÓN DEVOLUCIÓN HACIENDA',
            'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS'
        ];

        return SimpleExcelWriter::streamDownload('plantilla_cuentas_cobro.xlsx')
            ->noHeaderRow()
            ->addRow($headers)
            ->toBrowser();
    }

    private function parseDate($value)
    {
        if (!$value || $value === '0' || $value === 'N/A') return null;
        if ($value === '1') return now(); // Interpretar "1" como la fecha actual para no perder información de prueba
        try {
            // Si es un objeto ya (como Carbon o DateTime)
            if ($value instanceof \DateTimeInterface) return Carbon::instance($value);

            // Si es numérico y parece fecha Excel
            if (is_numeric($value) && $value > 40000 && $value < 60000) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Crea registros de EstadoBloqueCuenta para poblar el historial/estado actual
     * basado en los campos del Excel/Formulario Manual.
     */
    private function procesarBloquesHistoricos($cuenta, $data)
    {
        // 1. Bloque 1: ESTADO TRAS PRIMERA REVISIÓN (REV1)
        $estadoRevNombre = $data['ESTADO TRAS PRIMERA REVISIÓN'] ?? null;
        if ($estadoRevNombre && $estadoRevNombre != 'N/A') {
            $fechaRev = $this->parseDate($data['FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP'] ?? null);
            $bloqueId = $this->getBlockIdByCode('REV1');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoRevNombre%")->where('bloque_id', $bloqueId)->first();

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('REV1_REV'),
                    'fecha_ingreso_bloque' => $cuenta->fecha_radicacion,
                    'fecha_completado_bloque' => $fechaRev,
                    'bloque_completado' => !empty($fechaRev),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 2. Bloque 2: ENVIADA A INGRESO MERCANCIA SAP (SAP)
        $estadoSapNombre = $data['ENVIADA A INGRESO MERCANCIA SAP'] ?? $data['ENVIADA SAP'] ?? null;
        if ($estadoSapNombre && $estadoSapNombre != 'N/A') {
            $fechaSap = $this->parseDate($data['FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES'] ?? null);
            $bloqueId = $this->getBlockIdByCode('SAP');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoSapNombre%")->where('bloque_id', $bloqueId)->first();

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('SAP_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP'] ?? null),
                    'fecha_completado_bloque' => $fechaSap,
                    'bloque_completado' => !empty($fechaSap),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 3. Bloque 3: EN FACTURACIÓN (FAC)
        $estadoFacNombre = $data['EN FACTURACIÓN'] ?? null;
        if ($estadoFacNombre && $estadoFacNombre != 'N/A') {
            $fechaFac = $this->parseDate($data['FECHA EN QUE SE GENERA FACURACIÓN'] ?? null);
            $bloqueId = $this->getBlockIdByCode('FAC');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoFacNombre%")->where('bloque_id', $bloqueId)->first();

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('FAC_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES'] ?? null),
                    'fecha_completado_bloque' => $fechaFac,
                    'bloque_completado' => !empty($fechaFac),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 4. Bloque 4: FIRMA SECRETARIO (FIR)
        $estadoFirNombre = $data['FIRMA SECRETARIO'] ?? null;
        if ($estadoFirNombre && $estadoFirNombre != 'N/A') {
            $fechaFir = $this->parseDate($data['FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO'] ?? null);
            $bloqueId = $this->getBlockIdByCode('FIR');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoFirNombre%")->where('bloque_id', $bloqueId)->first();

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('FIR_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA EN QUE SE GENERA FACURACIÓN'] ?? null),
                    'fecha_completado_bloque' => $fechaFir,
                    'bloque_completado' => !empty($fechaFir),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 5. Bloque 5: EN HACIENDA (HAC)
        $estadoHacNombre = $data['RADICADA EN HACIENDA'] ?? null;
        if ($estadoHacNombre && $estadoHacNombre != 'N/A') {
            $fechaHac = $this->parseDate($data['FECHA DE RADICACIÓN'] ?? null);
            $bloqueId = $this->getBlockIdByCode('HAC');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoHacNombre%")->where('bloque_id', $bloqueId)->first();

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('HAC_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO'] ?? null),
                    'fecha_completado_bloque' => $fechaHac,
                    'bloque_completado' => !empty($fechaHac),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }
    }

    private function parseAmount($value)
    {
        return (float) str_replace([',', '$', ' '], '', $value);
    }

    private function obtenerOCrearEntidad(?string $nombre, string $tipo): ?int
    {
        if (empty($nombre)) return null;

        // Normalizar nombre
        $nombre = strtoupper(trim($nombre));

        // Filtrar casos especiales
        $especiales = ['NA', 'N/A', 'NINGUNA', 'REVISOR FISCAL', 'PARAFISCALES Y CONTADOR'];
        if (in_array($nombre, $especiales)) return null;

        $cacheKey = "$tipo:$nombre";

        // Revisar cache
        if (isset($this->entidadesCache[$cacheKey])) {
            return $this->entidadesCache[$cacheKey];
        }

        // Buscar o crear en BD
        $entidad = EntidadSeguridadSocial::firstOrCreate(
            ['nombre' => $nombre],
            ['tipo' => $tipo, 'es_activa' => true]
        );

        // Guardar en cache
        $this->entidadesCache[$cacheKey] = $entidad->id;

        return $entidad->id;
    }

    private function crearSeguridadSocial(array $datos, Contratista $contratista): void
    {
        $saludId = $this->obtenerOCrearEntidad($datos['ENTIDAD SALUD'] ?? null, 'SALUD');
        $pensionId = $this->obtenerOCrearEntidad($datos['ENTIDAD PENSIÓN'] ?? null, 'PENSION');
        $arlId = $this->obtenerOCrearEntidad($datos['ENTIDAD ARL'] ?? null, 'ARL');

        if ($saludId || $pensionId || $arlId) {
            ContratistaSeguridadSocial::updateOrCreate(
                ['contratista_id' => $contratista->id],
                [
                    'entidad_salud_id' => $saludId,
                    'entidad_pension_id' => $pensionId,
                    'entidad_arl_id' => $arlId,
                    'es_vigente' => true,
                ]
            );
        }
    }
}
