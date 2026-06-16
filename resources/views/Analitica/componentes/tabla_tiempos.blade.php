@php
    $tiempoEquipo = $datos['tiempoEquipo'] ?? collect();
@endphp

<div class="analytic-workflow-container">
    {{-- Header de la sección --}}
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <h6 class="fw-bold text-secondary mb-0">DESGLOSE POR BLOQUES Y ESTADOS</h6>
        <div class="badge bg-light text-primary border px-3">Tiempo Total: {{ $datos['kpis']['general'] }}</div>
    </div>

    @forelse($tiempoEquipo as $index => $item)
        @php
            $maxMinutos = $datos['tiempoEquipo']->max('minutos_totales') ?: 1;
            $porcentaje = ($item['minutos_totales'] / $maxMinutos) * 100;
            $collapseId = "collapseBlock" . $index;
            
            $status = 'Óptimo';
            $badgeClass = 'bg-light text-success border-success';
            if($porcentaje > 70) { 
                $status = 'Crítico'; 
                $badgeClass = 'bg-danger-soft text-danger border-danger'; 
            }
            elseif($porcentaje > 30) { 
                $status = 'Alto'; 
                $badgeClass = 'bg-warning-soft text-warning border-warning'; 
            }
            elseif($porcentaje > 10) { 
                $status = 'Medio'; 
                $badgeClass = 'bg-info-soft text-info border-info'; 
            }
        @endphp
        
        <div class="analytic-item mb-3 animate-in">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    {{-- Encabezado del Bloque (Trigger del Colapso) --}}
                    <div class="p-4 bg-white cursor-pointer hover-bg-light transition-all d-block w-100 border-0 text-start" 
                         role="button"
                         data-bs-toggle="collapse" 
                         data-bs-target="#{{ $collapseId }}" 
                         aria-expanded="false"
                         aria-controls="{{ $collapseId }}">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-4 col-md-5">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-shape bg-primary-soft text-primary rounded-3 p-2">
                                        <i class="bi bi-layers-half fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Bloque Workflow</div>
                                        <div class="fw-bold text-dark h6 mb-0">{{ $item['etapa'] }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-3">
                                <div class="progress" style="height: 6px; border-radius: 10px; background-color: #f1f5f9;">
                                    <div class="progress-bar rounded-pill" role="progressbar" 
                                         style="width: {{ $porcentaje }}%; background: linear-gradient(90deg, #6366f1, #a855f7);"></div>
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-2 text-md-end">
                                <div class="fw-bold text-dark">
                                    {{ app(\App\Http\Controllers\AnaliticaController::class)->formatMinutos($item['minutos_totales']) }}
                                </div>
                            </div>

                            <div class="col-lg-3 col-12 d-flex align-items-center justify-content-lg-end gap-3 mt-lg-0 mt-3">
                                <span class="badge rounded-pill px-3 py-2 {{ $badgeClass }}" style="font-size: 0.7rem;">
                                    {{ $status }}
                                </span>
                                <div class="collapse-arrow bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-chevron-down text-muted transition-all collapse-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Detalle de Estados (Colapsable) --}}
                    <div class="collapse" id="{{ $collapseId }}">
                        @if(isset($item['estados']) && count($item['estados']) > 0)
                            <div class="bg-light-subtle p-4 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-bold text-secondary small text-uppercase">Estados del bloque</div>
                                    @if(!empty($item['tramos']))
                                        <div class="badge bg-white text-primary border">Tramos por {{ $datos['granularidad_tramo'] ?? 'semana' }}</div>
                                    @endif
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                    @foreach($item['estados'] as $estado)
                                        @if($estado['minutos'] > 0)
                                            <div class="col">
                                                <div class="d-flex justify-content-between align-items-center p-3 bg-white border rounded-3 shadow-sm hover-elevate transition-all">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        <div class="dot bg-primary opacity-50" style="width: 6px; height: 6px; border-radius: 50%;"></div>
                                                        <span class="text-secondary small text-truncate" title="{{ $estado['nombre'] }}">{{ $estado['nombre'] }}</span>
                                                    </div>
                                                    <span class="fw-bold text-primary small ms-2">{{ $estado['label'] }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                @if(!empty($item['tramos']))
                                    <div class="mt-4">
                                        <div class="fw-bold text-secondary small text-uppercase mb-3">Tramos temporales</div>
                                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                            @foreach($item['tramos'] as $tramo)
                                                @if($tramo['minutos'] > 0)
                                                    <div class="col">
                                                        <div class="p-3 bg-white border rounded-3 shadow-sm hover-elevate transition-all">
                                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                                <div class="fw-bold text-dark small text-truncate" title="{{ $tramo['etiqueta'] }}">{{ $tramo['etiqueta'] }}</div>
                                                                <span class="fw-bold text-primary small ms-2">{{ $tramo['label'] }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
            <i class="bi bi-clipboard-x fs-1 text-muted opacity-25"></i>
            <p class="text-muted mt-3 mb-0">No se encontraron registros de tiempo para esta selección.</p>
        </div>
    @endforelse
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .hover-bg-light:hover { background-color: #f8fafc !important; }
    
    .collapse-icon {
        transition: transform 0.3s ease;
    }
    
    [aria-expanded="true"] .collapse-icon {
        transform: rotate(180deg);
    }
    
    .bg-primary-soft { background-color: rgba(99, 102, 241, 0.1); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
    .bg-warning-soft { background-color: rgba(245, 158, 11, 0.1); }
    .bg-info-soft { background-color: rgba(6, 182, 212, 0.1); }
    .bg-light-subtle { background-color: #f8fafc; }
    
    .hover-elevate:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
    }
    
    .transition-all { transition: all 0.3s ease; }
    
    .analytic-item {
        animation: slideUp 0.5s ease-out forwards;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
