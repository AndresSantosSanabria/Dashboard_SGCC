<!-- Modal de Historial de Workflow -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header-custom p-4 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); position: relative;">
                <div class="d-flex align-items-center w-100">
                    <div class="history-icon-wrapper me-3 d-flex align-items-center justify-content-center bg-white text-primary rounded-circle" style="width: 45px; height: 45px; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="historyModalLabel" style="font-size: 1.1rem; letter-spacing: 0.5px;">
                            Historial de Movimientos
                        </h5>
                        <p class="mb-0 small opacity-75">Contrato: <span id="historyContratoNum" class="fw-bold"></span></p>
                    </div>
                    <div class="ms-auto me-4">
                        <span id="historyTotalTimeBadge" class="badge bg-white text-primary px-3 py-2 shadow-sm" style="font-size: 0.85rem; border-radius: 20px; display: none;">
                            <i class="fas fa-clock me-1"></i> <span id="historyTotalTime"></span>
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 20px; right: 20px;"></button>
            </div>
            <div class="modal-body p-0 bg-white" style="max-height: 70vh; overflow-y: auto;">
                <div id="historySpinner" class="text-center py-5">
                    <div class="spinner-grow text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted fw-bold">Consultando trazabilidad...</p>
                </div>

                <div id="timelineContent" class="timeline-container-premium py-4 px-4" style="display: none;">
                    <!-- El contenido se cargará dinámicamente -->
                </div>

                <div id="historyEmpty" class="text-center py-5 px-4" style="display: none;">
                    <div class="empty-state-icon mb-4" style="font-size: 5rem; color: #e5e7eb;">
                        <i class="fas fa-route"></i>
                    </div>
                    <h5 class="text-dark fw-bold mb-2">Sin movimientos registrados</h5>
                    <p class="text-muted mx-auto" style="max-width: 300px;">Esta cuenta aún no ha realizado transiciones en el flujo de trabajo.</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-3">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius: 8px;">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-container-premium {
    position: relative;
}

.timeline-item-premium {
    display: flex;
    position: relative;
    margin-bottom: 30px;
}

.timeline-item-premium:last-child {
    margin-bottom: 0;
}

.item-left {
    min-width: 100px;
    text-align: right;
    padding-right: 25px;
    padding-top: 5px;
}

.item-time {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
}

.item-center {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 25px;
}

.item-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #3b82f6;
    border: 3px solid #bfdbfe;
    z-index: 2;
    margin-top: 10px;
}

.item-line {
    position: absolute;
    top: 24px;
    bottom: -30px;
    width: 2px;
    background: #e5e7eb;
    z-index: 1;
}

.timeline-item-premium:last-child .item-line {
    display: none;
}

.item-right {
    flex: 1;
    background: #f9fafb;
    border-radius: 12px;
    padding: 15px 20px;
    border: 1px solid #f3f4f6;
    transition: all 0.2s;
}

.item-right:hover {
    background: #f3f4f6;
    transform: translateX(5px);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.item-title {
    font-weight: 700;
    color: #111827;
    font-size: 0.95rem;
}

.item-transition {
    font-size: 0.85rem;
    color: #4b5563;
    margin-bottom: 10px;
}

.item-meta {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: #9ca3af;
}

.item-comment {
    margin-top: 10px;
    padding: 8px 12px;
    background: #ffffff;
    border-left: 3px solid #3b82f6;
    border-radius: 4px;
    font-style: italic;
    font-size: 0.85rem;
    color: #374151;
}
</style>
