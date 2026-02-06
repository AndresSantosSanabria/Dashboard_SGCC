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
                    @if ($tipoMensaje === 'success')
                        <div class="success-message" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;">
                            <strong>{{ $mensaje }}</strong>
                            @if ($resumenImportacion)
                                <div style="margin-top: 10px; font-size: 0.95em;">
                                    <p>📊 <strong>Resumen:</strong></p>
                                    <ul style="list-style: none; padding-left: 0;">
                                        <li>✔️ Registros exitosos: <strong>{{ $resumenImportacion['exitosas'] }}</strong></li>
                                        <li>📝 Total procesado: <strong>{{ $resumenImportacion['total'] }}</strong></li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @elseif ($tipoMensaje === 'warning')
                        <div class="warning-message" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px;">
                            <strong>{{ $mensaje }}</strong>
                            @if ($resumenImportacion)
                                <div style="margin-top: 10px; font-size: 0.95em;">
                                    <p>📊 <strong>Resumen:</strong></p>
                                    <ul style="list-style: none; padding-left: 0;">
                                        <li>✔️ Registros exitosos: <strong style="color: green;">{{ $resumenImportacion['exitosas'] }}</strong></li>
                                        <li>❌ Registros fallidos: <strong style="color: red;">{{ $resumenImportacion['fallidas'] }}</strong></li>
                                        <li>📝 Total procesado: <strong>{{ $resumenImportacion['total'] }}</strong></li>
                                    </ul>
                                    
                                    @if (count($resumenImportacion['errores']) > 0)
                                        <div style="margin-top: 15px; border-top: 1px solid #ffeeba; padding-top: 10px;">
                                            <p><strong>Errores encontrados:</strong></p>
                                            <div style="max-height: 200px; overflow-y: auto; background: #fffbf0; padding: 10px; border-radius: 3px;">
                                                @foreach ($resumenImportacion['errores'] as $error)
                                                    <p style="margin: 5px 0; font-size: 0.9em;">
                                                        <span style="color: #d9534f;"><strong>Contrato:</strong> {{ $error['numero_contrato'] }}</span><br>
                                                        <span style="color: #d9534f;"><strong>Cuenta:</strong> {{ $error['numero_cuenta'] }}</span><br>
                                                        <span style="color: #d9534f;"><strong>Motivo:</strong> {{ $error['error'] }}</span>
                                                    </p>
                                                    <hr style="margin: 8px 0; border: none; border-top: 1px dotted #ddd;">
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (count($encabezados) > 0)
                                        <div style="margin-top: 15px; border-top: 1px solid #ffeeba; padding-top: 10px;">
                                            <p><strong>📋 Columnas encontradas en tu Excel:</strong></p>
                                            <div style="background: #fffbf0; padding: 10px; border-radius: 3px; font-size: 0.85em;">
                                                <code style="white-space: pre-wrap; word-break: break-all;">{{ implode(', ', $encabezados) }}</code>
                                            </div>
                                            <p style="margin-top: 10px; font-size: 0.85em; color: #666;">
                                                <strong>💡 Campos requeridos mínimos:</strong><br>
                                                • cedula<br>
                                                • numero_de_contrato<br>
                                                • numero_de_cuenta_en_proceso_de_cuentas
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="error-message" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;">
                            <strong>{{ $mensaje }}</strong>
                        </div>
                    @endif
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
