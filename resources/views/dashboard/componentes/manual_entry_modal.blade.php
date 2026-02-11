<!-- Modal para Carga Manual -->
<div class="modal fade" id="manualEntryModal" tabindex="-1" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manualEntryModalLabel">Cargar Información Manualmente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="manualForm" data-url="{{ route('dashboard.manual') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        {{-- Contenido del formulario --}}
                        {{-- (Mantenemos todo el contenido interno igual) --}}
                        @include('dashboard.componentes.manual_form_content')
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnSaveManual" class="btn btn-primary">Cargar Registro</button>
                </div>
            </form>
        </div> {{-- modal-content --}}
    </div> {{-- modal-dialog --}}
</div> {{-- manualEntryModal --}}
