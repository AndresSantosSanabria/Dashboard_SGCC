<div class="modal fade" id="modalCuenta{{ $cuenta->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 12px;">
            <div class="modal-header-custom">
                <div class="modal-title-custom">Cambiar Estado de la Cuenta
                </div>
                @if ($canEdit)
                    <div class="status-buttons-row" id="statusButtons{{ $cuenta->id }}">
                        <div class="spinner-border spinner-border-sm text-light" role="status"><span
                                class="visually-hidden">Cargando...</span></div>
                    </div>
                @endif
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                    style="position: absolute; top: 20px; right: 20px;"></button>
            </div>
            <div class="modal-body" style="padding: 30px;">
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
                    @foreach ($cuenta->historialWorkflow->sortBy([['fecha_transicion', 'desc'], ['id', 'desc']]) as $index => $hist)
                        @php
                            $tipoDestino = $hist->estadoDestino?->tipo ?? 'INICIAL';
                            $colorClass = match ($tipoDestino) {
                                'APROBADO', 'FINAL' => 'bg-success-timeline',
                                'DEVUELTO' => 'bg-danger-timeline',
                                'EN_PROCESO' => 'bg-warning-timeline',
                                'INICIAL' => 'bg-info-timeline',
                                default => 'bg-secondary-timeline',
                            };
                            $icon = match ($tipoDestino) {
                                'APROBADO', 'FINAL' => 'fa-check',
                                'DEVUELTO' => 'fa-times',
                                'EN_PROCESO' => 'fa-sync',
                                'INICIAL' => 'fa-play',
                                default => 'fa-circle',
                            };
                        @endphp
                        <div class="timeline-item">
                            <div class="timeline-marker-wrapper">
                                <div class="timeline-marker {{ $colorClass }}">
                                    <i class="fas {{ $icon }}"></i>
                                </div>
                                @if (!$loop->last)
                                    <div class="timeline-line"></div>
                                @endif
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div class="timeline-title">
                                        {{ $hist->estadoDestino?->nombre }}
                                    </div>
                                    <div class="timeline-date">Fecha:
                                        {{ $hist->fecha_transicion->format('d/m/Y - H:i A') }}
                                    </div>
                                </div>
                                @if ($hist->estadoOrigen)
                                    <div class="timeline-transition">
                                        <div class="mb-1">
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-layer-group me-1"></i>
                                                Bloque {{ $hist->estadoOrigen->bloque->codigo ?? $hist->estadoOrigen->bloque_id }} 
                                                <i class="fas fa-arrow-right mx-1"></i> 
                                                Bloque {{ $hist->estadoDestino->bloque->codigo ?? $hist->estadoDestino->bloque_id }}
                                            </span>
                                        </div>
                                        <div class="small">
                                            <span class="text-muted">Estado:</span>
                                            {{ $hist->estadoOrigen->nombre }}
                                            <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                            {{ $hist->estadoDestino->nombre }}
                                        </div>
                                    </div>
                                @endif
                                <div class="timeline-meta">
                                    <div class="timeline-meta-item"><img
                                            src="https://ui-avatars.com/api/?name={{ urlencode($hist->usuarioAccion?->primer_nombre ?? 'S') }}&size=24&background=random"
                                            alt="avatar"
                                            style="width: 24px; height: 24px; border-radius: 50%; margin-right: 5px;"><span>{{ $hist->usuarioAccion?->primer_nombre ?? 'Sistema' }}
                                            (Usuario)
                                        </span></div>
                                    @if ($hist->tiempo_formateado)
                                        <div class="timeline-meta-item">
                                            <i class="far fa-clock"></i><span>Tiempo:
                                                {{ $hist->tiempo_formateado }}</span>
                                        </div>
                                    @endif
                                </div>
                                @if ($hist->comentarios)
                                    <div class="timeline-comment"><i class="fas fa-comment me-2"></i><span
                                            class="timeline-comment-text">Comentario:
                                            {{ $hist->comentarios }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal">Cerrar</button><a
                    href="{{ route('dashboard') }}?searchContrato={{ $cuenta->contrato?->numero_contrato }}"
                    class="btn btn-primary">Ver Dashboard</a></div>
        </div>
    </div>
</div>
