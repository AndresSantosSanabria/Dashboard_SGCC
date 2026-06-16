
{{-- SELECTORES DE BLOQUE (TABS INTERACTIVOS) --}}
<div class="workflow-selectors animate-in mb-4">
    @foreach ($workflow as $key => $block)
        <div class="workflow-selector selector-{{ $block['color'] ?? 'morado' }} {{ $loop->first ? 'active' : '' }}" 
             data-block-id="{{ $key }}" 
             onclick="selectWorkflowBlock('{{ $key }}')">
            <div class="selector-icon">
                <i class="fas fa-{{ $key == 1 ? 'file-medical' : ($key == 2 ? 'cog' : ($key == 3 ? 'file-invoice-dollar' : ($key == 4 ? 'signature' : ($key == 5 ? 'university' : 'archive')))) }}"></i>
            </div>
            <div class="selector-info">
                <div class="selector-title">{{ $block['nombre'] }}</div>
                <div class="selector-count">
                    {{ 
                        count(
                            collect($block['columnas'])
                                ->filter(function($col) {
                                    $nombre = strtoupper($col['nombre'] ?? '');
                                    return $nombre !== 'SIN TRAMITE' && $nombre !== 'SIN TRÁMITE';
                                })
                                ->pluck('cuentas')
                                ->flatten(1)
                        ) 
                    }} Casos
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- PANELES DE TABLERO (SOLO UNO VISIBLE) --}}
<div class="workflow-panels">
    @foreach ($workflow as $key => $block)
        <div class="workflow-panel {{ $loop->first ? 'active' : '' }}" id="panel-{{ $key }}">
            <div class="workflow-block">
                <div class="block-header block-{{ $block['color'] }}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-layer-group me-2"></i>
                        <span>{{ $block['nombre'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark shadow-sm">
                            {{ 
                                count(
                                    collect($block['columnas'])
                                        ->filter(function($col) {
                                            $nombre = strtoupper($col['nombre'] ?? '');
                                            return $nombre !== 'SIN TRAMITE' && $nombre !== 'SIN TRÁMITE';
                                        })
                                        ->pluck('cuentas')
                                        ->flatten(1)
                                ) 
                            }} Cuentas
                        </span>
                        <button class="btn-collapse" onclick="toggleBlockVisibility(this, '{{ $key }}')">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                    </div>
                </div>
                <div class="kanban-board">
                    @foreach ($block['columnas'] as $estadoId => $columna)
                        <div class="kanban-column">
                            <div class="column-title d-flex align-items-center">
                                <div class="me-2" style="width: 8px; height: 8px; border-radius: 50%; background-color: {{ $columna['color_hex'] ?? '#adb5bd' }}; shadow: 0 0 5px {{ $columna['color_hex'] }}44;"></div>
                                <span class="text-capitalize flex-grow-1">{{ $columna['nombre'] }}</span>
                                <span
                                    class="badge rounded-pill bg-white text-dark shadow-sm">{{ count($columna['cuentas']) }}</span>
                            </div>
                            <div class="column-content drop-zone" data-estado-id="{{ $estadoId }}"
                                data-bloque-id="{{ $block['id'] ?? $key }}">
                                @forelse($columna['cuentas'] as $cuenta)
                                    @php
                                        $alertClass = 'alert-amarillo';
                                        if ($columna['tipo'] === 'DEVUELTO') {
                                            $alertClass = 'alert-rojo';
                                        }
                                        if ($columna['tipo'] === 'APROBADO' || $columna['tipo'] === 'FINAL') {
                                            $alertClass = 'alert-verde';
                                        }
                                        $estadoBloqueActual = $cuenta->estadosBloques
                                            ->where('bloque_id', $cuenta->bloque_actual_id)
                                            ->first();
                                        $fechaIngreso =
                                            $estadoBloqueActual?->fecha_ultima_actualizacion ??
                                            ($estadoBloqueActual?->fecha_ingreso_bloque ?? $cuenta->created_at);
                                        
                                        // LOGICA DE TIEMPO INDIVIDUALIZADO
                                        // 1. Buscamos en el historial del workflow cuándo entró exactamente a este estado
                                        $transicionActual = $cuenta->historialWorkflow
                                            ->where('estado_destino_id', $cuenta->estado_actual_id)
                                            ->sortByDesc('fecha_transicion')
                                            ->first();

                                        $fechaInicioReal = $transicionActual?->fecha_transicion 
                                            ?? ($estadoBloqueActual?->fecha_ingreso_bloque ?? $cuenta->created_at);

                                        // 2. Usa el helper ANTI-BUG que filtra automáticamente por sesión actual
                                        // Esto previene la herencia de tiempos cuando la cuenta regresa a un estado
                                        $elapsedSeconds = \App\Models\TaskTimeLog::getElapsedTimeForCurrentState($cuenta);
                                        $timerPausado = $cuenta->estaPausadaPorSupervisorReturn();
                                    @endphp
                                    <div class="account-card" 
                                        style="border-left-color: {{ $cuenta->estadoActual?->color_hex ?? '#6366f1' }};"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCuenta{{ $cuenta->id }}" data-cuenta-id="{{ $cuenta->id }}">
                                        
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="card-id">#{{ $cuenta->contrato?->numero_contrato ?? 'N/A' }}</div>
                                            <span class="state-badge"
                                                style="background-color: {{ $cuenta->estadoActual?->color_hex }}15; color: {{ $cuenta->estadoActual?->color_hex }}; border: 1px solid {{ $cuenta->estadoActual?->color_hex }}44; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                                {{ $cuenta->estadoActual?->nombre }}
                                            </span>
                                        </div>

                                        <div class="card-contractor"
                                            title="{{ $cuenta->contratista?->nombre_completo }}">
                                            {{ Str::limit($cuenta->contratista?->nombre_completo ?? 'N/A', 40) }}
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="small fw-600 text-muted" style="font-size: 0.7rem;">
                                                <i class="bi bi-stack me-1"></i>Cuenta {{ $cuenta->numero_cuenta }}
                                            </div>
                                            @if ($cuenta->estadoActual && $cuenta->estadoActual->contabiliza_tiempo && !($cuenta->estadoActual->bloque_id == ($ultimoBloqueId ?? 6) && $cuenta->estadoActual->es_final))
                                                @php
                                                    $minutosTranscurridos = $elapsedSeconds / 60;
                                                    $timerAlertClass = '';
                                                    if ($minutosTranscurridos >= ($umbralCritico ?? 20)) {
                                                        $timerAlertClass = 'bg-danger text-white border-danger shadow-sm';
                                                    } elseif ($minutosTranscurridos >= ($umbralInformativo ?? 10)) {
                                                        $timerAlertClass = 'bg-warning text-dark border-warning shadow-sm';
                                                    }
                                                @endphp
                                                <div class="d-flex flex-column align-items-end">
                                                    <span class="timer-badge {{ $timerPausado ? 'bg-secondary text-white border-secondary shadow-sm' : $timerAlertClass }}"
                                                          data-elapsed="{{ $elapsedSeconds }}"
                                                          data-paused="{{ $timerPausado ? '1' : '0' }}" 
                                                          style="{{ $timerAlertClass ? 'padding: 3px 8px; border-radius: 6px; font-weight: 700;' : '' }}">
                                                        <i class="bi {{ $timerPausado ? 'bi-pause-circle-fill' : ($timerAlertClass ? 'bi-exclamation-octagon-fill' : 'bi-clock-history') }}"></i>
                                                        <span class="elapsed-time">{{ $timerPausado ? 'Pausado' : $businessTime->formatCalendarInterval($elapsedSeconds) }}</span>
                                                    </span>
                                                    <div class="extra-small text-muted mt-1" style="font-size: 0.6rem; opacity: 0.8;">
                                                        Total: {{ $cuenta->tiempo_total_ejecucion }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="pt-3 border-top mt-2 card-footer-info">
                                            <span>
                                                <i class="bi bi-person-badge me-1"></i>
                                                {{ Str::before($cuenta->responsableActual?->primer_nombre ?? 'Sin asignar', ' ') }}
                                            </span>
                                            <span>
                                                {{ $cuenta->created_at->format('d M') }}
                                            </span>
                                        </div>
                                    </div>
                                    @include('workflow.componentes.account_modal')
                                @empty <div class="text-center py-4 text-muted small border rounded bg-white">
                                        Sin cuentas </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
