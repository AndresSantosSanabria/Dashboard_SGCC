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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class CuentaCobroController extends Controller
{
    /**
     * CENTRAL DE OPERACIONES - SGCC
     * 
     * Este controlador es la columna vertebral operativa del sistema. 
     * Gestiona el ciclo de vida completo de una Cuenta de Cobro, 
     * desde su radicación (vía Excel o Manual) hasta su finalización contable.
     */
    private $blocksCache = [];
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
     * CONSULTA PÚBLICA (Citizen Transparency)
     * 
     * Permite que cualquier contratista consulte el estado de su pago 
     * usando sólo su NIT. Implementa una vista simplificada para 
     * reducir la carga administrativa de las oficinas.
     */
    public function publicConsultation(Request $request)
    {
        $nit = $request->input('nit');
        if (! $nit) return response()->json(['error' => 'NIT requerido'], 400);

        // Buscamos la cuenta más reciente para este contratista.
        $cuenta = CuentaCobro::whereHas('contrato.contratista', fn($q) => $q->where('nit', $nit))
            ->with([
                'estadoActual',
                'bloqueActual',
                'contrato.contratista',
                'responsableActual',
                'estadosBloques.responsable', // responsable por bloque
            ])
            ->latest('updated_at')->first();

        if (! $cuenta) return response()->json(['error' => 'No se encontraron trámites activos'], 404);

        // Buscar responsable: primero en el campo principal, luego en el bloque actual
        $responsable = $cuenta->responsableActual;

        if (! $responsable && $cuenta->bloque_actual_id) {
            $bloqueActual = $cuenta->estadosBloques
                ->where('bloque_id', $cuenta->bloque_actual_id)
                ->first();
            $responsable = $bloqueActual?->responsable;
        }

        $nombreResponsable = null;
        if ($responsable) {
            $nombreResponsable = trim(
                ($responsable->primer_nombre ?? '') . ' ' .
                ($responsable->primer_apellido ?? '')
            ) ?: null;
        }

        return response()->json([
            'id'                  => $cuenta->id,
            'contratista'         => $cuenta->contrato?->contratista?->razon_social ?? 'Sin datos',
            'estado'              => $cuenta->estadoActual?->nombre ?? 'En trámite',
            'bloque'              => $cuenta->bloqueActual?->nombre ?? 'N/A',
            'responsable'         => $nombreResponsable,
            'ultima_actualizacion'=> $cuenta->updated_at->format('d/m/Y H:i A'),
        ]);
    }

    /**
     * CONSULTA PÚBLICA: Historial de estados de una cuenta específica.
     * Permite al contratista ver el historial completo de su trámite.
     */
    public function publicHistorial($cuentaId)
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

        return response()->json([
            'success' => true,
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
    public function destroyContrato($id)
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
    private function getNextInvoiceNumber($contratoId): string
    {
        $maxFactura = CuentaCobro::where('contrato_id', $contratoId)
            ->whereNotNull('ultima_factura_hacienda')
            ->where('ultima_factura_hacienda', 'not like', 'MANUAL-%')
            ->whereRaw("ultima_factura_hacienda ~ '^[0-9]+$'")
            ->max(DB::raw('CAST(ultima_factura_hacienda AS integer)'));

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

        // 2. QUERY DINÁMICA: Carga perezosa (Eager Loading) para evitar N+1 
        // en la carga de entidades relacionadas como contratistas y supervisores.
        $query = CuentaCobro::query()->with([
            'contrato.contratista.seguridadSocialVigente.entidadSalud',
            'contrato.contratista.seguridadSocialVigente.entidadPension',
            'contrato.contratista.seguridadSocialVigente.entidadArl',
            'contrato.supervisor',
            'contrato.registrosPresupuestales',
            'responsableActual',
            'estadoActual',
            'bloqueActual',
            'estadosBloques.estadoActual',
            'estadosBloques.responsable',
            'planillasSeguridadSocial' => fn($q) => $q->orderByDesc('created_at'),
        ]);

        // 3. SEGURIDAD DE FILTRADO (Sandboxing)
        // Aplicamos las mismas reglas de visibilidad que en el Workflow para coherencia de datos.
        if ($user->verSoloAsignados()) {
            $query->where(function($q) use ($user) {
                // a. Es el responsable directo del trámite actual
                $q->where('responsable_actual_id', $user->id)
                // b. O es parte del equipo de gestión del contrato (Abogado, Contador, OPS)
                ->orWhereHas('contrato', function($cq) use ($user) {
                    $cq->where('abogado_user_id', $user->id)
                       ->orWhere('contador_user_id', $user->id)
                       ->orWhere('ops_user_id', $user->id);
                    
                    // c. O es el Supervisor del contrato (con el mismo nombre)
                    $nombreCompleto = $user->nombre_completo;
                    if ($nombreCompleto) {
                        $cq->orWhereHas('supervisor', function($sq) use ($nombreCompleto) {
                            $sq->where(DB::raw("TRIM(CONCAT(nombres, ' ', apellidos))"), 'ilike', '%' . $nombreCompleto . '%');
                        });
                    }
                });
            });
        }

        $bloquesPermitidos = $user->bloquesPermitidos();
        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $query->whereIn('bloque_actual_id', function ($subQuery) use ($bloquesPermitidos) {
                $subQuery->select('id')->from('bloques_workflow')->whereIn('codigo', $bloquesPermitidos);
            });
        }

        // 4. FILTROS AVANZADOS Y ORDENAMIENTO
        $sortOrder = $request->input('sort_order', 'asc');
        $sortBy = $request->input('sort_by', 'numero_contrato'); // Default a número contrato

        if ($request->filled('searchContrato')) {
            $query->whereHas('contrato', fn($q) => $q->where('numero_contrato', 'like', '%' . $request->searchContrato . '%'));
        }

        // Filtro exacto de contrato (desde offcanvas)
        if ($request->filled('filterContrato')) {
            $query->whereHas('contrato', fn($q) => $q->where('numero_contrato', $request->filterContrato));
        }

        if ($request->filled('searchContratista')) {
            $query->whereHas('contrato.contratista', fn($q) => $q->where('razon_social', 'like', '%' . $request->searchContratista . '%')
                ->orWhere('representante_legal', 'like', '%' . $request->searchContratista . '%'));
        }

        if ($request->filled('searchCedula')) {
            $query->whereHas('contrato.contratista', fn($q) => $q->where('nit', 'like', '%' . $request->searchCedula . '%'));
        }

        if ($request->filled('searchEstado')) {
            $query->whereHas('estadoActual', fn($q) => $q->where('nombre', $request->searchEstado));
        }

        if ($request->filled('searchNumeroCuenta')) {
            $query->where('numero_cuenta', (int) $request->searchNumeroCuenta);
        }

        // Filtro de número de cuenta (desde offcanvas)
        if ($request->filled('numero_cuenta')) {
            $query->where('numero_cuenta', (int) $request->numero_cuenta);
        }

        // Filtro por Supervisor
        if ($request->filled('filterSupervisor')) {
            $query->whereHas('contrato', fn($q) => $q->where('supervisor_id', $request->filterSupervisor));
        }

        // Filtro por Responsable Actual
        if ($request->filled('filterResponsable')) {
            $query->where('responsable_actual_id', $request->filterResponsable);
        }

        // Filtro por Estados de Revisión (Checkboxes)
        if ($request->filled('filterEstadosRevision')) {
            $query->whereIn('estado_actual_id', (array) $request->filterEstadosRevision);
        }

        // Filtro Radicada en Hacienda
        if ($request->filled('filterRadicadaHacienda')) {
            $valor = $request->filterRadicadaHacienda === 'SI';
            $query->where('finalizada', $valor);
        }

        // Filtro En Facturación (Bloque FAC)
        if ($request->filled('filterEnFacturacion')) {
            $bloqueFac = $this->getBlockIdByCode('FAC');
            if ($request->filterEnFacturacion === 'SI') {
                $query->where('bloque_actual_id', $bloqueFac);
            } else {
                $query->where('bloque_actual_id', '!=', $bloqueFac);
            }
        }

        // Aplicamos el ordenamiento por número de contrato (Natural Sort en BD)
        // Usamos una subconsulta para el ordenamiento para evitar joins complejos que puedan filtrar resultados erróneamente
        if ($sortBy === 'numero_contrato') {
            $query->orderBy(
                Contrato::selectRaw("CAST(NULLIF(regexp_replace(numero_contrato, '[^0-9]', '', 'g'), '') AS NUMERIC)")
                    ->whereColumn('contratos.id', 'cuentas_cobro.contrato_id')
                    ->limit(1),
                $sortOrder
            );
        } else {
            $query->latest();
        }

        $cuentas = $query->paginate(20)->appends($request->all());

        $bloques = BloqueWorkflow::ordenados()->get();

        // Respuesta AJAX para refresco de tabla sin recargar toda la página.
        if ($request->ajax()) {
            return response(view('dashboard.componentes.cuentas_table', compact('cuentas', 'canManage', 'canEditDashboard', 'bloques'))->render());
        }

        $supervisores = Supervisor::orderBy('nombres')->get();
        $estadosRevision = EstadoWorkflow::whereHas('bloque', fn($q) => $q->where('codigo', 'REV1'))->get();
        $todosLosEstados = EstadoWorkflow::where('es_activo', true)->with('bloque')->get()->groupBy('bloque.codigo');
        $estadosFiltro  = EstadoWorkflow::where('es_activo', true)->select('nombre')->distinct()->orderBy('nombre')->get();
        $responsables = Usuario::where('es_activo', true)->get();

        return view('dashboard.dashboard', compact('cuentas', 'supervisores', 'estadosRevision', 'todosLosEstados', 'estadosFiltro', 'canManage', 'canEditDashboard', 'bloques', 'responsables'));
    }

    /**
     * MOTOR DE IMPORTACIÓN MASIVA (Excel/BI Bridge)
     * 
     * Este es uno de los componentes más críticos. Maneja la ingesta de datos 
     * desde archivos externos, aplicando heurísticas para corregir errores 
     * comunes de digitación y desplazamientos de columnas en el Excel.
     */
    public function importExcel(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $request->validate(['inputFile' => 'required|mimes:xlsx,xls,csv,xlsm|max:10240']);
            $file = $request->file('inputFile');

            Log::info('=== IMPORTACIÓN INICIADA ===');
            Log::info('Archivo: ' . $file->getClientOriginalName());
            Log::info('Usuario: ' . Auth::user()->email);

            $rows = SimpleExcelReader::create($file->getRealPath(), $file->getClientOriginalExtension())->getRows();

            if ($rows->isEmpty()) throw new \Exception('Archivo vacío o formato inválido');

            Log::info('Total de filas en el archivo: ' . count($rows));

            $importCount = 0;
            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];
            $bloqueRad = BloqueWorkflow::where('codigo', 'REV1')->first();
            $estadoRad = $bloqueRad->estadoInicial ?? null;

            Log::info('Bloque REV1 encontrado: ' . ($bloqueRad ? 'SÍ' : 'NO'));

            foreach ($rows as $index => $row) {
                $filaActual = $index + 1;
                try {
                    // 1. NORMALIZACIÓN DE LLAVES
                    // Los archivos Excel varían en encabezados y pueden contener saltos de línea (\n).
                    // Normalizamos a mayúsculas, quitamos espacios y caracteres de control.
                    $data = [];
                    foreach ($row as $k => $v) {
                        $cleanKey = strtoupper(trim(str_replace(["\n", "\r", "\t"], ' ', (string)$k)));
                        $data[$cleanKey] = $v;
                    }

                    // Log de columnas encontradas (solo para la primera fila útil)
                    if ($filaActual === 1 || $filaActual === 2) {
                        Log::info("Fila $filaActual - Columnas detectadas: " . json_encode(array_keys($data)));
                    }

                    $numContrato = strtoupper(trim($this->getColumnValue($data, ['NUMERO DE CONTRATO', 'N° CONTRATO', 'CONTRATO'])));

                    if (empty($numContrato)) {
                        Log::warning("Fila $filaActual: No tiene NUMERO DE CONTRATO, saltando");
                        continue;
                    }

                    Log::info("Fila $filaActual: Procesando contrato $numContrato");

                    // 2. TRANSACCIONALIDAD ATÓMICA
                    // Aseguramos que se cree el Contratista AND Contrato AND Cuenta o nada se guarde.
                    DB::transaction(function () use ($data, &$importCount, &$createdCount, &$updatedCount, $filaActual, $numContrato, $bloqueRad, $estadoRad) {

                        // 3. HEURÍSTICA DE DESPLAZAMIENTO (Shift Detection)
                        $rawFechaRP = $this->getColumnValue($data, 'FECHA RP');
                        $rawValorRP = $this->getColumnValue($data, ['VALOR RP', 'VALOR CONTRATO']);

                        if (is_string($rawFechaRP) && str_contains($rawFechaRP, '$')) {
                            $finalValorRP = $rawFechaRP;
                            $finalFechaRP = $rawValorRP;
                        } else {
                            $finalValorRP = $rawValorRP;
                            $finalFechaRP = $rawFechaRP;
                        }

                        // 4. ENTIDADES RELACIONADAS (UPSERT)
                        $nit = strtoupper(trim($this->getColumnValue($data, ['CEDULA', 'NIT', 'CÉDULA'], '0')));
                        $contratista = Contratista::updateOrCreate(['nit' => $nit], [
                            'razon_social' => strtoupper(trim($this->getColumnValue($data, 'CONTRATISTA', 'SIN NOMBRE'))),
                            'tipo_persona' => (strlen($nit) > 10) ? 'JURIDICA' : 'NATURAL',
                        ]);

                        $supervisorName = $this->getColumnValue($data, 'SUPERVISOR', 'PENDIENTE');
                        $supervisorParts = $this->splitFullName($supervisorName);
                        $supervisor = Supervisor::updateOrCreate(
                            ['nombres' => $supervisorParts['nombres'], 'apellidos' => $supervisorParts['apellidos']],
                            ['cargo' => 'SUPERVISOR']
                        );

                        // 5. PERSISTENCIA DEL CONTRATO (UPSERT)
                        $modalidad = Modalidad::updateOrCreate(['nombre' => strtoupper(trim($this->getColumnValue($data, 'MODALIDAD', 'PRESTACIÓN DE SERVICIOS')))]);
                        $concepto = Concepto::updateOrCreate(['nombre' => strtoupper(trim($this->getColumnValue($data, 'CONCEPTO', 'APOYO A LA GESTIÓN')))]);
                        $planta = Planta::firstOrCreate(['codigo' => 'P001'], ['nombre' => 'PLANTA CENTRAL']);

                        $contrato = Contrato::where(DB::raw('UPPER(TRIM(numero_contrato))'), $numContrato)->first();
                        $contratoData = [
                            'contratista_id' => $contratista->id,
                            'supervisor_id' => $supervisor->id,
                            'modalidad_id' => $modalidad->id,
                            'planta_id' => $planta->id,
                            'concepto_id' => $concepto->id,
                            'monto_total' => $this->parseAmount($finalValorRP ?? 0),
                            'fecha_inicio' => $this->parseDate($this->getColumnValue($data, 'FECHA DE INICIO')),
                            'fecha_fin' => $this->parseDate($this->getColumnValue($data, 'FECHA DE TERMINACIÓN')),
                            'es_activo' => true,
                        ];

                        if ($contrato) {
                            $contrato->update($contratoData);
                        } else {
                            $contratoData['numero_contrato'] = $numContrato;
                            $contrato = Contrato::create($contratoData);
                        }

                        // 5.1 Registro Presupuestal (UPSERT)
                        $rpNum = $this->getColumnValue($data, ['RP', 'REGISTRO PRESUPUESTAL']);
                        if (! empty($rpNum)) {
                            RegistroPresupuestal::updateOrCreate(
                                ['numero_rp' => $rpNum, 'contrato_id' => $contrato->id],
                                [
                                    'fecha_rp' => $this->parseDate($finalFechaRP),
                                    'valor_rp' => $this->parseAmount($finalValorRP ?? 0),
                                ]
                            );
                        }

                        // 5.2 Seguridad Social (UPSERT)
                        $this->crearSeguridadSocial($data, $contratista);

                        // 6. INICIALIZACIÓN DEL WORKFLOW
                        $estaFinalizada = (strtoupper(trim($this->getColumnValue($data, 'RADICADA EN HACIENDA', ''))) === 'SI');

                        $numeroCuenta = $this->normalizeAccountNumber($this->getColumnValue($data, ['NUMERO DE CUENTA EN PROCESO DE CUENTAS', 'N° CUENTA']));
                        $pagosTotalesRaw = $this->getColumnValue($data, 'NUMERO DE PAGOS TOTALES');
                        $pagosTotales = (! empty($pagosTotalesRaw) && $pagosTotalesRaw != 0) ? (int) $pagosTotalesRaw : null;

                        $valorTotalContrato = $this->parseAmount($finalValorRP ?? 0);

                        // Buscar si ya existe la cuenta usando coincidencia exacta de string
                        $cuentaExistente = CuentaCobro::where('contrato_id', $contrato->id)
                            ->where('numero_cuenta', $numeroCuenta)
                            ->first();

                        // DETERMINACIÓN DE LA ETAPA ACTUAL DEL FLUJO
                        // Prioridad: 1. Finalizada | 2. Por hitos (rev1 -> sap -> fac -> fir -> hac)
                        
                        $bloqueActual = null;
                        $estadoActual = null;

                        if ($estaFinalizada) {
                            $bloqueActual = BloqueWorkflow::where('codigo', 'FIN')->first();
                            $estadoActual = $bloqueActual?->estadoInicial;
                        } else {
                            // Definición de hitos en orden cronológico inverso (el más avanzado gana)
                            $mapeoHitos = [
                                'HAC' => ['col' => 'RADICADA EN HACIENDA', 'default' => 'HAC_ESP'],
                                'FIR' => ['col' => 'FIRMA SECRETARIO', 'default' => 'FIR_ESP'],
                                'FAC' => ['col' => 'EN FACTURACIÓN', 'default' => 'FAC_ESP'],
                                'SAP' => ['col' => 'ENVIADA A INGRESO MERCANCIA SAP', 'default' => 'SAP_ESP'],
                                'REV1' => ['col' => 'ESTADO TRAS PRIMERA REVISIÓN', 'default' => 'REV1_SIN']
                            ];

                            foreach ($mapeoHitos as $codigoBloque => $config) {
                                $valorCelda = trim($this->getColumnValue($data, $config['col'], ''));
                                
                                if (!empty($valorCelda) && strtoupper($valorCelda) !== 'N/A' && $valorCelda !== '0') {
                                    $bloqueActual = BloqueWorkflow::where('codigo', $codigoBloque)->first();
                                    
                                    if ($bloqueActual) {
                                        // Intentamos buscar el estado exacto por nombre en ese bloque
                                        $estadoActual = EstadoWorkflow::where('bloque_id', $bloqueActual->id)
                                            ->where('nombre', 'ilike', "%$valorCelda%")
                                            ->first();
                                        
                                        // Si no encontramos match por nombre, usamos el inicial del bloque o el default por código
                                        if (!$estadoActual) {
                                            $estadoActual = $this->getStateIdByCode($config['default']) 
                                                ? EstadoWorkflow::find($this->getStateIdByCode($config['default']))
                                                : $bloqueActual->estadoInicial;
                                        }
                                        break; 
                                    }
                                }
                            }

                            // Fallback total: Si nada coincide y la cuenta es nueva, va a Revisión Inicial
                            if (!$bloqueActual) {
                                $bloqueActual = BloqueWorkflow::where('codigo', 'REV1')->first();
                                $estadoActual = $bloqueActual?->estadoInicial;
                            }

                            // AUTO-ADVANCE RECURSIVO: Si el estado determinado es "final" para su bloque, 
                            // avanzamos automáticamente al siguiente bloque hasta encontrar uno donde deba esperar.
                            // Esto resuelve el problema de contratos que se quedan "estancados" en estados de salida.
                            while ($estadoActual && $estadoActual->es_final && $bloqueActual && $bloqueActual->codigo !== 'FIN') {
                                $prevBloqueCodigo = $bloqueActual->codigo;
                                $siguienteBloque = BloqueWorkflow::where('orden', '>', $bloqueActual->orden)
                                    ->orderBy('orden')->first();
                                
                                if (!$siguienteBloque) break;

                                Log::info("  Auto-avance: El contrato estaba en estado final de {$prevBloqueCodigo}, movido a {$siguienteBloque->codigo}");
                                
                                $bloqueActual = $siguienteBloque;
                                $estadoActual = $siguienteBloque->estadoInicial;
                                
                                // Si el siguiente bloque no tiene estado inicial, paramos para evitar bucles
                                if (!$estadoActual) break;
                            }

                            // Preservar estado si la cuenta ya existía y el archivo no trae avances nuevos
                            if ($cuentaExistente && $bloqueActual?->codigo === 'REV1' && $estadoActual?->codigo === 'REV1_SIN') {
                                $bloqueActualId = $cuentaExistente->bloque_actual_id;
                                $estadoActualId = $cuentaExistente->estado_actual_id;
                            } else {
                                $bloqueActualId = $bloqueActual?->id;
                                $estadoActualId = $estadoActual?->id;
                            }
                        }

                        // Validación final antes de persistir
                        if (!$bloqueActualId || !$estadoActualId) {
                            throw new \Exception("No se pudo determinar un estado válido para el contrato $numContrato");
                        }

                        $cuenta = CuentaCobro::updateOrCreate(
                            ['contrato_id' => $contrato->id, 'numero_cuenta' => (string)$numeroCuenta],
                            [
                                'valor_cobro' => ($pagosTotales && $pagosTotales > 0) ? ($valorTotalContrato / $pagosTotales) : $valorTotalContrato,
                                'bloque_actual_id' => $bloqueActualId,
                                'estado_actual_id' => $estadoActualId,
                                'finalizada' => $estaFinalizada || ($cuentaExistente->finalizada ?? false),
                                'numero_pagos_totales' => $pagosTotales,
                                'numero_facturas_radicadas' => $this->parseAmount($this->getColumnValue($data, 'N° DE FACTURAS RADICADA HACIENDA', 0)),
                                'radicado_por' => strtoupper(trim($this->getColumnValue($data, 'RADICADO POR') ?? '')),
                                'fecha_radicacion' => $this->parseDate($this->getColumnValue($data, 'FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES')),
                                'responsable_actual_id' => Auth::id(),
                                'observaciones' => $this->getColumnValue($data, 'OBSERVATIONS') ?? $this->getColumnValue($data, 'OBSERVACIONES'),
                                'ss_ultima_cuenta' => $this->getColumnValue($data, ['PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA', 'PLANILLA SEGURIDAD', 'PLANILLA SEGURIDAD SOCIAL', 'SS ULTIMA CUENTA', 'PLANILLA SEG']),
                            ]
                        );

                        Log::info("Fila $filaActual: Cuenta cobro creada/actualizada ID: " . $cuenta->id);

                        // 7. RECONSTRUCCIÓN HISTÓRICA
                        // Si el Excel trae fechas de hitos previos (SAP, Facturación), las inyectamos como historial.
                        $this->procesarBloquesHistoricos($cuenta, $data);

                        if ($cuenta->wasRecentlyCreated) {
                            $createdCount++;
                        } else {
                            $updatedCount++;
                        }

                        $importCount++;
                        Log::info("Fila $filaActual: ✅ ÉXITO");
                    });
                } catch (\Exception $e) {
                    $errorMsg = "Fila $filaActual: " . $e->getMessage();
                    $errors[] = $errorMsg;
                    Log::error($errorMsg);
                    Log::error($e->getTraceAsString());
                }
            }

            $summary = "Importación finalizada. Total: $importCount ($createdCount creados, $updatedCount actualizados). Errores: " . count($errors);
            Log::info('=== IMPORTACIÓN FINALIZADA ===');
            Log::info($summary);

            Contrato::logManualAudit(null, 'IMPORT_EXCEL', $summary, 'cuentas_cobro');

            return response()->json(['success' => true, 'message' => $summary, 'errors' => $errors]);
        } catch (\Exception $e) {
            Log::error('ERROR GENERAL EN IMPORTACIÓN: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'importExcel']);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function storeManual(Request $request)
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
                
                $contratista = Contratista::updateOrCreate(
                    ['nit' => $nit],
                    array_filter([
                        'razon_social' => !empty($razonSocial) ? $razonSocial : 'SIN NOMBRE',
                        'tipo_persona' => (strlen($nit) > 10) ? 'JURIDICA' : 'NATURAL',
                    ])
                );

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
        } catch (\Exception $e) {
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'update', 'id' => $id]);

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
            'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS',
        ];

        return SimpleExcelWriter::streamDownload('plantilla_cuentas_cobro.xlsx')
            ->noHeaderRow()
            ->addRow($headers)
            ->toBrowser();
    }

    private function parseDate($value)
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
    private function procesarBloquesHistoricos($cuenta, $data)
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

    private function normalizeAccountNumber($value): string
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

    private function parseAmount($value)
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
    private function getColumnValue(&$data, $keys, $default = null)
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
