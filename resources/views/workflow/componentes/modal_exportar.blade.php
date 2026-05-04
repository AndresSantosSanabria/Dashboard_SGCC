<!-- Modal Exportar Excel -->
<div class="modal fade" id="modalExportarExcel" tabindex="-1" aria-labelledby="modalExportarExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0" style="background-color: var(--govco-blue); color: white; border-radius: 16px 16px 0 0; padding: 1.5rem;">
                <h5 class="modal-title fw-bold" id="modalExportarExcelLabel">
                    <i class="bi bi-file-earmark-excel me-2"></i> Exportar Informe de Gestión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formExportarExcel" action="{{ route('workflow.exportar') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">
                        Seleccione los parámetros para generar el informe de gestión en formato Excel.
                    </p>

                    {{-- Filtro de Sujetos --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-person-badge me-1"></i> Usuario(s) a consultar
                        </label>
                        @php
                            $esAdminOVis = Auth::user()->isAdmin() || Auth::user()->rol?->nombre === 'Visualizador';
                        @endphp

                        @if($esAdminOVis)
                            <div class="card bg-light border-0">
                                <div class="card-body p-2" style="max-height: 150px; overflow-y: auto;">
                                    <div class="form-check mb-2 pb-2 border-bottom">
                                        <input class="form-check-input" type="checkbox" id="user_todos" name="usuarios[]" value="todos" onchange="toggleAllUsers(this)">
                                        <label class="form-check-label fw-bold" for="user_todos">
                                            Todos los usuarios
                                        </label>
                                    </div>
                                    <div id="usuariosListContainer">
                                        @foreach($responsables as $resp)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input user-checkbox" type="checkbox" id="user_{{ $resp->id }}" name="usuarios[]" value="{{ $resp->id }}">
                                                <label class="form-check-label text-truncate d-block" for="user_{{ $resp->id }}">
                                                    {{ $resp->nombre_completo }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle"></i> Seleccione uno o más usuarios para el reporte.</small>

                            <script>
                                function toggleAllUsers(source) {
                                    const checkboxes = document.querySelectorAll('.user-checkbox');
                                    checkboxes.forEach(cb => cb.checked = source.checked);
                                }
                                
                                document.addEventListener('DOMContentLoaded', function() {
                                    const userCheckboxes = document.querySelectorAll('.user-checkbox');
                                    const selectAllCheckbox = document.getElementById('user_todos');
                                    
                                    userCheckboxes.forEach(function(checkbox) {
                                        checkbox.addEventListener('change', function() {
                                            if (!this.checked && selectAllCheckbox) {
                                                selectAllCheckbox.checked = false;
                                            } else if (selectAllCheckbox) {
                                                const allChecked = Array.from(userCheckboxes).every(c => c.checked);
                                                selectAllCheckbox.checked = allChecked;
                                            }
                                        });
                                    });
                                    
                                    // Validación básica del form
                                    const formExport = document.getElementById('formExportarExcel');
                                    if(formExport) {
                                        formExport.addEventListener('submit', function(e) {
                                            const esAdmin = {{ $esAdminOVis ? 'true' : 'false' }};
                                            if (esAdmin) {
                                                const anyChecked = Array.from(document.querySelectorAll('input[name="usuarios[]"]')).some(c => c.checked);
                                                if (!anyChecked) {
                                                    e.preventDefault();
                                                    alert('Debe seleccionar al menos un usuario para generar el informe.');
                                                }
                                            }
                                        });
                                    }
                                });
                            </script>
                        @else
                            <input type="text" class="form-control" value="Informe para: {{ Auth::user()->nombre_completo }}" disabled>
                            <input type="hidden" name="usuarios[]" value="{{ Auth::user()->id }}">
                        @endif
                    </div>

                    {{-- Filtro Temporal --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-calendar-range me-1"></i> Rango de Fechas
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="exportFechaInicio" name="fecha_inicio" required value="{{ request('fecha_desde') }}">
                                    <label for="exportFechaInicio">Fecha Inicio</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="exportFechaFin" name="fecha_fin" required value="{{ request('fecha_hasta') ?? date('Y-m-d') }}">
                                    <label for="exportFechaFin">Fecha Fin</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Selector de Parámetros --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-list-check me-1"></i> Parámetros a incluir
                        </label>
                        <div class="card bg-light border-0">
                            <div class="card-body p-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="paramTiempo" name="parametros[]" value="tiempo_respuesta" checked>
                                    <label class="form-check-label" for="paramTiempo">
                                        <strong>Tiempo de respuesta:</strong> Duración exacta en manos del usuario (ej: 2h 15m)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="paramOrigen" name="parametros[]" value="origen" checked>
                                    <label class="form-check-label" for="paramOrigen">
                                        <strong>Origen de asignación:</strong> Quién entregó el trabajo
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="paramDestino" name="parametros[]" value="destino" checked>
                                    <label class="form-check-label" for="paramDestino">
                                        <strong>Destino de gestión:</strong> A quién se transfirió la responsabilidad
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top-0 pt-0 p-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" id="btnProcesarExport">
                        <i class="bi bi-download me-2"></i> Generar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #modalExportarExcel .btn-success {
        background-color: #10b981;
        border-color: #10b981;
        font-weight: 600;
    }
    #modalExportarExcel .btn-success:hover {
        background-color: #059669;
        border-color: #059669;
    }
</style>
