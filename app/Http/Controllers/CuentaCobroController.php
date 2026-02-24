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

class CuentaCobroController extends Controller
{
    private $bloquesCache = [];
    private $estadosCache = [];

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

    public function publicConsultation(Request $request)
    {
        $nit = $request->input('nit');

        if (!$nit) {
            return response()->json(['error' => 'NIT no proporcionado'], 400);
        }

        // Search for the account associated with the NIT via Contratista -> Contrato -> CuentaCobro
        $cuenta = CuentaCobro::whereHas('contrato.contratista', function ($query) use ($nit) {
            $query->where('nit', $nit);
        })
            ->with(['estadoActual', 'bloqueActual', 'contrato.contratista'])
            ->latest('updated_at')
            ->first();

        if (!$cuenta) {
            return response()->json(['error' => 'No se encontró ninguna cuenta asociada a este NIT/Cédula.'], 404);
        }

        return response()->json([
            'id' => $cuenta->id,
            'contratista' => $cuenta->contrato->contratista->razon_social,
            'numero_contrato' => $cuenta->contrato->numero_contrato,
            'numero_cuenta' => $cuenta->numero_cuenta,
            'estado' => $cuenta->estadoActual->nombre ?? 'N/A',
            'bloque' => $cuenta->bloqueActual->nombre ?? 'N/A',
            'ultima_actualizacion' => $cuenta->updated_at->format('d/m/Y H:i A'),
        ]);
    }

    /**
     * Get history filtered by current account cycle (Public version)
     * Shows ALL transitions of the current cycle (returns, block changes, etc.)
     * Only excludes the internal "Inicio manual del ciclo" marker event.
     */
    public function publicHistorial($cuentaId)
    {
        $cuenta = CuentaCobro::findOrFail($cuentaId);

        // The account's fecha_radicacion is reset each time a new cycle starts.
        // Use it as the lower bound to only show the current cycle's history.
        $fechaInicioActual = $cuenta->fecha_radicacion ?? $cuenta->created_at;

        $query = \App\Models\HistorialWorkflow::with([
            'bloque',
            'estadoOrigen',
            'estadoDestino',
            'usuarioAccion'
        ])
        ->where('cuenta_cobro_id', $cuentaId)
        // Only show events from the current cycle onwards
        ->where('fecha_transicion', '>=', $fechaInicioActual)
        // Exclude the internal cycle-start marker ("Inicio manual del ciclo - Cuenta #X")
        ->where(function($q) {
            $q->whereNull('comentarios')
              ->orWhere('comentarios', 'not like', 'Inicio manual del ciclo%');
        });

        $historial = $query->orderBy('fecha_transicion', 'asc')->get();

        return response()->json([
            'success' => true,
            'historial' => $historial,
            'tiempo_total' => $cuenta->tiempo_total_ejecucion,
            'fecha_inicio' => $historial->count() > 0 ? $historial->first()->fecha_transicion->format('d/m/Y H:i') : null
        ]);
    }


    private function getNextInvoiceNumber($contratoId)
    {
        // Obtener el máximo número de factura radicado para este contrato
        // Convertimos a entero para asegurar orden numérico correcto
        $maxInvoice = CuentaCobro::where('contrato_id', $contratoId)
            ->whereNotNull('ultima_factura_hacienda')
            ->where('ultima_factura_hacienda', '!=', 'N/A')
            ->selectRaw('MAX(CAST(ultima_factura_hacienda AS UNSIGNED)) as max_num')
            ->value('max_num');

        return ($maxInvoice ?? 0) + 1;
    }
    public function index(Request $request)
    {
        // Registrar lectura de dashboard (Auditoría)
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el dashboard de cuentas de cobro', 'cuentas_cobro');

        /** @var \App\Models\Usuario $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // 1. Check if user can access dashboard (Management) or Consolidado (Read-Only)
        $canManage = $user->puedeAccederDashboard();
        $canViewOnly = $user->puedeAccederConsolidado();

        $canViewOnly = $user->puedeAccederConsolidado();

        // DEBUG BLOCK
        if (!$canViewOnly && request()->has('debug')) {
            dd([
                'DEBUG INFO' => 'Access Denied',
                'User ID' => $user->id,
                'Role' => $user->rol ? $user->rol->toArray() : 'No Role',
                'User Permissions' => $user->permisos,
                'Is Admin?' => $user->isAdmin(),
                'Can Access Dashboard?' => $user->puedeAccederDashboard(),
                'Can Access Consolidated?' => $user->puedeAccederConsolidado(),
                'Has "acceder_dashboard"?' => $user->tienePermiso('acceder_dashboard'),
                'Has "acceder_consolidado"?' => $user->tienePermiso('acceder_consolidado'),
                'Role Permissions Raw' => $user->rol ? $user->rol->permisos : 'N/A',
            ]);
        }

        if (!$canViewOnly) {
            if ($user->puedeAccederWorkflow()) {
                return redirect()->route('workflow');
            }
            if ($user->isAdmin()) {
                return redirect()->route('configuracion.index');
            }
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $canEditDashboard = $user->tienePermiso('editar_dashboard');

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

        // 2. Filter by "Solo asignados" if applicable
        // 2. Filter by "Solo asignados" if applicable (STRICT: Only current responsibility)
        if ($user->verSoloAsignados()) {
            $query->where('responsable_actual_id', $user->id);
        }

        // 3. Filter by allowed blocks (Consistent with Workflow)
        $bloquesPermitidos = $user->bloquesPermitidos();
        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $query->whereIn('bloque_actual_id', function ($subQuery) use ($bloquesPermitidos) {
                $subQuery->select('id')
                    ->from('bloques_workflow')
                    ->whereIn('codigo', $bloquesPermitidos);
            });
        } elseif ($bloquesPermitidos !== true) {
            // If not true (all blocks) and not valid array, show no accounts
            $query->whereRaw('1 = 0');
        }

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

        if ($request->filled('numero_cuenta')) {
            $query->where('numero_cuenta', $request->numero_cuenta);
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
            return response(view('dashboard.componentes.cuentas_table', compact('cuentas', 'canManage', 'canEditDashboard'))->render())
                ->header('X-Total-Count', $cuentas->total());
        }

        return view('dashboard.dashboard', compact('cuentas', 'supervisores', 'estadosRevision', 'todosLosEstados', 'canManage', 'canEditDashboard'));
    }

    public function importExcel(Request $request)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (!$user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar importaciones.'], 403);
        }
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

                        // --- LÓGICA DEGuessing PARA EXCEL DESPLAZADO ---
                        $rawFechaRP = $data['FECHA RP'] ?? null;
                        $rawValorRP = $data['VALOR RP'] ?? $data['VALOR CONTRATO'] ?? null;
                        
                        $finalValorRP = $rawValorRP;
                        $finalFechaRP = $rawFechaRP;

                        // Si FECHA RP parece un precio (tiene $) y VALOR RP parece una fecha u vacío, intercambiamos
                        if (is_string($rawFechaRP) && str_contains($rawFechaRP, '$')) {
                            $finalValorRP = $rawFechaRP;
                            $finalFechaRP = (is_string($rawValorRP) && !str_contains($rawValorRP, '$')) ? $rawValorRP : null;
                        }

                        // 4. SUPERVISOR (Fallback si viene desplazado a FECHA DE TERMINACIÓN)
                        $supervisorNombre = $data['SUPERVISOR'] ?? '';
                        $fechaTerminacionRaw = $data['FECHA DE TERMINACIÓN'] ?? '';
                        
                        if ((empty($supervisorNombre) || $supervisorNombre == 'PENDIENTE') && !empty($fechaTerminacionRaw) && !is_numeric($fechaTerminacionRaw) && !str_contains($fechaTerminacionRaw, '-')) {
                            $supervisorNombre = $fechaTerminacionRaw;
                        }

                        $supervisor = Supervisor::firstOrCreate(
                            ['nombres' => $supervisorNombre ?: 'PENDIENTE', 'apellidos' => ''],
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
                            'fecha_inicio' => $this->parseDate($data['FECHA DE INICIO'] ?? null),
                            'fecha_fin' => $this->parseDate($data['FECHA DE TERMINACIÓN'] ?? null), // Solo si parece fecha
                            'monto_total' => $this->parseAmount($finalValorRP ?? 0),
                        ]);

                        // 7. REGISTRO PRESUPUESTAL
                        $rpNum = $data['RP'] ?? null;
                        if ($rpNum) {
                            RegistroPresupuestal::create([
                                'numero_rp' => $rpNum,
                                'contrato_id' => $contrato->id,
                                'fecha_rp' => $this->parseDate($finalFechaRP),
                                'valor_rp' => $this->parseAmount($finalValorRP ?? 0),
                            ]);
                        }

                        // 8. SEGURIDAD SOCIAL
                        $this->crearSeguridadSocial($data, $contratista);

                        // 9. CUENTA DE COBRO
                        Log::info("Fila $filaActual: Creando cuenta de cobro");

                        $numeroCuenta = $data['NUMERO DE CUENTA EN PROCESO DE CUENTAS'] ?? $data['N° CUENTA'] ?? $data['NUMERO CUENTA'] ?? 0;
                        if (empty($numeroCuenta) || $numeroCuenta == 0) $numeroCuenta = 1;
                        $valorRP = $this->parseAmount($finalValorRP ?? 0);
                        $pagosTotalesRaw = $data['NUMERO DE PAGOS TOTALES'] ?? $data['TOTAL PAGOS'] ?? null;
                        $pagosTotales = (!empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int)$pagosTotalesRaw : null;

                        // Determinar bloque actual basado en datos históricos
                        $radHacienda = strtoupper($data['RADICADA EN HACIENDA'] ?? '');
                        $estaFinalizada = ($radHacienda === 'SI' || $radHacienda === 'SÍ');

                        $bloqueId = $this->getBlockIdByCode('REV1');
                        $estadoId = $this->getStateIdByCode('REV1_SIN'); // DEFAULT: Sin tramite

                        if ($estaFinalizada) {
                            $bloqueId = $this->getBlockIdByCode('FIN');
                            $estadoId = $this->getStateIdByCode('FIN_OK');
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
                        } elseif (!empty($data['ESTADO TRAS PRIMERA REVISIÓN'])) {
                            // Si viene dato en la columna REV1, buscarlo
                            $estadoId = EstadoWorkflow::where('nombre', $data['ESTADO TRAS PRIMERA REVISIÓN'])
                                ->where('bloque_id', $bloqueId)
                                ->value('id') ?? $this->getStateIdByCode('REV1_SIN');
                        }

                        // --- DETECTAR DESPLAZAMIENTO DE COLUMNAS (SHIFT) ---
                        $valPorcentajeRaw = $data['N° DE FACTURAS RADICADA HACIENDA'] ?? $data['FACTURAS RADICADAS'] ?? '';
                        $isShifted = false;
                        
                        // Si en la columna de Facturas o Porcentaje viene texto (como SANITAS o REVISOR FISCAL)
                        $checkShift = $data['PORCENTAJE DE CUENTAS'] ?? '';
                        if (is_string($checkShift) && !empty($checkShift) && !is_numeric($checkShift) && !str_contains($checkShift, '%') && !str_contains($checkShift, ',')) {
                            $isShifted = true;
                        }

                        $facturasRadVal = $this->parseAmount($valPorcentajeRaw);
                        $pagosTotalesRaw = $data['NUMERO DE PAGOS TOTALES'] ?? $data['TOTAL PAGOS'] ?? null;
                        $pagosTotales = (!empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int)$pagosTotalesRaw : null;

                        $porcentajeCalculado = ($pagosTotales > 0) ? (($facturasRadVal / $pagosTotales) * 100) : 0;
                        if ($porcentajeCalculado > 100) $porcentajeCalculado = 100; // Cap a 100%

                        $fechaRadicacionExcel = $this->parseDate($data['FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES'] ?? $data['FECHA RADICACION'] ?? null);
                        $radicadoPor = $data['RADICADO POR'] ?? null;
                        
                        // Si está desplazado, reasignar campos de auditoría y fechas
                        if ($isShifted) {
                            $radicadoPor = $data['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA'] ?? $radicadoPor;
                            $fechaRadicacionExcel = $this->parseDate($data['RADICADO POR'] ?? null) ?: $fechaRadicacionExcel;
                        }

                        $cuenta = CuentaCobro::create([
                            'contrato_id' => $contrato->id,
                            'numero_cuenta' => $numeroCuenta,
                            'valor_cobro' => ($pagosTotales && $pagosTotales > 0) ? ($valorRP / $pagosTotales) : $valorRP,
                            'fecha_radicacion' => $fechaRadicacionExcel, 
                            'numero_pagos_totales' => $pagosTotales,
                            'numero_facturas_radicadas' => $facturasRadVal,

                            'porcentaje_cuentas' => $porcentajeCalculado,
                            'radicado_por' => $radicadoPor,
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
                        $mesPlanilla = $data['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA'] ?? $data['MES PLANILLA'] ?? 'RESERVA';
                        if ($isShifted) {
                            $mesPlanilla = $data['ENTIDAD ARL'] ?? 'RESERVA';
                        }

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

            // Registrar en auditoría el resumen de la importación
            Contrato::logManualAudit(null, 'IMPORT_EXCEL', $summary, 'cuentas_cobro');

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
            Contrato::logManualAudit(null, 'FAILURE_IMPORT_EXCEL', $e->getMessage(), 'cuentas_cobro');
            return response()->json([
                'success' => false,
                'message' => "Error crítico: " . $e->getMessage()
            ], 500);
        }
    }

    public function storeManual(Request $request)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (!$user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar cargas manuales.'], 403);
        }
        try {
            // Normalizar keys: PHP convierte espacios en guiones bajos (_) en los nombres de los campos
            $data = [];
            foreach ($request->all() as $key => $value) {
                $normalizedKey = str_replace('_', ' ', strtoupper($key));
                $data[$normalizedKey] = $value;
            }

            // 1. Validar obligatorios (Server-side)
            $requiredFields = [
                'NUMERO DE CONTRATO' => 'Número de Contrato',
            ];

            foreach ($requiredFields as $field => $label) {
                if (empty($data[$field])) {
                    return response()->json(['success' => false, 'message' => "El campo '$label' es obligatorio."], 422);
                }
            }

            $numContrato = $data['NUMERO DE CONTRATO'];

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
                $numeroCuenta = $data['NUMERO DE CUENTA EN PROCESO DE CUENTAS'] ?? 0;
                if (empty($numeroCuenta) || $numeroCuenta == 0) $numeroCuenta = 1;
                $valorRP = $this->parseAmount($data['VALOR RP'] ?? 0);
                $pagosTotalesRaw = $data['NUMERO DE PAGOS TOTALES'] ?? null;
                $pagosTotales = (!empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int)$pagosTotalesRaw : null;

                // Determinar bloque y estado actual basado en el formulario (de mayor a menor importancia)
                $bloqueId = $this->getBlockIdByCode('REV1');
                $estadoId = $this->getStateIdByCode('REV1_SIN'); // Default: Sin tramite

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
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('REV1_SIN');
                }

                $estaFinalizada = ($bloqueId == $this->getBlockIdByCode('FIN') || ($bloqueId == $this->getBlockIdByCode('HAC') && ($data['RADICADA EN HACIENDA'] ?? '') === 'SI'));

                // Calcular siguiente número de factura si está radicada en hacienda
                $ultimaFacturaHacienda = $data['ULTIMA FACTURA RADICADA HACIENDA'] ?? $data['RADICADA EN HACIENDA'] ?? null;
                $estadoRadicada = $data['RADICADA EN HACIENDA'] ?? '';

                if ($estadoRadicada === 'SI' || $estadoRadicada === 'Radicada' || $estaFinalizada) {
                    if (empty($ultimaFacturaHacienda) || $ultimaFacturaHacienda === 'SI' || $ultimaFacturaHacienda === 'Radicada' || $ultimaFacturaHacienda === 'N/A') {
                        $ultimaFacturaHacienda = $this->getNextInvoiceNumber($contrato->id);
                    }
                }

                $facturasRad = $this->parseAmount($ultimaFacturaHacienda ?? $data['N° DE FACTURAS RADICADA HACIENDA'] ?? 0);

                $cuenta = CuentaCobro::create([
                    'contrato_id' => $contrato->id,
                    'numero_cuenta' => $numeroCuenta,
                                        'valor_cobro' => ($pagosTotales && $pagosTotales > 0) ? ($valorRP / $pagosTotales) : $valorRP,

                    'fecha_radicacion' => $this->parseDate($data['FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES'] ?? null),
                    'numero_pagos_totales' => $pagosTotales,
                    'numero_facturas_radicadas' => $facturasRad,

                    'porcentaje_cuentas' => ($pagosTotales > 0) ? (($facturasRad / $pagosTotales) * 100) : 0,
                    'radicado_por' => $data['RADICADO POR'] ?? null,
                    'bloque_actual_id' => $bloqueId,
                    'estado_actual_id' => $estadoId,
                    'responsable_actual_id' => Auth::id(),
                    'finalizada' => $estaFinalizada,
                    'observaciones' => $data['OBSERVACIONES'] ?? null,
                    'ultima_factura_hacienda' => $ultimaFacturaHacienda,
                    'fecha_radicacion_hacienda' => $this->parseDate($data['FECHA DE RADICACIÓN'] ?? null),
                    'observacion_hacienda' => $data['OBSERVACIÓN DEVOLUCIÓN HACIENDA'] ?? null,
                ]);

                // 8. Procesar Bloques Históricos
                $this->procesarBloquesHistoricos($cuenta, $data);

                // 9. Planilla
                $mesPlanilla = $data['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA'] ?? 'RESERVA';
                if ($mesPlanilla) {
                    PlanillaSeguridadSocial::create([
                        'cuenta_cobro_id' => $cuenta->id,
                        'numero_planilla' => 'MANUAL-' . $numeroCuenta . '-' . uniqid(),
                        'mes_planilla' => strtoupper($mesPlanilla),
                        'es_ultima' => true
                    ]);
                }
            });

            // Registrar en auditoría la carga manual
            Contrato::logManualAudit(null, 'INSERT_MANUAL', "Carga manual exitosa del contrato " . ($numContrato ?? ''), 'cuentas_cobro');

            return response()->json(['success' => true, 'message' => 'Registro cargado correctamente a la base de datos.']);
        } catch (\Exception $e) {
            Log::error('Error en carga manual: ' . $e->getMessage());
            Contrato::logManualAudit(null, 'FAILURE_INSERT_MANUAL', $e->getMessage(), 'cuentas_cobro');
            return response()->json(['success' => false, 'message' => "Error: " . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $cuenta = CuentaCobro::with([
                'contrato.contratista',
                'contrato.supervisor',
                'contrato.registrosPresupuestales',
                'planillasSeguridadSocial' => fn($q) => $q->where('es_ultima', true),
                'contrato.contratista.seguridadSocialVigente.entidadSalud',
                'contrato.contratista.seguridadSocialVigente.entidadPension',
                'contrato.contratista.seguridadSocialVigente.entidadArl',
                'estadosBloques.bloque',
                'estadosBloques.estadoActual'
            ])->findOrFail($id);

            /** @var \App\Models\RegistroPresupuestal $rp */
            $rp = $cuenta->contrato->registrosPresupuestales->first();
            /** @var \App\Models\Contrato $contrato */
            $contrato = $cuenta->contrato;

            // Preparar datos para el formulario
            $data = [
                'NUMERO DE CONTRATO' => $contrato->numero_contrato,
                'CONTRATISTA' => $contrato->contratista->razon_social,
                'CEDULA' => $contrato->contratista->nit,
                'RP' => $rp?->numero_rp,
                'FECHA RP' => ($rp && $rp->fecha_rp instanceof \Carbon\Carbon) ? $rp->fecha_rp->format('Y-m-d') : null,
                'VALOR RP' => $rp?->valor_rp,
                'FECHA DE INICIO' => ($contrato->fecha_inicio instanceof \Carbon\Carbon) ? $contrato->fecha_inicio->format('Y-m-d') : null,
                'FECHA DE TERMINACIÓN' => ($contrato->fecha_fin instanceof \Carbon\Carbon) ? $contrato->fecha_fin->format('Y-m-d') : null,
                'SUPERVISOR' => $contrato->supervisor->nombres, // Asumiendo que solo se guardan nombres en este campo simple
                'NUMERO DE CUENTA EN PROCESO DE CUENTAS' => $cuenta->numero_cuenta,
                'NUMERO DE PAGOS TOTALES' => $cuenta->numero_pagos_totales,
                'N° DE FACTURAS RADICADA HACIENDA' => $cuenta->numero_facturas_radicadas,
                'PORCENTAJE DE CUENTAS' => $cuenta->porcentaje_cuentas,
                'ENTIDAD SALUD' => $cuenta->contrato->contratista->seguridadSocialVigente?->entidadSalud?->nombre,
                'ENTIDAD PENSIÓN' => $cuenta->contrato->contratista->seguridadSocialVigente?->entidadPension?->nombre,
                'ENTIDAD ARL' => $cuenta->contrato->contratista->seguridadSocialVigente?->entidadArl?->nombre,
                'PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA' => $cuenta->planillasSeguridadSocial->first()?->mes_planilla,
                'RADICADO POR' => $cuenta->radicado_por,
                'FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES' => $cuenta->fecha_radicacion?->format('Y-m-d'),
                'OBSERVACIONES' => $cuenta->observaciones,

                // Campos adicionales (Bloques)
                'ESTADO TRAS PRIMERA REVISIÓN' => $cuenta->estadosBloques->where('bloque.codigo', 'REV1')->first()?->estadoActual?->nombre,
                'FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP' => $cuenta->estadosBloques->where('bloque.codigo', 'REV1')->first()?->fecha_completado_bloque?->format('Y-m-d'),
                'RESPONSABLE_REV' => $cuenta->estadosBloques->where('bloque.codigo', 'REV1')->first()?->responsable?->primer_nombre, // Ojo con esto si es manual

                'ENVIADA A INGRESO MERCANCIA SAP' => $cuenta->estadosBloques->where('bloque.codigo', 'SAP')->first()?->estadoActual?->nombre,

                'FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES' => $cuenta->estadosBloques->where('bloque.codigo', 'FAC')->first()?->fecha_ingreso_bloque?->format('Y-m-d'),
                'EN FACTURACIÓN' => $cuenta->estadosBloques->where('bloque.codigo', 'FAC')->first()?->estadoActual?->nombre,
                'RESPONSABLE_FAC' => $cuenta->estadosBloques->where('bloque.codigo', 'FAC')->first()?->responsable?->primer_nombre,
                'FECHA EN QUE SE GENERA FACURACIÓN' => $cuenta->estadosBloques->where('bloque.codigo', 'FAC')->first()?->fecha_completado_bloque?->format('Y-m-d'),

                'FIRMA SECRETARIO' => $cuenta->estadosBloques->where('bloque.codigo', 'FIR')->first()?->estadoActual?->nombre,
                'FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO' => $cuenta->estadosBloques->where('bloque.codigo', 'FIR')->first()?->fecha_completado_bloque?->format('Y-m-d'),

                'RADICADA EN HACIENDA' => $cuenta->estadosBloques->where('bloque.codigo', 'HAC')->first()?->estadoActual?->nombre,
                'FECHA DE RADICACIÓN' => $cuenta->fecha_radicacion_hacienda?->format('Y-m-d'),
                'ULTIMA FACTURA RADICADA HACIENDA' => $cuenta->ultima_factura_hacienda,
                'OBSERVACIÓN DEVOLUCIÓN HACIENDA' => $cuenta->observacion_hacienda,
                'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS' => $cuenta->diferencia_cuentas,
            ];

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al cargar datos: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $cuenta = CuentaCobro::findOrFail($id);

            // Normalizar keys
            $data = [];
            foreach ($request->all() as $key => $value) {
                $normalizedKey = str_replace('_', ' ', strtoupper($key));
                $data[$normalizedKey] = $value;
            }

            DB::transaction(function () use ($data, $cuenta) {
                // 1. Actualizar Contrato (SOLO CAMPOS PERMITIDOS, NO EL NUMERO DE CONTRATO)
                // El usuario pidió NO modificar el número de contrato.
                $contrato = $cuenta->contrato;
                $contrato->update([
                    'fecha_inicio' => $this->parseDate($data['FECHA DE INICIO'] ?? null),
                    'fecha_fin' => $this->parseDate($data['FECHA DE TERMINACIÓN'] ?? null),
                    'monto_total' => $this->parseAmount($data['VALOR RP'] ?? 0),
                ]);

                // 2. Actualizar Contratista
                if (!empty($data['CONTRATISTA'])) {
                    $contrato->contratista->update(['razon_social' => $data['CONTRATISTA']]);
                }
                // (Nota: Si cambia la cédula, debería buscar/crear otro contratista, pero por simplicidad de edición asumimos actualización de datos del mismo)
                if (!empty($data['CEDULA'])) {
                    $contrato->contratista->update(['nit' => $data['CEDULA']]);
                }

                // 3. Supervisor
                if (!empty($data['SUPERVISOR'])) {
                    $supervisor = Supervisor::firstOrCreate(
                        ['nombres' => $data['SUPERVISOR'], 'apellidos' => ''],
                        ['cargo' => 'SUPERVISOR']
                    );
                    $contrato->update(['supervisor_id' => $supervisor->id]);
                }

                // 4. RP
                if ($rp = $contrato->registrosPresupuestales->first()) {
                    $rp->update([
                        'numero_rp' => $data['RP'] ?? $rp->numero_rp,
                        'fecha_rp' => $this->parseDate($data['FECHA RP'] ?? null),
                        'valor_rp' => $this->parseAmount($data['VALOR RP'] ?? 0),
                    ]);
                }

                // 5. Seguridad Social
                $this->crearSeguridadSocial($data, $contrato->contratista);

                // 6. Cuenta de Cobro
                $valorRP = $this->parseAmount($data['VALOR RP'] ?? 0);
                $pagosTotalesRaw = $data['NUMERO DE PAGOS TOTALES'] ?? null;
                $pagosTotales = (!empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int)$pagosTotalesRaw : null;

                // Preservar numero_facturas_radicadas actual: NO sobrescribir con el valor del formulario
                $facturasRadicadasActual = $cuenta->numero_facturas_radicadas ?? 0;

                $newNumCuenta = $data['NUMERO DE CUENTA EN PROCESO DE CUENTAS'] ?? $cuenta->numero_cuenta;
                if (empty($newNumCuenta) || $newNumCuenta == 0) $newNumCuenta = 1;

                $cuenta->update([
                    'numero_cuenta' => $newNumCuenta,
                                        'valor_cobro' => ($pagosTotales && $pagosTotales > 0) ? ($valorRP / $pagosTotales) : $valorRP,

                    'fecha_radicacion' => $this->parseDate($data['FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES'] ?? null) ?? $cuenta->fecha_radicacion,
                    'numero_pagos_totales' => $pagosTotales,
                    'numero_facturas_radicadas' => $facturasRadicadasActual,

                    'porcentaje_cuentas' => ($pagosTotales > 0) ? (($facturasRadicadasActual / $pagosTotales) * 100) : 0,
                    'radicado_por' => $data['RADICADO POR'] ?? $cuenta->radicado_por,
                    'observaciones' => $data['OBSERVACIONES'] ?? null,
                    'ultima_factura_hacienda' => $data['ULTIMA FACTURA RADICADA HACIENDA'] ?? null,
                    'fecha_radicacion_hacienda' => $this->parseDate($data['FECHA DE RADICACIÓN'] ?? null),
                    'observacion_hacienda' => $data['OBSERVACIÓN DEVOLUCIÓN HACIENDA'] ?? null,
                    'diferencia_cuentas' => $data['DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS'] ?? 0,
                ]);

                // Lógica especial para actualizar ultima_factura_hacienda si cambia estado a Radicada
                $estadoRadicada = $data['RADICADA EN HACIENDA'] ?? '';
                Log::info("Checking Radicada State: '$estadoRadicada'");

                if (strtoupper($estadoRadicada) === 'RADICADA' || strtoupper($estadoRadicada) === 'SI') {
                    // Verificar si ya tiene número asignado o si tiene un placeholder, asignar el siguiente
                    $currentFactura = $cuenta->ultima_factura_hacienda;
                    Log::info("Current Factura: '$currentFactura'");

                    if (empty($currentFactura) || $currentFactura === 'N/A' || $currentFactura === 'SI' || $currentFactura === 'Radicada') {
                        $nextNum = $this->getNextInvoiceNumber($cuenta->contrato_id);
                        Log::info("Generating Next Num: $nextNum");

                        // Solo asignar número de factura, NO incrementar facturas_radicadas aquí
                        // El incremento de facturas_radicadas ocurre al FINALIZAR el ciclo en el workflow
                        $cuenta->update([
                            'ultima_factura_hacienda' => $nextNum,
                        ]);
                    } else {
                        Log::info("Skipping generation: Current factura is present and valid.");
                    }
                }

                // 7. Bloques Históricos (Actualizar estados si cambiaron)
                $this->procesarBloquesHistoricos($cuenta, $data);

                // 8. Planilla (Si cambia)
                $mesPlanilla = $data['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA'] ?? null;
                if ($mesPlanilla) {
                    PlanillaSeguridadSocial::updateOrCreate(
                        ['cuenta_cobro_id' => $cuenta->id, 'es_ultima' => true],
                        ['mes_planilla' => strtoupper($mesPlanilla)]
                    );
                }
            });

            return response()->json(['success' => true, 'message' => 'Registro actualizado correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error actualizando registro: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
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
        if (!$value || $value === '0' || $value === 'N/A' || $value === '') return null;
        if ($value === '1') return now();

        // Si es un objeto ya (como Carbon o DateTime)
        if ($value instanceof \DateTimeInterface) return Carbon::instance($value);

        if (is_string($value)) {
            $value = trim($value);
            // Si parece un monto (contiene $) no es una fecha válida
            if (str_contains($value, '$')) return null;
            // Si es algo como "15 MESES" tampoco es fecha
            if (preg_match('/[0-9]+\s*MESES/i', $value)) return null;
        }

        try {
            // Si es numérico y parece fecha Excel (días desde 1900-01-01)
            if (is_numeric($value) && $value > 40000 && $value < 60000) {
                return Carbon::create(1899, 12, 30)->addDays((int)$value);
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
                    'fecha_ingreso_bloque' => $cuenta->fecha_radicacion ?? $cuenta->updated_at ?? now(),
                    'fecha_completado_bloque' => $fechaRev,
                    'fecha_ultima_actualizacion' => now(),
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
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP'] ?? null) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaSap,
                    'fecha_ultima_actualizacion' => now(),
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
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES'] ?? null) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaFac,
                    'fecha_ultima_actualizacion' => now(),
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
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA EN QUE SE GENERA FACURACIÓN'] ?? null) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaFir,
                    'fecha_ultima_actualizacion' => now(),
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
                    'fecha_ingreso_bloque' => $this->parseDate($data['FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO'] ?? null) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaHac,
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => !empty($fechaHac),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // ASEGURAR QUE EL BLOQUE ACTUAL TENGA UN REGISTRO (Para el cronómetro en el Dashboard)
        if ($cuenta->bloque_actual_id) {
            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $cuenta->bloque_actual_id],
                [
                    'estado_actual_id' => $cuenta->estado_actual_id,
                    'fecha_ingreso_bloque' => now(), // Empieza sumando desde 0 al momento de la creación manual
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => $cuenta->finalizada,
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }
    }

    private function parseAmount($value)
    {
        if ($value instanceof \DateTimeInterface) return 0.0;
        if (is_numeric($value)) return (float) $value;
        if (empty($value)) return 0.0;

        // Limpiar caracteres comunes
        $clean = str_replace(['$', ' ', '%'], '', (string)$value);

        // Si no hay números, retornar 0
        if (!preg_match('/[0-9]/', $clean)) return 0.0;

        // Caso especial: $80.591.600 (Puntos como miles)
        // Si hay múltiples puntos, o si hay un punto y luego una coma
        $dots = substr_count($clean, '.');
        $commas = substr_count($clean, ',');

        if ($dots > 1) {
            $clean = str_replace('.', '', $clean);
        }

        if ($commas > 0) {
            // Si tiene coma, asumimos que es el decimal (formato latino)
            // A menos que tenga un punto después de la coma (raro)
            if ($dots > 0 && strrpos($clean, '.') < strrpos($clean, ',')) {
                $clean = str_replace('.', '', $clean);
            }
            $clean = str_replace(',', '.', $clean);
        }

        // Si después de limpiar múltiples puntos aún queda uno,
        // verificamos si es decimal o miles. En este contexto ($80.591.600),
        // si termina en .XXX es muy probable que sean miles si el número es grande.
        // Pero para ser conservadores, solo removemos si count > 1.
        // Si el usuario tiene "28.577.658.777", todos los puntos se irán. Correcto.

        return (float) $clean;
    }

    private function obtenerOCrearEntidad(?string $nombre, string $tipo): ?int
    {
        if (empty($nombre)) return null;

        // Normalizar nombre
        $nombre = strtoupper(trim($nombre));

        // Filtrar casos especiales
        $especiales = ['NA', 'N/A', 'NINGUNA', 'REVISOR FISCAL', 'PARAFISCALES Y CONTADOR', 'SIN DATO', '0'];
        if (in_array($nombre, $especiales)) return null;

        // Buscar o crear en BD (Sin cache global para evitar errores en transacciones fallidas)
        $entidad = EntidadSeguridadSocial::firstOrCreate(
            ['nombre' => $nombre],
            ['tipo' => $tipo, 'es_activa' => true]
        );

        return $entidad->id;
    }

    private function crearSeguridadSocial(array $datos, Contratista $contratista): void
    {
        // Detect shifted mapping
        $isShifted = false;
        $valCheck = $datos['PORCENTAJE DE CUENTAS'] ?? '';
        if (is_string($valCheck) && !empty($valCheck) && !is_numeric($valCheck) && !str_contains($valCheck, '%')) {
            $isShifted = true;
        }

        if ($isShifted) {
            $salud = $datos['PORCENTAJE DE CUENTAS'] ?? null;
            $pension = $datos['ENTIDAD SALUD'] ?? null;
            $arl = $datos['ENTIDAD PENSIÓN'] ?? null;
        } else {
            $salud = $datos['ENTIDAD SALUD'] ?? null;
            $pension = $datos['ENTIDAD PENSIÓN'] ?? null;
            $arl = $datos['ENTIDAD ARL'] ?? null;
        }

        $saludId = $this->obtenerOCrearEntidad($salud, 'SALUD');
        $pensionId = $this->obtenerOCrearEntidad($pension, 'PENSION');
        $arlId = $this->obtenerOCrearEntidad($arl, 'ARL');

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
