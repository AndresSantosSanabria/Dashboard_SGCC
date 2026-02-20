<table class="table table-hover table-seguimiento mb-0">
    <thead>
        <tr>
            <th class="text-center">Acciones</th>
            <th>Proceso</th>
            <th class="sticky-col">Contrato</th>
            <th>Modalidad</th>
            <th>Contratista</th>
            <th>Supervisor</th>
            <th>Objeto</th>
            <th>Valor</th>
            <th class="text-center">SECOP</th>
            <th class="text-center">Planta</th>
            <th class="text-center">Concepto</th>
            <th class="text-center">CDP</th>
            <th class="text-center">Estudios</th>
            <th class="text-center">Idoneidad</th>
            <th class="text-center">Clausulado</th>
            <th class="text-center">RPC</th>
            <th class="text-center">Inicio</th>
            <th class="text-center">Delegación</th>
            <th class="text-center">Póliza</th>
        </tr>
    </thead>
    <tbody>
        @forelse($contratos as $c)
            <tr>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle shadow-sm btn-edit-contrato"
                        data-bs-toggle="modal" data-bs-target="#modalEditarContrato"
                        data-id="{{ $c->id }}" 
                        data-numero_proceso="{{ $c->numero_proceso }}"
                        data-numero_contrato="{{ $c->numero_contrato }}"
                        data-modalidad_id="{{ $c->modalidad_id }}"
                        data-contratista_nombre="{{ $c->contratista->razon_social ?: $c->contratista->representante_legal }}"
                        data-supervisor_id="{{ $c->supervisor_id }}"
                        data-objeto="{{ $c->objeto }}"
                        data-monto_total="{{ $c->monto_total }}"
                        data-link_secop="{{ $c->link_secop }}"
                        style="width: 32px; height: 32px; padding: 4px;"
                        title="Editar Contrato">
                        <i class="bi bi-pencil fs-6"></i>
                    </button>
                </td>
                <td><span class="text-muted small">#{{ $c->numero_proceso ?? 'N/A' }}</span></td>
                <td class="sticky-col">
                    <span class="fw-bold text-dark">{{ $c->numero_contrato }}</span>
                </td>
                <td>
                    <span class="badge rounded-pill bg-light text-dark border px-3">
                        {{ $c->modalidad->nombre ?? 'N/A' }}
                    </span>
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold text-primary"
                            style="font-size: 0.8rem;">{{ $c->contratista->nombre_completo ?? 'N/A' }}</span>
                        <span class="text-muted" style="font-size: 0.7rem;">NIT:
                            {{ $c->contratista->nit ?? 'N/A' }}</span>
                    </div>
                </td>
                <td>
                    <span class="text-secondary small">{{ $c->supervisor->nombre_completo ?? 'N/A' }}</span>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 180px;" title="{{ $c->objeto }}">
                        <small class="text-muted">{{ $c->objeto ?? 'Sin objeto' }}</small>
                    </div>
                </td>
                <td>
                    <span class="fw-bold text-success">${{ number_format($c->monto_total, 0, ',', '.') }}</span>
                </td>
                <td class="text-center">
                    @if ($c->link_secop)
                        <a href="{{ $c->link_secop }}" target="_blank"
                            class="btn btn-sm btn-outline-info rounded-circle shadow-sm"
                            style="width: 32px; height: 32px; padding: 4px;">
                            <i class="bi bi-link-45deg fs-5"></i>
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-center small">{{ $c->planta->codigo ?? 'N/A' }}</td>
                <td class="text-center small">{{ $c->concepto->nombre ?? 'N/A' }}</td>
                <td class="text-center small fw-bold">{{ $c->cdp_codigo ?? 'N/A' }}</td>

                <!-- Semáforos -->
                @php
                    $checklistFields = [
                        'estudios_previos_status' => 'EP',
                        'idoneidad_status' => 'ID',
                        'clausulado_status' => 'CL',
                        'rpc_status' => 'RP',
                        'acta_inicio_status' => 'AI',
                        'delegacion_status' => 'DE',
                        'poliza_status' => 'PO',
                    ];
                @endphp

                @foreach ($checklistFields as $field => $label)
                    <td style="min-width: 120px; padding: 0.8rem 0.4rem;">
                        <select class="status-dropdown status-{{ strtolower($c->$field) ?: 'null' }}"
                            data-id="{{ $c->id }}" data-field="{{ $field }}">
                            <option value="" {{ empty($c->$field) || $c->$field == '-' ? 'selected' : '' }}>-</option>
                            <option value="OK" {{ $c->$field == 'OK' ? 'selected' : '' }}>OK</option>
                            <option value="PENDIENTE" {{ $c->$field == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE
                            </option>
                            <option value="ROJO" {{ $c->$field == 'ROJO' ? 'selected' : '' }}>NO CARGADO</option>
                            <option value="NA" {{ $c->$field == 'NA' ? 'selected' : '' }}>NA</option>
                        </select>
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="19" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center opacity-50">
                        <i class="bi bi-search fs-1 mb-2"></i>
                        <p class="fw-semibold">No se encontraron resultados para los filtros aplicados</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="px-4 py-3 bg-light border-top d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Página <strong>{{ $contratos->currentPage() }}</strong> de <strong>{{ $contratos->lastPage() }}</strong>
        <span class="mx-2">|</span>
        Total <strong>{{ $contratos->total() }}</strong> contratos
    </div>
    <div class="pagination-premium">
        {{ $contratos->links() }}
    </div>
</div>
