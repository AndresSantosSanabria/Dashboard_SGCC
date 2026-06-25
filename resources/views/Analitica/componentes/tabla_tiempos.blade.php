@php
    $tiempoEquipo = $datos['tiempoEquipo'] ?? collect();
    $maxMinutosGlobal = $tiempoEquipo->max('minutos_totales') ?: 1;
@endphp

<div class="analytic-workflow-container">
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <span class="section-title mb-0">DESGLOSE POR BLOQUES Y ESTADOS</span>
        <span class="badge bg-sapphire-soft text-sapphire border-0 px-3" style="font-size:0.75rem">Tiempo Total: {{ $datos['kpis']['general'] }}</span>
    </div>

    @forelse($tiempoEquipo as $index => $item)
        @php
            $porcentaje = $maxMinutosGlobal > 0 ? ($item['minutos_totales'] / $maxMinutosGlobal) * 100 : 0;
            $collapseId = "collapseBlock" . $index;

            if ($porcentaje > 70) {
                $status = 'Crítico';
                $statusClass = 'text-ruby border-ruby';
                $barColor = '#BE123C';
                $barBg = 'bg-ruby-soft';
                $accentColor = 'var(--ruby)';
            } elseif ($porcentaje > 30) {
                $status = 'Alto';
                $statusClass = 'text-amber border-amber';
                $barColor = '#B45309';
                $barBg = 'bg-amber-soft';
                $accentColor = 'var(--amber)';
            } elseif ($porcentaje > 10) {
                $status = 'Medio';
                $statusClass = 'text-sapphire border-sapphire';
                $barColor = '#1D4ED8';
                $barBg = 'bg-sapphire-soft';
                $accentColor = 'var(--sapphire)';
            } else {
                $status = 'Óptimo';
                $statusClass = 'text-emerald border-emerald';
                $barColor = '#047857';
                $barBg = 'bg-emerald-soft';
                $accentColor = 'var(--emerald)';
            }
        @endphp

        <div class="analytic-item animate-in">
            <div class="block-header"
                 role="button"
                 data-bs-toggle="collapse"
                 data-bs-target="#{{ $collapseId }}"
                 aria-expanded="false"
                 aria-controls="{{ $collapseId }}">
                <div class="block-status-bar" style="background:{{ $accentColor }};"></div>
                <div class="block-info">
                    <div class="block-sublabel">Bloque Workflow</div>
                    <div class="block-name">{{ $item['etapa'] }}</div>
                </div>
                <div class="block-progress d-none d-md-block">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $porcentaje }}%; background: {{ $barColor }};"></div>
                    </div>
                </div>
                <div class="block-time">{{ $item['label'] }}</div>
                <span class="block-status-badge {{ $statusClass }}" style="background:transparent;">{{ $status }}</span>
                @if(!empty($item['tramos']))
                <div class="tramo-chips d-none d-lg-flex">
                    @foreach(collect($item['tramos'])->take(3) as $tramo)
                        @if($tramo['minutos'] > 0)
                            <span class="tramo-chip" title="{{ $tramo['etiqueta'] }}: {{ $tramo['label'] }}">{{ $tramo['etiqueta'] }}</span>
                        @endif
                    @endforeach
                    @if(count($item['tramos']) > 3)
                        <span class="tramo-chip">+{{ count($item['tramos']) - 3 }}</span>
                    @endif
                </div>
                @endif
                <div class="block-chevron"><i class="bi bi-chevron-down"></i></div>
            </div>

            <div class="collapse" id="{{ $collapseId }}">
                <div class="block-detail">
                    @if(isset($item['estados']) && count($item['estados']) > 0)
                        <div class="states-grid">
                            @foreach($item['estados'] as $estado)
                                @if($estado['minutos'] > 0)
                                    <div class="state-item">
                                        <span class="state-name">{{ $estado['nombre'] }}</span>
                                        <span class="state-time">{{ $estado['label'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($item['tramos']))
                        <div class="tramos-section">
                            <div class="tramos-label">Tramos temporales</div>
                            <div class="tramos-grid">
                                @foreach($item['tramos'] as $tramo)
                                    @if($tramo['minutos'] > 0)
                                        <div class="tramo-item">
                                            <span class="tramo-label">{{ $tramo['etiqueta'] }}</span>
                                            <span class="tramo-time">{{ $tramo['label'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state py-5">
            <i class="bi bi-clipboard-x"></i>
            <p class="mt-2 mb-0">No se encontraron registros de tiempo para esta selección.</p>
        </div>
    @endforelse
</div>

<style>
    .border-ruby { border-color: var(--ruby) !important; }
    .border-amber { border-color: var(--amber) !important; }
    .border-sapphire { border-color: var(--sapphire) !important; }
    .border-emerald { border-color: var(--emerald) !important; }
</style>
