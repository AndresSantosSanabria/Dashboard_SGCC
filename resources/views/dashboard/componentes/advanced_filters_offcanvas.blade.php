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

        <div class="mb-3">
            <label class="form-label fw-bold">Estado tras Primera Revisión</label>
            <div class="p-2 border rounded" style="max-height: 200px; overflow-y: auto;">
                @foreach ($estadosRevision as $estado)
                    <div class="form-check">
                        <input class="form-check-input filter-input" type="checkbox" name="filterEstadosRevision[]"
                            value="{{ $estado->id }}" id="est_{{ $estado->id }}"
                            {{ in_array($estado->id, (array) request('filterEstadosRevision')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="est_{{ $estado->id }}">
                            {{ $estado->nombre }}
                        </label>
                    </div>
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
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Limpiar filtros</a>
        </div>
    </div>
</div>
