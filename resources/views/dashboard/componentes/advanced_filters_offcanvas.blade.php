{{-- Nivel 2: Panel de Filtros Avanzados (Offcanvas) --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdvancedFilters"
    aria-labelledby="offcanvasAdvancedFiltersLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="offcanvasAdvancedFiltersLabel">Filtros Avanzados</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label fw-bold">Número de Contrato (Exacto)</label>
            <input type="text" name="filterContrato" value="{{ request('filterContrato') }}"
                class="form-control filter-input" placeholder="Búsqueda exacta...">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Número de Cuenta</label>
            <input type="number" name="numero_cuenta" value="{{ request('numero_cuenta') }}"
                class="form-control filter-input" placeholder="Ej: 5">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Supervisor</label>
            <select name="filterSupervisor" class="form-select filter-input">
                <option value="">Todos los supervisores</option>
                @foreach ($supervisores as $sup)
                    <option value="{{ $sup->id }}" {{ request('filterSupervisor') == $sup->id ? 'selected' : '' }}>
                        {{ $sup->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold small text-muted uppercase">Estados por Etapa</label>
            <div class="accordion accordion-flush border rounded overflow-hidden shadow-sm" id="accordionStates">
                @foreach ($bloques as $bloque)
                    @php
                        $estadosDelBloque = $todosLosEstados[$bloque->codigo] ?? collect();
                    @endphp
                    @if ($estadosDelBloque->isNotEmpty())
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $bloque->codigo }}">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse{{ $bloque->codigo }}" 
                                    aria-expanded="false" aria-controls="collapse{{ $bloque->codigo }}"
                                    style="font-size: 0.8rem; background: #f8fafc;">
                                    {{ $bloque->nombre }}
                                </button>
                            </h2>
                            <div id="collapse{{ $bloque->codigo }}" class="accordion-collapse collapse" 
                                aria-labelledby="heading{{ $bloque->codigo }}" data-bs-parent="#accordionStates">
                                <div class="accordion-body p-3 bg-white">
                                    @foreach ($estadosDelBloque as $est)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="filterEstadosRevision[]"
                                                value="{{ $est->id }}" id="est_adv_{{ $est->id }}"
                                                {{ in_array($est->id, (array) request('filterEstadosRevision')) ? 'checked' : '' }}>
                                            <label class="form-check-label ms-2 small" for="est_adv_{{ $est->id }}">
                                                {{ $est->nombre }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Radicada en Hacienda</label>
            <select name="filterRadicadaHacienda" class="form-select filter-input">
                <option value="">Cualquiera</option>
                <option value="SI" {{ request('filterRadicadaHacienda') === 'SI' ? 'selected' : '' }}>Sí
                </option>
                <option value="NO" {{ request('filterRadicadaHacienda') === 'NO' ? 'selected' : '' }}>No
                </option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">En Facturación</label>
            <select name="filterEnFacturacion" class="form-select filter-input">
                <option value="">Cualquiera</option>
                <option value="SI" {{ request('filterEnFacturacion') === 'SI' ? 'selected' : '' }}>Sí</option>
                <option value="NO" {{ request('filterEnFacturacion') === 'NO' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button type="button" class="btn btn-primary"
                onclick="bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAdvancedFilters'))?.hide(); fetchFilteredData();">
                Aplicar Filtros
            </button>
            <button type="button" class="btn btn-secondary"
                onclick="document.getElementById('filtersForm').reset(); bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAdvancedFilters'))?.hide(); fetchFilteredData();">
                Limpiar filtros
            </button>
        </div>
    </div>
</div>
