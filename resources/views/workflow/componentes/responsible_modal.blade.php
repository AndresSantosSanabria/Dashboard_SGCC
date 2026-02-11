<!-- Modal para Asignar Responsable -->
<div class="modal fade" id="modalAsignarResponsable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 12px;">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title">
                    <i class="fas fa-user-check me-2"></i>Asignar Responsable
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <p class="text-muted mb-3" id="modalResponsableMessage">
                    <i class="fas fa-info-circle me-2"></i>
                    Seleccione el responsable que gestionará esta cuenta:
                </p>
                <div class="mb-3">
                    <label for="selectResponsable" class="form-label fw-bold">Responsable:</label>
                    <select id="selectResponsable" class="form-select form-select-lg" style="border-radius: 8px;">
                        <option value="">-- Seleccione un responsable --</option>
                    </select>
                </div>
                <input type="hidden" id="cuentaIdResponsable" value="">
                <input type="hidden" id="estadoDestinoIdResponsable" value="">
                <input type="hidden" id="estadoCodigoResponsable" value="">
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e9ecef;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarResponsable">
                    <i class="fas fa-check me-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
