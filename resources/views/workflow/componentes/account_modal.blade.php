<div class="modal fade" id="modalCuenta{{ $cuenta->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-block position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="bi bi-file-earmark-diff me-2"></i>Gestión de Cuenta #{{ $cuenta->numero_cuenta }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                @if ($canEdit)
                    <div class="status-buttons-row" id="statusButtons{{ $cuenta->id }}">
                        <div class="spinner-border spinner-border-sm text-light" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-body">
                <div id="newCuentaBanner{{ $cuenta->id }}" class="alert alert-success d-none mb-3" role="alert" style="border-radius: 12px;">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Nueva cuenta creada correctamente para este contrato. Esta es la cuenta recién iniciada.
                </div>
                <div class="info-cards-row">
                    <div class="info-card">
                        <div class="info-card-icon"><i class="fas fa-file-contract"></i></div>
                        <div class="info-card-title">Detalles del
                            Contrato</div>
                        <div class="info-card-content">
                            <div><strong>Contrato
                                    #</strong>{{ $cuenta->contrato?->numero_contrato ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Cliente:</strong>{{ Str::limit($cuenta->contrato?->contratista?->nombre_completo ?? 'N/A', 30) }}
                            </div>
                            <div>
                                <strong>Valor:</strong>${{ number_format($cuenta->valor_cobro, 2) }}
                            </div>
                            <div class="mt-1 pt-1 border-top">
                                <span class="badge bg-primary-soft text-primary" style="font-size: 0.75rem;">
                                    <i class="fas fa-calendar-check me-1"></i> SS Mes:
                                    {{ $cuenta->ss_ultima_cuenta ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon"><i class="fas fa-tasks"></i></div>
                        <div class="info-card-title">Estado Actual
                        </div>
                        <div class="info-card-content">
                            <div><strong>Estado:</strong><span
                                    id="estadoActual{{ $cuenta->id }}">{{ $cuenta->estadoActual?->nombre ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <strong>Fecha:</strong>{{ $cuenta->estadosBloques->where('bloque_id', $cuenta->bloque_actual_id)->first()?->fecha_ingreso_bloque?->format('d/m/Y') ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Responsable:</strong>{{ $cuenta->responsableActual?->primer_nombre ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon"><i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="info-card-title">Tesorería
                        </div>
                        <div class="info-card-content">
                            <div><strong>Factura
                                    Pendiente</strong></div>
                            <div><strong>Monto
                                    Aprobado:</strong>${{ number_format($cuenta->valor_cobro, 2) }}
                            </div>
                            <div><strong>Última
                                    Actividad:</strong>{{ $cuenta->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="timeline-section-title d-flex align-items-center">
                    <i class="fas fa-history me-2"></i>Línea de Tiempo
                    <span class="badge bg-white text-primary border ms-3 shadow-sm px-3 py-2"
                        style="font-size: 0.8rem; border-radius: 20px;">
                        <i class="fas fa-clock me-1 text-primary-light"></i> <span
                            class="text-muted small fw-normal">Total:</span> {{ $cuenta->tiempo_total_ejecucion }}
                    </span>
                </div>
                <div class="timeline-container" id="timeline{{ $cuenta->id }}">
                    @php
                        $timelineCompleta = collect($cuenta->timeline_completa)
                            ->sortByDesc(function ($evento) {
                                $fecha = data_get($evento, 'fecha');
                                return $fecha ? $fecha->timestamp : 0;
                            })
                            ->values();
                    @endphp

                    @forelse ($timelineCompleta as $evento)
                        @php
                            $fecha = data_get($evento, 'fecha');
                            $fechaFin = data_get($evento, 'fecha_fin');
                            $fuente = data_get($evento, 'fuente', 'historial');
                            $tipo = data_get($evento, 'tipo', 'transicion');
                            $estadoDestino = data_get($evento, 'estado_destino', []);
                            $estadoOrigen = data_get($evento, 'estado_origen', []);
                            $bloque = data_get($evento, 'bloque', []);
                            $usuario = data_get($evento, 'usuario_accion.nombre', 'Sistema');
                            $comentarios = data_get($evento, 'comentarios');
                            $tiempoFormateado = data_get($evento, 'tiempo_formateado');
                            $esReconstruido = (bool) data_get($evento, 'reconstruido', false);
                            $esDevolucion = (bool) data_get($evento, 'es_devolucion', false);
                            $esDevolucionSupervisor = (bool) data_get($evento, 'es_devolucion_supervisor', false);
                            $responsableDestinoNombre = data_get($evento, 'responsable_destino_nombre');
                            $supervisorDestinoNombre = data_get($evento, 'supervisor_destino_nombre');
                            $tipoDestino = data_get($estadoDestino, 'tipo', 'INICIAL');
                            $colorHex = data_get($estadoDestino, 'color_hex') ?? '#6c757d';
                            $icon = match ($tipoDestino) {
                                'APROBADO', 'FINAL' => 'fa-check',
                                'DEVUELTO' => 'fa-times',
                                'EN_PROCESO' => 'fa-sync',
                                'INICIAL' => 'fa-play',
                                default => 'fa-circle',
                            };
                            $badgeClass = match ($tipoDestino) {
                                'APROBADO', 'FINAL' => 'bg-success-timeline',
                                'DEVUELTO' => 'bg-danger-timeline',
                                'EN_PROCESO' => 'bg-warning-timeline',
                                'INICIAL' => 'bg-info-timeline',
                                default => 'bg-secondary-timeline',
                            };
                        @endphp
                        <div class="timeline-item">
                            <div class="timeline-marker-wrapper">
                                <div class="timeline-marker"
                                    style="background-color: {{ $colorHex }}; box-shadow: 0 4px 10px {{ $colorHex }}44;">
                                    <i class="fas {{ $icon }}"></i>
                                </div>
                                @if (!$loop->last)
                                    <div class="timeline-line"></div>
                                @endif
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div class="timeline-title d-flex align-items-center flex-wrap gap-2">
                                        <span>{{ $tipo === 'bloque' ? ($bloque['nombre'] ?? 'Bloque') : ($estadoDestino['nombre'] ?? 'Estado') }}</span>
                                        @if ($esReconstruido)
                                            <span class="badge bg-light text-dark border">Reconstruido</span>
                                        @elseif ($esDevolucion)
                                            @if ($esDevolucionSupervisor)
                                                <span class="badge bg-danger text-white">Devuelto al supervisor</span>
                                                @if ($supervisorDestinoNombre)
                                                    <span class="badge bg-warning text-dark">Supervisor: {{ $supervisorDestinoNombre }}</span>
                                                @endif
                                            @else
                                                <span class="badge bg-danger text-white">Devuelto al responsable</span>
                                            @endif
                                        @else
                                            <span class="badge {{ $badgeClass }}">{{ $fuente === 'historial' ? 'Transición' : 'Bloque' }}</span>
                                        @endif
                                    </div>
                                    <div class="timeline-date">Fecha:
                                        {{ $fecha ? $fecha->format('d/m/Y - H:i A') : 'N/A' }}
                                        @if ($fechaFin)
                                            <span class="text-muted">hasta {{ $fechaFin->format('d/m/Y - H:i A') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="timeline-transition">
                                    <div class="mb-1 d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-layer-group me-1"></i>
                                            {{ $bloque['nombre'] ?? ($tipo === 'bloque' ? 'Bloque' : data_get($estadoOrigen, 'nombre', 'Bloque')) }}
                                        </span>
                                        @if ($tipo === 'transicion')
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-sign-out-alt me-1"></i>
                                                Destino: {{ $estadoDestino['nombre'] ?? 'N/A' }}
                                            </span>
                                            @if ($esDevolucion && $esDevolucionSupervisor && $supervisorDestinoNombre)
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-user-shield me-1"></i>
                                                    Supervisor: {{ $supervisorDestinoNombre }}
                                                </span>
                                            @elseif ($esDevolucion && $responsableDestinoNombre)
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-user-tag me-1"></i>
                                                    Responsable: {{ $responsableDestinoNombre }}
                                                </span>
                                            @endif
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-clock me-1"></i>
                                                Duración: {{ $tiempoFormateado ?? '0s' }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Estado actual {{ $estadoDestino['nombre'] ?? 'N/A' }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($tipo !== 'transicion')
                                        <div class="small">
                                            <span class="text-muted">Referencia:</span> {{ $estadoOrigen['nombre'] ?? 'Inicio del flujo' }}
                                        </div>
                                    @endif
                                </div>

                                <div class="timeline-meta">
                                    <div class="timeline-meta-item">
                                        <img
                                            src="https://ui-avatars.com/api/?name={{ urlencode(explode(' ', $usuario)[0] ?? 'S') }}&size=24&background=random"
                                            alt="avatar"
                                            style="width: 24px; height: 24px; border-radius: 50%; margin-right: 5px;">
                                        <span>{{ $usuario }} ({{ $fuente === 'historial' ? 'Usuario' : 'Registro' }})</span>
                                    </div>
                                </div>

                                @if ($comentarios)
                                    <div class="timeline-comment">
                                        <i class="fas fa-comment me-2"></i>
                                        <span class="timeline-comment-text">Comentario: {{ $comentarios }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small border rounded bg-white">
                            No hay información histórica suficiente para reconstruir la línea de tiempo.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal"
                    style="border-radius:12px;">
                    <i class="bi bi-x-lg me-1"></i> Cerrar
                </button>
                @php
                    $totalCuentasContrato = $cuenta->contrato?->cuentasCobro()
                        ->whereNull('deleted_at')
                        ->count() ?? 0;
                    $limiteCuentas = (int) ($cuenta->contrato?->cuentasCobro()
                        ->max('numero_pagos_totales') ?? 0) ?: 1;
                        
                    $cuentasParaMax = $cuenta->contrato?->cuentasCobro()
                        ->whereNull('deleted_at')
                        ->get() ?? collect();
                    $maxNumeroCuenta = (int) $cuentasParaMax->max(fn($c) => (int)$c->numero_cuenta);
                    $nextCuenta = $maxNumeroCuenta + 1;

                    $puedeCrearParalela = $cuenta->contrato && $nextCuenta <= $limiteCuentas && $totalCuentasContrato < $limiteCuentas;
                @endphp
                @if ($puedeCrearParalela)
                    <button type="button"
                        class="btn btn-warning px-4"
                        style="border-radius:12px;"
                        onclick="startParallelAccount({{ $cuenta->id }}, '{{ $cuenta->contrato?->numero_contrato }}', {{ $nextCuenta }})">
                        <i class="bi bi-plus-circle me-1"></i> Iniciar cuenta paralela (#{{ $nextCuenta }})
                    </button>
                @elseif ($cuenta->contrato)
                    <span class="badge bg-secondary px-3 py-2">
                        <i class="bi bi-lock me-1"></i> Límite ({{ $limiteCuentas }})
                    </span>
                @endif
                <a href="{{ route('dashboard') }}?searchContrato={{ $cuenta->contrato?->numero_contrato }}"
                    class="btn btn-premium-confirm">
                    <i class="bi bi-speedometer2 me-1"></i> Ver en Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
