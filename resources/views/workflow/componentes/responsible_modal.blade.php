<!-- Modal para Asignar Responsable -->
<div class="modal fade" id="modalAsignarResponsable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-check me-2"></i>Asignar Responsable
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="modalResponsableMessage">
                    <i class="fas fa-info-circle me-2"></i>
                    Seleccione el responsable que gestionará esta cuenta:
                </p>
                <div class="alert alert-info d-none mb-3" id="responsableBadgeContainer" style="border-radius: 8px;">
                    <span id="responsableBadge" class="badge"></span>
                </div>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 12px;">
                    <i class="bi bi-x-lg me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-premium-confirm" id="btnConfirmarResponsable">
                    <i class="bi bi-check-lg me-2"></i>Confirmar Asignación
                </button>
            </div>
        </div>
    </div>
</div>
