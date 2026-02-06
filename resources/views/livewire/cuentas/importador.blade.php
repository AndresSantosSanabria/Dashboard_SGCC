<div x-data="{ open: false }" x-on:open-modal-import.window="open = true" x-on:close-modal-import.window="open = false"
    x-show="open" x-cloak class="modal-overlay">

    <!-- Backdrop -->
    <div class="modal-backdrop" @click="open = false"></div>

    <!-- Modal Container -->
    <div class="modal-container">
        <div class="modal-content">

            <!-- Close Button -->
            <button @click="open = false" class="modal-close">
                <i class="fas fa-times"></i>
            </button>

            <!-- Header -->
            <div class="modal-header">
                <h2>CARGUE MASIVO DE CUENTAS</h2>
                <p class="modal-subtitle">Arrastra tu Excel o haz clic para seleccionar el archivo</p>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="file-upload-area">
                    <input type="file" wire:model="archivo" class="file-input" id="fileInput">

                    @if ($archivo)
                        <div class="file-selected">
                            <i class="fas fa-file-excel"></i>
                            <p>{{ $archivo->getClientOriginalName() }}</p>
                        </div>
                    @else
                        <div class="file-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Selecciona tu archivo Excel</p>
                            <span class="file-formats">Formatos aceptados: .xlsx, .xls</span>
                        </div>
                    @endif
                </div>

                @error('archivo')
                    <div class="error-message">{{ $message }}</div>
                @enderror

                @if ($mensaje)
                    <div class="success-message">{{ $mensaje }}</div>
                @endif
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button @click="open = false" class="btn-cancel">
                    CANCELAR
                </button>
                <button wire:click="importar" wire:loading.attr="disabled" class="btn-submit">
                    <span wire:loading.remove wire:target="importar">COMENZAR IMPORTACIÓN</span>
                    <span wire:loading wire:target="importar">
                        <i class="fas fa-spinner fa-spin"></i> PROCESANDO...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
