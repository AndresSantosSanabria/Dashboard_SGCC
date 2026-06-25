@foreach($cuentas as $c)
    @php $diff = ($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0); @endphp
    <tr>
        <td class="fw-bold text-sapphire">{{ $c->contrato->numero_contrato ?? 'N/A' }}</td>
        <td><span style="font-size:0.8125rem;color:var(--text-secondary)">{{ $c->contrato->contratista->nombre_completo ?? 'N/A' }}</span></td>
        <td><span class="badge bg-sapphire-soft text-sapphire border-0" style="font-weight:600;font-size:0.71875rem;">{{ $c->bloqueActual->nombre ?? 'N/A' }}</span></td>
        <td><span style="font-size:0.8125rem;color:var(--text-secondary)">{{ $c->estadoActual->nombre ?? 'N/A' }}</span></td>
        <td class="text-center fw-semibold">{{ $c->numero_pagos_totales > 0 ? $c->numero_pagos_totales : '' }}</td>
        <td class="text-center fw-semibold">{{ $c->radicadas_bi ?? 0 }}</td>
        <td class="text-center">
            @if($diff > 0)
                <span class="badge bg-ruby-soft text-ruby border-0" style="font-weight:600;">{{ $diff }}</span>
            @else
                <span class="badge bg-emerald-soft text-emerald border-0" style="font-weight:600;"><i class="bi bi-check2"></i> OK</span>
            @endif
        </td>
        <td style="min-width: 120px;">
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:5px;border-radius:10px;background:var(--bg-main);">
                    <div class="progress-bar rounded-pill" style="width: {{ min($c->avance_bi, 100) }}%; background:#1D4ED8;"></div>
                </div>
                <span style="font-size:0.71875rem;color:var(--text-muted);font-weight:600;">{{ number_format($c->avance_bi, 1) }}%</span>
            </div>
        </td>
    </tr>
@endforeach
