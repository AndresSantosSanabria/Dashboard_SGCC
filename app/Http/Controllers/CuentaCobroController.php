<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\BloqueWorkflow;
use App\Models\Concepto;
use App\Models\Contratista;
use App\Models\ContratistaSeguridadSocial;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\EntidadSeguridadSocial;
use App\Models\EstadoBloqueCuenta;
use App\Models\EstadoWorkflow;
use App\Models\HistorialWorkflow;
use App\Models\Modalidad;
use App\Models\PlanillaSeguridadSocial;
use App\Models\Planta;
use App\Models\RegistroPresupuestal;
use App\Models\Supervisor;
use App\Models\Usuario;
use App\Http\Requests\DashboardRequest;
use App\Services\ImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CuentaCobroController extends Controller
{
    /**
     * CENTRAL DE OPERACIONES - SGCC
     * 
     * Este controlador es la columna vertebral operativa del sistema. 
     * Gestiona el ciclo de vida completo de una Cuenta de Cobro, 
     * desde su radicación (vía Excel o Manual) hasta su finalización contable.
     */
    /** @var array<string, int> */
    private $blocksCache = [];

    /** @var array<string, int> */
    private $statesCache = [];

    /**
     * Optimizador de Consultas: Cacheamos IDs de bloques/estados para evitar 
     * consultas redundantes a la BD durante importaciones masivas.
     */
    private function getBlockIdByCode(string $code): int
    {
        if (isset($this->blocksCache[$code])) return $this->blocksCache[$code];
        return $this->blocksCache[$code] = BloqueWorkflow::where('codigo', $code)->value('id');
    }

    private function getStateIdByCode(string $code): ?int
    {
        if (isset($this->statesCache[$code])) return $this->statesCache[$code];
        return $this->statesCache[$code] = EstadoWorkflow::where('codigo', $code)->value('id');
    }

    /**
     * Devuelve el listado de cuentas asociadas a un contrato para la consulta pública.
     * El criterio público prioriza cuentas activas y recientes, ordenadas por fecha de actualización.
     */
    private function buildPublicAccountsPayload(Contrato $contrato): array
    {
        $cuentas = $contrato->cuentasCobro()
            ->with([
                'estadoActual',
                'bloqueActual',
                'responsableActual',
                'historialWorkflow.estadoDestino',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (CuentaCobro $cuenta) {
                // Excluir cuentas que aún están en "Sin Trámite" — no han iniciado ciclo
                $estadoNombre = strtolower($cuenta->estadoActual?->nombre ?? '');
                return ! str_contains($estadoNombre, 'sin trámite')
                    && ! str_contains($estadoNombre, 'sin tramite');
            })
            ->values()
            ->map(function (CuentaCobro $cuenta) {
                // La fecha de inicio real es cuando la cuenta salió de "Sin Trámite"
                // (primer registro en historial_workflow donde estado_destino NO es sin trámite)
                $historialCuenta = $cuenta->historialWorkflow ?? collect();
                $primerMovimiento = $historialCuenta
                    ->sortBy('fecha_transicion')
                    ->first(function ($h) {
                        $nombreDestino = strtolower($h->estadoDestino?->nombre ?? '');
                        return ! str_contains($nombreDestino, 'sin trámite')
                            && ! str_contains($nombreDestino, 'sin tramite');
                    });

                $fechaInicio = $primerMovimiento
                    ? $primerMovimiento->fecha_transicion
                    : ($cuenta->fecha_radicacion ?? $cuenta->created_at ?? now());

                // Avance individual de la cuenta (no del contrato)
                // Finalizada = 100%, en proceso = bloques completados / total bloques
                if ((bool) $cuenta->finalizada) {
                    $progreso = 100;
                } else {
                    $totalBloques = \App\Models\BloqueWorkflow::ordenados()->count();
                    $bloqueActualOrden = $cuenta->bloqueActual?->orden ?? 1;
                    $progreso = $totalBloques > 0
                        ? round(($bloqueActualOrden / $totalBloques) * 100, 1)
                        : 0;
                }

                return [
                    'id' => $cuenta->id,
                    'numero_cuenta' => $cuenta->numero_cuenta,
                    'id_tramite' => $cuenta->numero_radicado ?? $cuenta->id,
                    'fecha_inicio' => optional($fechaInicio)->format('d/m/Y'),
                    'fecha_inicio_iso' => optional($fechaInicio)?->toIso8601String(),
                    'estado_actual' => $cuenta->estadoActual?->nombre ?? 'En trámite',
                    'estado_tipo' => $cuenta->estadoActual?->tipo ?? null,
                    'bloque_actual' => $cuenta->bloqueActual?->nombre ?? 'N/A',
                    'responsable_actual' => $cuenta->responsableActual
                        ? trim(($cuenta->responsableActual->primer_nombre ?? '') . ' ' . ($cuenta->responsableActual->primer_apellido ?? ''))
                        : 'Sin asignar',
                    'ultima_actualizacion' => optional($cuenta->updated_at)->format('d/m/Y H:i A'),
                    'finalizada' => (bool) $cuenta->finalizada,
                    'es_activa' => ! (bool) $cuenta->finalizada,
                    'progreso' => $progreso,
                    'resumen' => trim(sprintf(
                        'Cuenta %s | %s | %s',
                        $cuenta->numero_cuenta ?? 'N/A',
                        $cuenta->estadoActual?->nombre ?? 'En trámite',
                        optional($cuenta->updated_at)->format('d/m/Y')
                    )),
                ];
            })
            ->values();

        return [
            'contrato_id' => $contrato->id,
            'numero_contrato' => $contrato->numero_contrato,
            'contratista' => $contrato->contratista?->razon_social ?? 'Sin datos',
            'cuentas_count' => $cuentas->count(),
            'requires_selection' => $cuentas->count() > 1,
            'cuentas' => $cuentas,
        ];
    }

    /**
     * Valida el límite máximo de cuentas por contrato según numero_pagos_totales.
     * Se excluye la cuenta actual cuando se trata de una actualización.
     */
    private function ensureActiveAccountsLimit(Contrato $contrato, ?int $ignoreCuentaId = null, ?int $limitePersonalizado = null): void
    {
        $totalCount = CuentaCobro::where('contrato_id', $contrato->id)
            ->when($ignoreCuentaId, fn($q) => $q->where('id', '!=', $ignoreCuentaId))
            ->count();

        $limite = $limitePersonalizado ?? (int) CuentaCobro::where('contrato_id', $contrato->id)
            ->max('numero_pagos_totales');

        if ($totalCount >= $limite) {
            throw new \RuntimeException("El contrato ya ha alcanzado el límite máximo de {$limite} cuentas de cobro (N° Pagos Totales).");
        }
    }

    /**
     * Genera un identificador visible de trámite para la consulta pública.
     */
    private function assignNumeroRadicadoIfPossible(CuentaCobro $cuenta, Contrato $contrato): void
    {
        if (! Schema::hasColumn('cuentas_cobro', 'numero_radicado')) {
            return;
        }

        if (! empty($cuenta->numero_radicado)) {
            return;
        }

        $radicado = sprintf('TR-%s-%s', $contrato->numero_contrato, $cuenta->id);
        $cuenta->forceFill(['numero_radicado' => $radicado])->saveQuietly();
    }

    /**
     * CONSULTA PÚBLICA (Citizen Transparency)
     * 
     * Permite que cualquier contratista consulte el estado de su pago 
     * usando sólo su NIT. Implementa una vista simplificada para 
     * reducir la carga administrativa de las oficinas.
     */
    public function publicConsultation(Request $request)
    {
        $nit = $request->input('nit');
        $numeroContrato = $request->input('numero_contrato');

        if (! $nit) {
            return response()->json(['error' => 'El Número de Identificación (NIT/Cédula) es requerido'], 400);
        }

        $contratosQuery = Contrato::whereHas('contratista', fn($cq) => $cq->whereNit($nit));
        if ($numeroContrato) {
            $contratosQuery->where('numero_contrato', $numeroContrato);
        }

        $contrato = $contratosQuery
            ->with([
                'contratista',
                'cuentasCobro.estadoActual',
                'cuentasCobro.bloqueActual',
                'cuentasCobro.responsableActual',
            ])
            ->latest('updated_at')
            ->first();

        if (! $contrato) {
            return response()->json(['error' => 'No se encontró ningún trámite asociado a este documento.'], 404);
        }

        $payload = $this->buildPublicAccountsPayload($contrato);

        return response()->json(array_merge([
            'success' => true,
            'contratista' => $payload['contratista'],
            'numero_contrato' => $payload['numero_contrato'],
            'contrato_id' => $payload['contrato_id'],
            'requires_selection' => $payload['requires_selection'],
            'cuentas_count' => $payload['cuentas_count'],
            'cuentas' => $payload['cuentas'],
        ], $payload['cuentas_count'] === 1 ? [
            'cuenta_default' => $payload['cuentas']->first(),
        ] : []));
    }

    /**
     * CONSULTA PÚBLICA: Historial de estados de una cuenta específica.
     * Permite al contratista ver el historial completo de su trámite.
     */
    public function publicHistorial(int $cuentaId)
    {
        $cuenta = CuentaCobro::with([
            'estadoActual',
            'bloqueActual',
            'contrato.contratista',
            'historialWorkflow.estadoOrigen',
            'historialWorkflow.estadoDestino',
            'historialWorkflow.bloque',
            'historialWorkflow.usuarioAccion',
        ])->findOrFail($cuentaId);

        $historial = $cuenta->historialWorkflow->sortByDesc('fecha_transicion')->values();

        // FILTRADO DINÁMICO: Omitir eventos anteriores al inicio del ciclo actual (Solo vista pública)
        // Punto de Corte: El movimiento más reciente que sea un "Inicio manual de ciclo" 
        // O un cambio de estado desde "Finalizada" hacia un nuevo estado inicial (como "Sin trámite" o "Radicado")
        $marcaCorte = $historial->first(function($h) {
            $esInicioManual = stripos($h->comentarios ?? '', 'Inicio manual del ciclo') !== false;
            
            $nombreOrigen = strtolower($h->estadoOrigen?->nombre ?? '');
            $nombreDestino = strtolower($h->estadoDestino?->nombre ?? '');
            
            $esRetornoInicial = str_contains($nombreOrigen, 'finalizada') && 
                                (str_contains($nombreDestino, 'sin trámite') || 
                                 str_contains($nombreDestino, 'sin tramite') || 
                                 str_contains($nombreDestino, 'radicado'));
                                 
            return $esInicioManual || $esRetornoInicial;
        });

        if ($marcaCorte) {
            // Conservamos solo los movimientos desde el hito hacia adelante
            $historial = $historial->filter(function($h) use ($marcaCorte) {
                return $h->id >= $marcaCorte->id;
            })->values();
        }

        // Fecha de inicio real: primer movimiento fuera de "Sin Trámite"
        $primerMovimiento = $historial
            ->sortBy('fecha_transicion')
            ->first(function ($h) {
                $nombreDestino = strtolower($h->estadoDestino?->nombre ?? '');
                return ! str_contains($nombreDestino, 'sin trámite')
                    && ! str_contains($nombreDestino, 'sin tramite');
            });
        $fechaInicioReal = $primerMovimiento
            ? $primerMovimiento->fecha_transicion
            : ($cuenta->fecha_radicacion ?? $cuenta->created_at ?? now());

        return response()->json([
            'success' => true,
            'cuenta' => [
                'id' => $cuenta->id,
                'numero_cuenta' => $cuenta->numero_cuenta,
                'id_tramite' => $cuenta->numero_radicado ?? $cuenta->id,
                'fecha_inicio' => optional($fechaInicioReal)->format('d/m/Y'),
                'estado_actual' => $cuenta->estadoActual?->nombre ?? 'En trámite',
                'bloque_actual' => $cuenta->bloqueActual?->nombre ?? 'N/A',
            ],
            'contratista' => $cuenta->contrato?->contratista?->razon_social ?? 'Sin datos',
            'numero_contrato' => $cuenta->contrato?->numero_contrato ?? 'N/A',
            'estado_actual' => $cuenta->estadoActual?->nombre ?? 'En trámite',
            'bloque_actual' => $cuenta->bloqueActual?->nombre ?? 'N/A',
            'historial' => $historial,
            'tiempo_total' => $cuenta->tiempo_total_ejecucion,
        ]);
    }

    /**
     * ELIMINA un contrato globalmente con todos sus registros asociados.
     * Equivalente al destroy del SeguimientoController para uso desde el dashboard.
     */
    public function destroyContrato(int $id)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $contrato = Contrato::findOrFail($id);
            $numeroContrato = $contrato->numero_contrato;

            DB::transaction(function () use ($contrato) {
                // Eliminar registros relacionados en orden para respetar FK
                $cuentaIds = $contrato->cuentasCobro()->pluck('id');

                EstadoBloqueCuenta::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                HistorialWorkflow::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                PlanillaSeguridadSocial::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                Alerta::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                $contrato->cuentasCobro()->delete();
                RegistroPresupuestal::where('contrato_id', $contrato->id)->delete();
                $contrato->delete();
            });

            Contrato::logManualAudit(null, 'DELETE', "Contrato #$numeroContrato eliminado globalmente", 'contratos');

            return response()->json(['success' => true, 'message' => "Contrato #$numeroContrato eliminado correctamente."]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', ['operacion' => 'destroyContrato', 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Genera el siguiente número de factura para un contrato.
     * Se busca el número más alto existente y se incrementa en 1.
     */
    private function getNextInvoiceNumber(int $contratoId): string
    {
        $maxFactura = CuentaCobro::where('contrato_id', $contratoId)
            ->whereNotNull('ultima_factura_hacienda')
            ->where('ultima_factura_hacienda', 'not like', 'MANUAL-%')
            ->where('ultima_factura_hacienda', '~', '^[0-9]+$')
            ->selectRaw('MAX(CAST(ultima_factura_hacienda AS INTEGER)) as max_val')
            ->value('max_val');

        return (string) (($maxFactura ?? 0) + 1);
    }

    /**
     * DASHBOARD CONSOLIDADO (Operaciones Centralizadas)
     * 
     * Renderiza la vista principal para los administradores y revisores. 
     * Implementa filtros multicapa y seguridad granual por bloques.
     */
    public function index(Request $request)
    {
        // AUDITORÍA: Punto de control para accesos a datos sensibles.
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el consolidado de cuentas', 'cuentas_cobro');

        /** @var Usuario $user */
        $user = Auth::user();

        // 1. GATEKEEPING: Verificamos permisos específicos (Dashboard vs Consolidado)
        if (! $user->puedeAccederConsolidado()) {
            if ($user->puedeAccederWorkflow()) return redirect()->route('workflow');
            abort(403, 'Acceso restringido');
        }

        $canManage = $user->puedeAccederDashboard();
        $canEditDashboard = $user->tienePermiso('editar_dashboard');

        // 2. QUERY DINÁMICA: Ahora consulta desde Contrato con LEFT JOIN + GROUP BY
        // para garantizar UNA SOLA FILA POR CONTRATO sin duplicados.
        $query = $this->buildCuentasQuery($request);
        $contratos = $query->paginate(20)->appends($request->all());

        $bloques = BloqueWorkflow::ordenados()->get();

        // Respuesta AJAX para refresco de tabla sin recargar toda la página.
        if ($request->ajax()) {
            return response(view('dashboard.componentes.cuentas_table', compact('contratos', 'canManage', 'canEditDashboard', 'bloques'))->render());
        }

        $supervisores = Supervisor::orderBy('nombres')->get();
        $estadosRevision = EstadoWorkflow::whereHas('bloque', fn($q) => $q->where('codigo', 'REV1'))->get();
        $todosLosEstados = EstadoWorkflow::where('es_activo', true)->with('bloque')->get()->groupBy('bloque.codigo');
        $estadosFiltro  = EstadoWorkflow::where('es_activo', true)->select('nombre')->distinct()->orderBy('nombre')->get();
        $responsables = Usuario::where('es_activo', true)->get();

        return view('dashboard.dashboard', compact('contratos', 'supervisores', 'estadosRevision', 'todosLosEstados', 'estadosFiltro', 'canManage', 'canEditDashboard', 'bloques', 'responsables'));
    }

    /**
     * Construye la consulta base para el consolidado, compartida entre la vista y el export.
     * AHORA consulta desde Contrato con LEFT JOIN + GROUP BY para asegurar UNA FILA POR CONTRATO.
     */
    private function buildCuentasQuery(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();

        $query = Contrato::query()
            ->has('cuentasCobro')
            ->with([
                'contratista.seguridadSocialVigente.entidadSalud',
                'contratista.seguridadSocialVigente.entidadPension',
                'contratista.seguridadSocialVigente.entidadArl',
                'supervisor',
                'registrosPresupuestales',
                'cuentasCobro' => fn($q) => $q->with([
                    'bloqueActual',
                    'estadoActual',
                    'responsableActual',
                    'estadosBloques.estadoActual',
                    'estadosBloques.responsable',
                    'planillasSeguridadSocial' => fn($pq) => $pq->orderByDesc('created_at'),
                ]),
            ])
            ->addSelect([
                'pagos_totales' => CuentaCobro::whereColumn('contrato_id', 'contratos.id')
                    ->selectRaw('COALESCE(MAX(numero_pagos_totales), 0)'),
                'facturas_radicadas' => CuentaCobro::whereColumn('contrato_id', 'contratos.id')
                    ->where('finalizada', true)
                    ->selectRaw('COUNT(*)'),
            ]);

        if ($user->verSoloAsignados()) {
            $query->where(function ($q) use ($user) {
                $q->where('abogado_user_id', $user->id)
                    ->orWhere('contador_user_id', $user->id)
                    ->orWhere('ops_user_id', $user->id)
                    ->orWhereHas('cuentasCobro', fn($cq) => $cq->where('responsable_actual_id', $user->id));

                $nombreCompleto = $user->nombre_completo;
                if ($nombreCompleto) {
                    $q->orWhereHas('supervisor', function ($sq) use ($nombreCompleto) {
                        $sq->where(function ($qw) use ($nombreCompleto) {
                            $qw->where('nombres', 'ilike', "%{$nombreCompleto}%")
                                ->orWhere('apellidos', 'ilike', "%{$nombreCompleto}%")
                                ->orWhere(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'ilike', "%{$nombreCompleto}%");
                        });
                    });
                }
            });
        }

        $bloquesPermitidos = $user->bloquesPermitidos();
        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $bloqueIds = BloqueWorkflow::whereIn('codigo', $bloquesPermitidos)->pluck('id');
            $query->whereHas('cuentasCobro', fn($q) => $q->whereIn('bloque_actual_id', $bloqueIds));
        }

        // Filtros (ahora sobre Contrato con whereHas para condiciones sobre cuentas)
        if ($request->filled('searchContrato')) {
            $query->where('numero_contrato', 'like', '%' . $request->searchContrato . '%');
        }
        if ($request->filled('filterContrato')) {
            $query->where('numero_contrato', $request->filterContrato);
        }
        if ($request->filled('searchContratista')) {
            $query->whereHas('contratista', fn($q) => $q->where('razon_social', 'like', '%' . $request->searchContratista . '%')
                ->orWhere('representante_legal', 'like', '%' . $request->searchContratista . '%'));
        }
        if ($request->filled('searchCedula')) {
            $query->whereHas('contratista', fn($q) => $q->whereNit($request->searchCedula));
        }
        if ($request->filled('searchEstado')) {
            $query->whereHas('cuentasCobro.estadoActual', fn($q) => $q->where('nombre', $request->searchEstado));
        }
        if ($request->filled('searchNumeroCuenta')) {
            $query->whereHas('cuentasCobro', fn($q) => $q->where('numero_cuenta', (int) $request->searchNumeroCuenta));
        }
        if ($request->filled('numero_cuenta')) {
            $query->whereHas('cuentasCobro', fn($q) => $q->where('numero_cuenta', (int) $request->numero_cuenta));
        }
        if ($request->filled('filterSupervisor')) {
            $query->where('supervisor_id', $request->filterSupervisor);
        }
        if ($request->filled('filterResponsable')) {
            $query->whereHas('cuentasCobro', fn($q) => $q->where('responsable_actual_id', $request->filterResponsable));
        }
        if ($request->filled('filterEstadosRevision')) {
            $query->whereHas('cuentasCobro', fn($q) => $q->whereIn('estado_actual_id', (array) $request->filterEstadosRevision));
        }
        if ($request->filled('filterRadicadaHacienda')) {
            $valor = $request->filterRadicadaHacienda === 'SI';
            $query->whereHas('cuentasCobro', fn($q) => $q->where('finalizada', $valor));
        }
        if ($request->filled('filterEnFacturacion')) {
            $bloqueFac = $this->getBlockIdByCode('FAC');
            if ($request->filterEnFacturacion === 'SI') {
                $query->whereHas('cuentasCobro', fn($q) => $q->where('bloque_actual_id', $bloqueFac));
            } else {
                $query->whereDoesntHave('cuentasCobro', fn($q) => $q->where('bloque_actual_id', $bloqueFac));
            }
        }

        $sortOrder = $request->input('sort_order', 'asc');
        $sortBy = $request->input('sort_by', 'numero_contrato');
        if ($sortBy === 'numero_contrato') {
            $query->orderByRaw("CAST(NULLIF(regexp_replace(numero_contrato, '[^0-9]', '', 'g'), '') AS NUMERIC) {$sortOrder}");
        } else {
            $query->latest();
        }

        return $query;
    }

    /**
     * EXPORTAR A EXCEL (Advanced Reporting)
     * 
     * @param Request $request
     * @return void
     */
    public function exportExcel(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        Contrato::logManualAudit(null, 'EXPORT', 'Exportación de consolidado a Excel', 'cuentas_cobro');

        $contratos = $this->buildCuentasQuery($request)->get();
        $bloques = BloqueWorkflow::ordenados()->get();

        $spreadsheet = new Spreadsheet();
        
        // --- HOJA 1: DATOS ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Consolidado de Cuentas');

        // Encabezados
        $headers = [
            'N° CONTRATO', 'CONTRATISTA', 'CEDULA/NIT', 'ESTADO ACTUAL', 'RP', 'FECHA RP', 
            'VALOR RP', 'FECHA INICIO', 'FECHA FIN', 'SUPERVISOR', 'N° CUENTA(S)', 
            'PAGOS TOTALES', 'FACTURAS RADICADAS', '% PROGRESO', 
            'SALUD', 'PENSIÓN', 'ARL', 'SS ULTIMA CUENTA', 'RADICADO POR', 'FECHA RADICACION'
        ];
        
        foreach ($bloques as $b) {
            $headers[] = "ESTADO " . strtoupper($b->nombre);
            $headers[] = "FECHA " . strtoupper($b->nombre);
        }
        
        $headers[] = 'ULTIMA FACTURA HACIENDA';
        $headers[] = 'OBS. HACIENDA';
        $headers[] = 'DIFERENCIA CUENTAS';

        $sheet->fromArray($headers, NULL, 'A1');

        // Estilo de Cabecera
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004884']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Datos - UNA FILA POR CONTRATO
        $rowIndex = 2;
        foreach ($contratos as $contrato) {
            $cuentasColl = $contrato->cuentasCobro->sortByDesc('id');
            $cuentaActiva = $cuentasColl->first(fn($cx) => !$cx->finalizada);
            $cuentaRef = $cuentaActiva ?? $cuentasColl->first();
            $rp = $contrato->registrosPresupuestales?->first();
            $ss = $contrato->contratista?->seguridadSocialVigente;

            $row = [
                $contrato->numero_contrato ?? 'N/A',
                $contrato->contratista?->razon_social ?? 'N/A',
                $contrato->contratista?->nit ?? 'N/A',
                $cuentaRef?->estadoActual?->nombre ?? 'N/A',
                $rp?->numero_rp ?? 'N/A',
                $rp?->fecha_rp ? $rp->fecha_rp->format('d/m/Y') : 'N/A',
                $rp?->valor_rp ?? 0,
                $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A',
                $contrato->fecha_fin ? $contrato->fecha_fin->format('d/m/Y') : 'N/A',
                $contrato->supervisor?->nombre_completo ?? 'N/A',
                $cuentasColl->pluck('numero_cuenta')->implode(', '),
                $contrato->pagos_totales ?? 0,
                $contrato->facturas_radicadas ?? 0,
                $contrato->pagos_totales > 0 ? ($contrato->facturas_radicadas / $contrato->pagos_totales) : 0,
                $ss?->entidadSalud?->nombre ?? 'N/A',
                $ss?->entidadPension?->nombre ?? 'N/A',
                $ss?->entidadArl?->nombre ?? 'N/A',
                $cuentaRef?->ss_ultima_cuenta ?? 'N/A',
                $cuentaRef?->radicado_por,
                $cuentaRef?->fecha_radicacion ? $cuentaRef->fecha_radicacion->format('d/m/Y H:i') : 'N/A',
            ];

            foreach ($bloques as $b) {
                $histBlock = $cuentaRef?->estadosBloques?->where('bloque_id', $b->id)->first();
                $row[] = $histBlock?->estadoActual?->nombre ?? 'Pendiente';
                $row[] = $histBlock?->fecha_completado_bloque ? $histBlock->fecha_completado_bloque->format('d/m/Y') : '-';
            }

            $row[] = $cuentaRef?->ultima_factura_hacienda ?? 'N/A';
            $row[] = $cuentaRef?->observacion_hacienda ?? 'N/A';
            $row[] = $cuentaRef?->diferencia_cuentas ?? 0;

            $sheet->fromArray($row, NULL, "A{$rowIndex}");
            
            // Formatos numéricos
            $sheet->getStyle("G{$rowIndex}")->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle("N{$rowIndex}")->getNumberFormat()->setFormatCode('0.0%');
            
            $rowIndex++;
        }

        // Auto-ancho de columnas
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        $sheet->freezePane('D2'); // Congelar contrato y contratista

        // --- HOJA 2: ANALÍTICA ---
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Resumen Ejecutivo');
        
        $summarySheet->setCellValue('A1', 'ANÁLISIS DE GESTIÓN DE CUENTAS');
        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('004884'));
        
        // 1. Resumen por Contrato
        $summarySheet->setCellValue('A3', 'RESUMEN POR CONTRATO');
        $summarySheet->getStyle('A3')->getFont()->setBold(true);
        
        $sumRow = 4;
        $summarySheet->fromArray(['Contrato', 'Cuentas Activas', '% Participación'], NULL, "A{$sumRow}");
        $summarySheet->getStyle("A{$sumRow}:C{$sumRow}")->getFont()->setBold(true);
        $sumRow++;
        
        $totalContratos = $contratos->count();
        foreach ($contratos as $c) {
            $activas = $c->cuentasCobro->where('finalizada', false)->count();
            $perc = $totalContratos > 0 ? (1 / $totalContratos) : 0;
            
            $summarySheet->setCellValue("A{$sumRow}", $c->numero_contrato ?? 'N/A');
            $summarySheet->setCellValue("B{$sumRow}", $activas);
            $summarySheet->setCellValue("C{$sumRow}", $perc);
            $summarySheet->getStyle("C{$sumRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sumRow++;
        }

        // 2. Resumen por Supervisor
        $sumRow += 2;
        $summarySheet->setCellValue("A{$sumRow}", 'GESTIÓN POR SUPERVISOR');
        $summarySheet->getStyle("A{$sumRow}")->getFont()->setBold(true);
        $sumRow++;
        
        $supervisoresCount = $contratos->groupBy(fn($c) => $c->supervisor_id ?? 0);
        $summarySheet->fromArray(['Supervisor', 'Contratos en Gestión'], NULL, "A{$sumRow}");
        $summarySheet->getStyle("A{$sumRow}:B{$sumRow}")->getFont()->setBold(true);
        $sumRow++;
        
        foreach ($supervisoresCount as $id => $group) {
            $nombreSup = $group->first()->supervisor?->nombre_completo ?? 'N/A';
            $summarySheet->setCellValue("A{$sumRow}", $nombreSup);
            $summarySheet->setCellValue("B{$sumRow}", $group->count());
            $sumRow++;
        }

        // 3. Montos en Trámite
        $sumRow += 2;
        $totalValor = $contratos->sum(fn($c) => $c->registrosPresupuestales?->first()?->valor_rp ?? 0);
        $summarySheet->setCellValue("A{$sumRow}", 'VALOR TOTAL EN GESTIÓN');
        $summarySheet->setCellValue("B{$sumRow}", $totalValor);
        $summarySheet->getStyle("A{$sumRow}")->getFont()->setBold(true);
        $summarySheet->getStyle("B{$sumRow}")->getNumberFormat()->setFormatCode('$#,##0');

        $summarySheet->getColumnDimension('A')->setAutoSize(true);
        $summarySheet->getColumnDimension('B')->setAutoSize(true);

        // Salida
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Consolidado_Cuentas_' . now()->format('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.urlencode($fileName).'"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    /**
     * MOTOR DE IMPORTACIÓN MASIVA (Excel/BI Bridge)
     * 
     * Este es uno de los componentes más críticos. Maneja la ingesta de datos 
     * desde archivos externos, aplicando heurísticas para corregir errores 
     * comunes de digitación y desplazamientos de columnas en el Excel.
     */
    public function importExcel(Request $request, \App\Services\ImportService $importService)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $request->validate(['inputFile' => 'required|mimes:xlsx,xls,csv,xlsm|max:10240']);
            $result = $importService->importExcel($request->file('inputFile'), $user);

            if ($result['success']) {
                Contrato::logManualAudit(null, 'IMPORT_EXCEL', $result['message'], 'cuentas_cobro');
            }

            return response()->json($result);
        } catch (\Throwable $e) {
            $traceId = (string) \Illuminate\Support\Str::uuid();
            Log::error('ERROR GENERAL EN IMPORTACIÓN', [
                'trace_id' => $traceId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['success' => false, 'message' => 'Error interno. Ref: ' . $traceId], 500);
        }
    }


    public function storeManual(DashboardRequest $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar cargas manuales.'], 403);
        }
        try {
            // Se usa getColumnValue para mayor flexibilidad con los nombres de los campos de formulario
            $rawRequest = $request->all();
            
            // 1. Validar obligatorios
            if (empty($this->getColumnValue($rawRequest, 'NUMERO DE CONTRATO'))) {
                return response()->json(['success' => false, 'message' => "El campo 'Número de Contrato' es obligatorio."], 422);
            }

            $numContrato = strtoupper(trim($this->getColumnValue($rawRequest, 'NUMERO DE CONTRATO')));

            // 2. Validar existencia
            $contratoExistente = Contrato::where(DB::raw('UPPER(TRIM(numero_contrato))'), $numContrato)->first();
            $esActualizacion = (bool) $contratoExistente;

            $bloqueRad = BloqueWorkflow::where('codigo', 'REV1')->first();
            $estadoRad = $bloqueRad?->estadoInicial;

            if (! $bloqueRad || ! $estadoRad) {
                return response()->json(['success' => false, 'message' => 'Error de configuración de workflow (REV1).'], 500);
            }

            $cuenta = null;
            $numeroCuenta = null;

            DB::transaction(function () use ($rawRequest, $numContrato, $esActualizacion, &$cuenta, &$numeroCuenta) {
                // 1. Contratista
                $nit = strtoupper(trim($this->getColumnValue($rawRequest, ['CEDULA', 'NIT'], '0')));
                $razonSocial = strtoupper(trim($this->getColumnValue($rawRequest, 'CONTRATISTA', '')));
                
                $contratista = Contratista::whereNit($nit)->first();
                $contratistaData = array_filter([
                    'razon_social' => !empty($razonSocial) ? $razonSocial : 'SIN NOMBRE',
                    'tipo_persona' => (strlen($nit) > 10) ? 'JURIDICA' : 'NATURAL',
                ]);

                if ($contratista) {
                    $contratista->update($contratistaData);
                } else {
                    $contratistaData['nit'] = $nit;
                    $contratista = Contratista::create($contratistaData);
                }

                // 2. Supervisor
                $supervisorName = $this->getColumnValue($rawRequest, 'SUPERVISOR', 'PENDIENTE');
                $supervisorParts = $this->splitFullName($supervisorName);
                $supervisor = Supervisor::firstOrCreate(
                    ['nombres' => $supervisorParts['nombres'], 'apellidos' => $supervisorParts['apellidos']],
                    ['cargo' => 'SUPERVISOR']
                );

                // 3. Catálogos
                $modalidad = Modalidad::firstOrCreate(['nombre' => strtoupper(trim($this->getColumnValue($rawRequest, 'MODALIDAD', 'PRESTACIÓN DE SERVICIOS')))]);
                $concepto = Concepto::firstOrCreate(['nombre' => strtoupper(trim($this->getColumnValue($rawRequest, 'CONCEPTO', 'APOYO A LA GESTIÓN')))]);
                $planta = Planta::firstOrCreate(['codigo' => 'P001'], ['nombre' => 'PLANTA CENTRAL']);

                // 4. Contrato
                $contrato = Contrato::where(DB::raw('UPPER(TRIM(numero_contrato))'), $numContrato)->first();

                $contratoData = [
                    'contratista_id' => $contratista->id,
                    'supervisor_id' => $supervisor->id,
                    'modalidad_id' => $modalidad->id,
                    'planta_id' => $planta->id,
                    'concepto_id' => $concepto->id,
                    'fecha_inicio' => $this->parseDate($this->getColumnValue($rawRequest, 'FECHA DE INICIO')),
                    'fecha_fin' => $this->parseDate($this->getColumnValue($rawRequest, 'FECHA DE TERMINACIÓN')),
                    'monto_total' => $this->parseAmount($this->getColumnValue($rawRequest, 'VALOR RP', 0)),
                    'es_activo' => true,
                ];

                if ($contrato) {
                    $contrato->update($contratoData);
                } else {
                    $contratoData['numero_contrato'] = $numContrato;
                    $contrato = Contrato::create($contratoData);
                }

                // 5. Registro Presupuestal
                $rpNum = trim($this->getColumnValue($rawRequest, 'RP', ''));
                if (! empty($rpNum)) {
                    RegistroPresupuestal::updateOrCreate(
                        ['numero_rp' => $rpNum, 'contrato_id' => $contrato->id],
                        [
                            'fecha_rp' => $this->parseDate($this->getColumnValue($rawRequest, 'FECHA RP')),
                            'valor_rp' => $this->parseAmount($this->getColumnValue($rawRequest, 'VALOR RP', 0)),
                        ]
                    );
                }

                // 6. Seguridad Social
                $this->crearSeguridadSocial($rawRequest, $contratista);

                // 7. Cuenta de Cobro
                $numeroCuenta = $this->normalizeAccountNumber($this->getColumnValue($rawRequest, 'NUMERO DE CUENTA EN PROCESO DE CUENTAS', ''));

                $valorRP = $this->parseAmount($this->getColumnValue($rawRequest, 'VALOR RP', 0));
                $pagosTotalesRaw = $this->getColumnValue($rawRequest, 'NUMERO DE PAGOS TOTALES');
                $pagosTotales = (! empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int) $pagosTotalesRaw : null;

                // Determinar bloque y estado actual
                $bloqueId = $this->getBlockIdByCode('REV1');
                $estadoId = $this->getStateIdByCode('REV1_SIN');

                if (! empty($this->getColumnValue($rawRequest, 'RADICADA EN HACIENDA'))) {
                    $bloqueId = $this->getBlockIdByCode('HAC');
                    $estadoId = EstadoWorkflow::where('nombre', $this->getColumnValue($rawRequest, 'RADICADA EN HACIENDA'))
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('HAC_ESP');
                } elseif (! empty($this->getColumnValue($rawRequest, 'FIRMA SECRETARIO'))) {
                    $bloqueId = $this->getBlockIdByCode('FIR');
                    $estadoId = EstadoWorkflow::where('nombre', $this->getColumnValue($rawRequest, 'FIRMA SECRETARIO'))
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('FIR_ESP');
                } elseif (! empty($this->getColumnValue($rawRequest, 'EN FACTURACIÓN'))) {
                    $bloqueId = $this->getBlockIdByCode('FAC');
                    $estadoId = EstadoWorkflow::where('nombre', $this->getColumnValue($rawRequest, 'EN FACTURACIÓN'))
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('FAC_ESP');
                } elseif (! empty($this->getColumnValue($rawRequest, 'ENVIADA A INGRESO MERCANCIA SAP'))) {
                    $bloqueId = $this->getBlockIdByCode('SAP');
                    $estadoId = EstadoWorkflow::where('nombre', $this->getColumnValue($rawRequest, 'ENVIADA A INGRESO MERCANCIA SAP'))
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('SAP_ESP');
                } elseif (! empty($this->getColumnValue($rawRequest, 'ESTADO TRAS PRIMERA REVISIÓN'))) {
                    $bloqueId = $this->getBlockIdByCode('REV1');
                    $estadoId = EstadoWorkflow::where('nombre', $this->getColumnValue($rawRequest, 'ESTADO TRAS PRIMERA REVISIÓN'))
                        ->where('bloque_id', $bloqueId)->value('id') ?? $this->getStateIdByCode('REV1_SIN');
                }

                // AUTO-ADVANCE MANUAL RECURSIVO: Aplicar la misma lógica de avance para cargas manuales
                $estadoObj = EstadoWorkflow::find($estadoId);
                $bloqueActualObj = BloqueWorkflow::find($bloqueId);

                while ($estadoObj && $estadoObj->es_final && $bloqueId != $this->getBlockIdByCode('FIN')) {
                    $siguienteBloque = BloqueWorkflow::where('orden', '>', $bloqueActualObj->orden)
                        ->orderBy('orden')->first();
                    
                    if (!$siguienteBloque) break;

                    $bloqueId = $siguienteBloque->id;
                    $bloqueActualObj = $siguienteBloque;
                    $estadoObj = $siguienteBloque->estadoInicial;
                    $estadoId = $estadoObj?->id ?? $estadoId;

                    if (!$estadoObj) break;
                }

                $estaFinalizada = ($bloqueId == $this->getBlockIdByCode('FIN') || ($bloqueId == $this->getBlockIdByCode('HAC') && ($this->getColumnValue($rawRequest, 'RADICADA EN HACIENDA') === 'SI')));

                $facturasRadicadas = (int) $this->getColumnValue($rawRequest, 'N° DE FACTURAS RADICADA HACIENDA', 0);
                $cuentaExistente = CuentaCobro::where('contrato_id', $contrato->id)
                    ->where('numero_cuenta', (string) $numeroCuenta)
                    ->first();

                if (! $cuentaExistente || ! $cuentaExistente->finalizada) {
                    $this->ensureActiveAccountsLimit($contrato, $cuentaExistente?->id, (int) ($pagosTotales ?? 0));
                }

            $cuenta = CuentaCobro::updateOrCreate(
                ['contrato_id' => $contrato->id, 'numero_cuenta' => (string)$numeroCuenta],
                [
                        'valor_cobro' => ($pagosTotales && $pagosTotales > 0) ? ($valorRP / $pagosTotales) : $valorRP,
                        'numero_pagos_totales' => $pagosTotales,
                        'numero_facturas_radicadas' => $facturasRadicadas,
                        'porcentaje_cuentas' => ($pagosTotales > 0) ? (($facturasRadicadas / $pagosTotales) * 100) : 0,
                        'diferencia_cuentas' => ($pagosTotales ?? 0) - ($facturasRadicadas ?? 0),
                        'fecha_radicacion' => $this->parseDate($this->getColumnValue($rawRequest, 'FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES')),
                        'radicado_por' => $this->getColumnValue($rawRequest, 'RADICADO POR'),
                        'ultima_factura_hacienda' => $this->getColumnValue($rawRequest, 'ULTIMA FACTURA RADICADA HACIENDA'),
                        'fecha_radicacion_hacienda' => $this->parseDate($this->getColumnValue($rawRequest, 'FECHA DE RADICACIÓN')),
                        'observacion_hacienda' => $this->getColumnValue($rawRequest, 'OBSERVACIÓN DEVOLUCIÓN HACIENDA'),
                        'bloque_actual_id' => $bloqueId,
                        'estado_actual_id' => $estadoId,
                        'responsable_actual_id' => Auth::id(),
                        'finalizada' => $estaFinalizada,
                        'observaciones' => $this->getColumnValue($rawRequest, 'OBSERVACIONES'),
                    'ss_ultima_cuenta' => $this->getColumnValue($rawRequest, ['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA', 'PLANILLA SEGURIDAD', 'PLANILLA SEGURIDAD SOCIAL', 'SS ULTIMA CUENTA', 'PLANILLA SEG']),

                ]
            );
            $this->assignNumeroRadicadoIfPossible($cuenta, $contrato);

                // 8. Planilla
                $valPlanilla = $this->getColumnValue($rawRequest, ['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA', 'PLANILLA SEGURIDAD', 'PLANILLA SEGURIDAD SOCIAL', 'SS ULTIMA CUENTA', 'PLANILLA SEG']);
                if ($valPlanilla) {
                    PlanillaSeguridadSocial::updateOrCreate(
                        ['cuenta_cobro_id' => $cuenta->id, 'es_ultima' => true],
                        [
                            'mes_planilla' => strtoupper($valPlanilla),
                            'numero_planilla' => $valPlanilla
                        ]
                    );
                }

                // 9. Procesar Bloques Históricos
                $this->procesarBloquesHistoricos($cuenta, $rawRequest);
            });

            // Registrar en auditoría la carga manual
            $accion = ($cuenta && $cuenta->wasRecentlyCreated) ? 'Creado' : 'Actualizado';
            $msg = "Registro {$accion} correctamente: Contrato " . ($numContrato ?? '') . ", Cuenta " . ($numeroCuenta ?? '');

            Contrato::logManualAudit(null, 'INSERT_MANUAL', $msg, 'cuentas_cobro');

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'storeManual', 'contrato' => $numContrato ?? 'desconocido']);

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Carga los datos de una cuenta específica para su edición en el formulario.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(int $id)
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
                'estadosBloques.estadoActual',
            ])->findOrFail($id);

            /** @var RegistroPresupuestal $rp */
            $rp = $cuenta->contrato->registrosPresupuestales->first();
            /** @var Contrato $contrato */
            $contrato = $cuenta->contrato;

            // Preparar datos para el formulario
            $data = [
                'NUMERO DE CONTRATO' => $contrato->numero_contrato,
                'CONTRATISTA' => $contrato->contratista->razon_social,
                'CEDULA' => $contrato->contratista->nit,
                'RP' => $rp?->numero_rp,
                'FECHA RP' => ($rp && $rp->fecha_rp instanceof Carbon) ? $rp->fecha_rp->format('Y-m-d') : null,
                'VALOR RP' => $rp?->valor_rp,
                'FECHA DE INICIO' => ($contrato->fecha_inicio instanceof Carbon) ? $contrato->fecha_inicio->format('Y-m-d') : null,
                'FECHA DE TERMINACIÓN' => ($contrato->fecha_fin instanceof Carbon) ? $contrato->fecha_fin->format('Y-m-d') : null,
                'SUPERVISOR' => trim(($contrato->supervisor->nombres ?? '').' '.($contrato->supervisor->apellidos ?? '')),
                'NUMERO DE CUENTA EN PROCESO DE CUENTAS' => $cuenta->numero_cuenta,
                'NUMERO DE PAGOS TOTALES' => $cuenta->numero_pagos_totales,
                'N° DE FACTURAS RADICADA HACIENDA' => $cuenta->numero_facturas_radicadas,
                'PORCENTAJE DE CUENTAS' => $cuenta->porcentaje_cuentas,
                'ENTIDAD SALUD' => $cuenta->contrato->contratista->seguridadSocialVigente?->entidadSalud?->nombre,
                'ENTIDAD PENSIÓN' => $cuenta->contrato->contratista->seguridadSocialVigente?->entidadPension?->nombre,
                'ENTIDAD ARL' => $cuenta->contrato->contratista->seguridadSocialVigente?->entidadArl?->nombre,
                'PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA' => $cuenta->ss_ultima_cuenta ?? $cuenta->planillasSeguridadSocial->first()?->mes_planilla,
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
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'edit', 'id' => $id]);

            return response()->json(['success' => false, 'message' => 'Error al cargar datos: ' . $e->getMessage()], 500);
        }
    }

    public function update(DashboardRequest $request, int $id)
    {
        $traceId = (string) \Illuminate\Support\Str::uuid();
        
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
                if (! empty($data['CONTRATISTA'])) {
                    $contrato->contratista->update(['razon_social' => $data['CONTRATISTA']]);
                }
                // (Nota: Si cambia la cédula, debería buscar/crear otro contratista, pero por simplicidad de edición asumimos actualización de datos del mismo)
                if (! empty($data['CEDULA'])) {
                    $contrato->contratista->update(['nit' => $data['CEDULA']]);
                }

                // 3. Supervisor
                if (! empty($data['SUPERVISOR'])) {
                    $supervisorParts = $this->splitFullName($data['SUPERVISOR']);
                    $supervisor = Supervisor::firstOrCreate(
                        ['nombres' => $supervisorParts['nombres'], 'apellidos' => $supervisorParts['apellidos']],
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
                $valorRP = $this->parseAmount($this->getColumnValue($data, 'VALOR RP', 0));
                $pagosTotalesRaw = $this->getColumnValue($data, 'NUMERO DE PAGOS TOTALES');
                $pagosTotales = (! empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int) $pagosTotalesRaw : null;

                // Permitir edición manual de facturas radicadas
                $facturasRadicadasForm = $this->getColumnValue($data, 'N° DE FACTURAS RADICADA HACIENDA');
                $facturasRadicadasActual = ($facturasRadicadasForm !== null && $facturasRadicadasForm !== '') 
                    ? (int) $facturasRadicadasForm 
                    : ($cuenta->numero_facturas_radicadas ?? 0);

                $newNumCuenta = $this->getColumnValue($data, 'NUMERO DE CUENTA EN PROCESO DE CUENTAS') ?? $cuenta->numero_cuenta;
                if (empty($newNumCuenta) || $newNumCuenta == 0) {
                    $newNumCuenta = 1;
                }

                $cuenta->update([
                    'numero_cuenta' => $newNumCuenta,
                    'valor_cobro' => ($pagosTotales && $pagosTotales > 0) ? ($valorRP / $pagosTotales) : $valorRP,

                    'fecha_radicacion' => $this->parseDate($this->getColumnValue($data, 'FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES')),
                    'numero_pagos_totales' => $pagosTotales,
                    'numero_facturas_radicadas' => $facturasRadicadasActual,

                    'porcentaje_cuentas' => ($pagosTotales > 0) ? (($facturasRadicadasActual / $pagosTotales) * 100) : 0,
                    'radicado_por' => $this->getColumnValue($data, 'RADICADO POR'),
                    'observaciones' => $this->getColumnValue($data, 'OBSERVACIONES'),
                    'ultima_factura_hacienda' => $this->getColumnValue($data, 'ULTIMA FACTURA RADICADA HACIENDA'),
                    'fecha_radicacion_hacienda' => $this->parseDate($this->getColumnValue($data, 'FECHA DE RADICACIÓN')),
                    'observacion_hacienda' => $this->getColumnValue($data, 'OBSERVACIÓN DEVOLUCIÓN HACIENDA'),
                    'diferencia_cuentas' => ($pagosTotales ?? 0) - ($facturasRadicadasActual ?? 0),
                    'ss_ultima_cuenta' => $this->getColumnValue($data, ['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA', 'PLANILLA SEGURIDAD', 'PLANILLA SEGURIDAD SOCIAL', 'SS ULTIMA CUENTA', 'PLANILLA SEG']),

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
                        Log::info('Skipping generation: Current factura is present and valid.');
                    }
                }

                // 7. Bloques Históricos (Actualizar estados si cambiaron)
                $this->procesarBloquesHistoricos($cuenta, $data);

                // 8. Planilla (Si cambia)
                $valPlanilla = $this->getColumnValue($data, ['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA', 'PLANILLA SEGURIDAD', 'PLANILLA SEGURIDAD SOCIAL', 'SS ULTIMA CUENTA', 'PLANILLA SEG']);
                if ($valPlanilla) {
                    PlanillaSeguridadSocial::updateOrCreate(
                        ['cuenta_cobro_id' => $cuenta->id, 'es_ultima' => true],
                        [
                            'mes_planilla' => strtoupper($valPlanilla),
                            'numero_planilla' => $valPlanilla
                        ]
                    );
                }
            });

            return response()->json(['success' => true, 'message' => 'Registro actualizado correctamente.']);
        } catch (\Throwable $e) {
            Log::error("Error en CuentaCobroController@update", [
                'trace_id' => $traceId,
                'message'  => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
                'id'       => $id
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Ocurrió un error interno al procesar su solicitud. Ref: ' . $traceId
            ], 500);
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
            'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS',
        ];

        return SimpleExcelWriter::streamDownload('plantilla_cuentas_cobro.xlsx')
            ->noHeaderRow()
            ->addRow($headers)
            ->toBrowser();
    }

    private function parseDate(mixed $value)
    {
        if (! $value || $value === '0' || $value === 'N/A' || $value === '') {
            return null;
        }
        if ($value === '1') {
            return now();
        }

        // Si es un objeto ya (como Carbon o DateTime)
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            $value = trim($value);
            // Si parece un monto (contiene $) no es una fecha válida
            if (str_contains($value, '$')) {
                return null;
            }
            // Si es algo como "15 MESES" tampoco es fecha
            if (preg_match('/[0-9]+\s*MESES/i', $value)) {
                return null;
            }
        }

        try {
            // Si es numérico y parece fecha Excel (días desde 1900-01-01)
            if (is_numeric($value) && $value > 40000 && $value < 60000) {
                return Carbon::create(1899, 12, 30)->addDays((int) $value);
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
    private function procesarBloquesHistoricos(CuentaCobro $cuenta, array $data)
    {
        Log::info("Procesando bloques históricos para cuenta ID: " . $cuenta->id);

        // 1. Bloque 1: ESTADO TRAS PRIMERA REVISIÓN (REV1)
        $estadoRevNombre = $this->getColumnValue($data, 'ESTADO TRAS PRIMERA REVISIÓN');
        Log::info("  REV1 Estado: " . ($estadoRevNombre ?? 'NULL'));

        if ($estadoRevNombre && $estadoRevNombre != 'N/A') {
            $fechaRev = $this->parseDate($this->getColumnValue($data, 'FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP'));
            $bloqueId = $this->getBlockIdByCode('REV1');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoRevNombre%")->where('bloque_id', $bloqueId)->first();
            Log::info("    Bloque ID: $bloqueId, Estado encontrado: " . ($estado ? 'SÍ (' . $estado->id . ')' : 'NO'));

            $fechaIngresoBloque = $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now();
            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('REV1_REV'),
                    'fecha_ingreso_bloque' => $fechaIngresoBloque,
                    'fecha_completado_bloque' => $fechaRev ?? (($cuenta->bloque_actual_id > $bloqueId) ? now() : null),
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => ! empty($fechaRev) || ($cuenta->bloque_actual_id > $bloqueId),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 2. Bloque 2: ENVIADA A INGRESO MERCANCIA SAP (SAP)
        $estadoSapNombre = $this->getColumnValue($data, ['ENVIADA A INGRESO MERCANCIA SAP', 'ENVIADA SAP']);
        Log::info("  SAP Estado: " . ($estadoSapNombre ?? 'NULL'));

        if ($estadoSapNombre && $estadoSapNombre != 'N/A') {
            $fechaSap = $this->parseDate($this->getColumnValue($data, 'FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES'));
            $bloqueId = $this->getBlockIdByCode('SAP');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoSapNombre%")->where('bloque_id', $bloqueId)->first();
            Log::info("    Bloque ID: $bloqueId, Estado encontrado: " . ($estado ? 'SÍ (' . $estado->id . ')' : 'NO'));

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('SAP_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($this->getColumnValue($data, 'FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP')) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaSap ?? (($cuenta->bloque_actual_id > $bloqueId) ? now() : null),
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => ! empty($fechaSap) || ($cuenta->bloque_actual_id > $bloqueId),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 3. Bloque 3: EN FACTURACIÓN (FAC)
        $estadoFacNombre = $this->getColumnValue($data, 'EN FACTURACIÓN');
        Log::info("  FAC Estado: " . ($estadoFacNombre ?? 'NULL'));

        if ($estadoFacNombre && $estadoFacNombre != 'N/A') {
            $fechaFac = $this->parseDate($this->getColumnValue($data, 'FECHA EN QUE SE GENERA FACURACIÓN'));
            $bloqueId = $this->getBlockIdByCode('FAC');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoFacNombre%")->where('bloque_id', $bloqueId)->first();
            Log::info("    Bloque ID: $bloqueId, Estado encontrado: " . ($estado ? 'SÍ (' . $estado->id . ')' : 'NO'));

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('FAC_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($this->getColumnValue($data, 'FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES')) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaFac ?? (($cuenta->bloque_actual_id > $bloqueId) ? now() : null),
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => ! empty($fechaFac) || ($cuenta->bloque_actual_id > $bloqueId),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 4. Bloque 4: FIRMA SECRETARIO (FIR)
        $estadoFirNombre = $this->getColumnValue($data, 'FIRMA SECRETARIO');
        Log::info("  FIR Estado: " . ($estadoFirNombre ?? 'NULL'));

        if ($estadoFirNombre && $estadoFirNombre != 'N/A') {
            $fechaFir = $this->parseDate($this->getColumnValue($data, 'FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO'));
            $bloqueId = $this->getBlockIdByCode('FIR');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoFirNombre%")->where('bloque_id', $bloqueId)->first();
            Log::info("    Bloque ID: $bloqueId, Estado encontrado: " . ($estado ? 'SÍ (' . $estado->id . ')' : 'NO'));

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('FIR_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($this->getColumnValue($data, 'FECHA EN QUE SE GENERA FACURACIÓN')) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaFir ?? (($cuenta->bloque_actual_id > $bloqueId) ? now() : null),
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => ! empty($fechaFir) || ($cuenta->bloque_actual_id > $bloqueId),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 5. Bloque 5: EN HACIENDA (HAC)
        $estadoHacNombre = $this->getColumnValue($data, 'RADICADA EN HACIENDA');
        Log::info("  HAC Estado: " . ($estadoHacNombre ?? 'NULL'));

        if ($estadoHacNombre && $estadoHacNombre != 'N/A') {
            $fechaHac = $this->parseDate($this->getColumnValue($data, 'FECHA DE RADICACIÓN'));
            $bloqueId = $this->getBlockIdByCode('HAC');
            $estado = EstadoWorkflow::where('nombre', 'like', "%$estadoHacNombre%")->where('bloque_id', $bloqueId)->first();
            Log::info("    Bloque ID: $bloqueId, Estado encontrado: " . ($estado ? 'SÍ (' . $estado->id . ')' : 'NO'));

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueId],
                [
                    'estado_actual_id' => $estado?->id ?? $this->getStateIdByCode('HAC_ESP'),
                    'fecha_ingreso_bloque' => $this->parseDate($this->getColumnValue($data, 'FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO')) ?? $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now(),
                    'fecha_completado_bloque' => $fechaHac,
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => ! empty($fechaHac),
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );
        }

        // 6. ASEGURAR QUE EL BLOQUE ACTUAL TENGA UN REGISTRO (Para el cronómetro en el Dashboard)
        if ($cuenta->bloque_actual_id) {
            // Intentar determinar la fecha de ingreso al bloque actual basada en el fin del bloque anterior
            $fechaIngresoActual = now();
            if ($cuenta->bloque_actual_id > 1) {
                $ultimoBloque = EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)
                    ->where('bloque_id', '<', $cuenta->bloque_actual_id)
                    ->whereNotNull('fecha_completado_bloque')
                    ->orderByDesc('bloque_id')
                    ->first();
                if ($ultimoBloque) {
                    $fechaIngresoActual = $ultimoBloque->fecha_completado_bloque;
                }
            } else {
                $fechaIngresoActual = $cuenta->fecha_radicacion ?? $cuenta->created_at ?? now();
            }

            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $cuenta->bloque_actual_id],
                [
                    'estado_actual_id' => $cuenta->estado_actual_id,
                    'fecha_ingreso_bloque' => $fechaIngresoActual,
                    'fecha_ultima_actualizacion' => now(),
                    'bloque_completado' => $cuenta->finalizada,
                    'responsable_id' => $cuenta->responsable_actual_id,
                ]
            );

            // ACTUALIZACIÓN CRÍTICA: Ajustar el cronómetro de la cuenta al tiempo real de entrada
            $cuenta->ultimo_inicio_conteo = $fechaIngresoActual;
            $cuenta->saveQuietly();
        }

        Log::info("  ✅ Bloques históricos procesados");
    }

    private function normalizeAccountNumber(mixed $value): string
    {
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        $clean = trim((string)($value ?? ''));

        // Si termina en .0 (ej de Excel) removerlo
        if (str_ends_with($clean, '.0')) {
            $clean = substr($clean, 0, -2);
        }

        return (empty($clean) || $clean === '0') ? '1' : $clean;
    }

    private function parseAmount(mixed $value)
    {
        if ($value instanceof \DateTimeInterface) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (empty($value)) {
            return 0.0;
        }

        // Limpiar caracteres comunes
        $clean = str_replace(['$', ' ', '%'], '', (string) $value);

        // Si no hay números, retornar 0
        if (! preg_match('/[0-9]/', $clean)) {
            return 0.0;
        }

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

    private function splitFullName(?string $fullName): array
    {
        if (empty($fullName)) {
            return ['nombres' => '', 'apellidos' => ''];
        }

        $fullName = strtoupper(trim($fullName));
        $parts = preg_split('/\s+/', $fullName);

        if (count($parts) === 1) {
            return ['nombres' => $fullName, 'apellidos' => ''];
        }

        $apellidos = array_pop($parts);
        $nombres = implode(' ', $parts);

        return ['nombres' => $nombres, 'apellidos' => $apellidos];
    }

    private function obtenerOCrearEntidad(?string $nombre, string $tipo): ?int
    {
        if (empty($nombre)) {
            return null;
        }

        // Normalizar nombre
        $nombre = strtoupper(trim($nombre));

        // Filtrar casos especiales
        $especiales = ['NA', 'N/A', 'NINGUNA', 'REVISOR FISCAL', 'PARAFISCALES Y CONTADOR', 'SIN DATO', '0'];
        if (in_array($nombre, $especiales)) {
            return null;
        }

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
        if (is_string($valCheck) && ! empty($valCheck) && ! is_numeric($valCheck) && ! str_contains($valCheck, '%')) {
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

    /**
     * MAPEO FLEXIBLE DE COLUMNAS
     * Busca una columna en el array ignorando prefijos/sufijos entre paréntesis
     */
    private function getColumnValue(array &$data, string|array $keys, mixed $default = null)
    {
        // Si es un array, probar cada clave
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $searchKey = strtoupper(trim($key));
                // Probar exacta
                if (isset($data[$searchKey])) return $data[$searchKey];
                // Probar con underscores
                $underscoreKey = str_replace(' ', '_', $searchKey);
                if (isset($data[$underscoreKey])) return $data[$underscoreKey];
            }
        } elseif (is_string($keys)) {
            $searchKey = strtoupper(trim($keys));
            // Probar exacta
            if (isset($data[$searchKey])) return $data[$searchKey];
            // Probar con underscores
            $underscoreKey = str_replace(' ', '_', $searchKey);
            if (isset($data[$underscoreKey])) return $data[$underscoreKey];

            // Si no existe exactamente, buscar por coincidencia parcial o limpieza de paréntesis y N°
            foreach ($data as $k => $v) {
                // Normalizar clave de origen: strtoupper y cambiar _ por espacio
                $kNorm = str_replace('_', ' ', strtoupper(trim($k)));
                // Remover paréntesis y N° para comparar de forma flexible
                $kClean = trim(preg_replace('/\s*\([^)]*\)?/', '', $kNorm));
                $kClean = str_replace('N°', 'N', $kClean);

                $searchKeyClean = str_replace('N°', 'N', trim(preg_replace('/\s*\([^)]*\)?/', '', $searchKey)));

                if ($kClean === $searchKeyClean) {
                    return $v;
                }
            }
        }

        return $default;
    }
}
