@foreach($cuentas as $c)
    @php 
        $hasBrecha = (($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0)) > 0;
    @endphp
    <div class="m-contract-item">
        <div class="m-contract-dot" style="background: {{ $hasBrecha ? '#ef4444' : '#10b981' }}"></div>
        <div class="m-contract-info">
            <span class="m-contract-id">{{ $c->contrato->numero_contrato ?? 'N/A' }}</span>
            <span class="m-contract-user">{{ $c->contrato->contratista->nombre_completo ?? 'N/A' }}</span>
        </div>
        <i class="bi bi-chevron-right m-contract-chevron"></i>
    </div>
@endforeach
