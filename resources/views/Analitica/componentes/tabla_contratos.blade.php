@foreach($cuentas as $c)
    @php $diff = ($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0); @endphp
    <tr class="{{ $diff > 0 ? 'table-alert' : '' }}">
        <td class="fw-bold text-govco-blue">{{ $c->contrato->numero_contrato ?? 'N/A' }}</td>
        <td><small>{{ $c->contrato->contratista->nombre_completo ?? 'N/A' }}</small></td>
        <td><small class="badge" style="background:rgba(0,72,132,.08); color:var(--govco-blue); font-weight:600;">{{ $c->bloqueActual->nombre ?? 'N/A' }}</small></td>
        <td><small>{{ $c->estadoActual->nombre ?? 'N/A' }}</small></td>
        <td class="text-center fw-semibold">{{ $c->numero_pagos_totales ?? 0 }}</td>
        <td class="text-center fw-semibold">{{ $c->radicadas_bi ?? 0 }}</td>
        <td class="text-center">
            @if($diff > 0)
                <span class="badge badge-danger-soft">{{ $diff }}</span>
            @else
                <span class="badge badge-success-soft"><i class="bi bi-check2"></i> OK</span>
            @endif
        </td>
        <td style="min-width: 120px;">
            <div class="d-flex align-items-center gap-2">
                <div class="progress-slim flex-grow-1">
                    <div class="progress-bar" style="width: {{ min($c->avance_bi, 100) }}%"></div>
                </div>
                <small class="text-muted fw-semibold" style="font-size:.72rem;">{{ number_format($c->avance_bi, 1) }}%</small>
            </div>
        </td>
    </tr>
@endforeach
