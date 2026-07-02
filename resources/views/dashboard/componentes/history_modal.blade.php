<!-- Modal de Historial de Workflow -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header-custom p-3 p-md-4 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); position: relative;">
                <div class="d-flex align-items-center w-100">
                    <div class="history-icon-wrapper me-2 me-md-3 d-flex align-items-center justify-content-center bg-white text-primary rounded-circle" style="width: 40px; height: 40px; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(0,0,0,0.2); flex-shrink: 0;">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="historyModalLabel" style="font-size: 1.1rem; letter-spacing: 0.5px;">
                            Historial de Movimientos
                        </h5>
                        <p class="mb-0 small opacity-75">
                            Contrato: <span id="historyContratoNum" class="fw-bold"></span>
                            <span class="mx-2">|</span>
                            Cuenta: <span id="historyCuentaNum" class="fw-bold"></span>
                        </p>
                    </div>
                    <div class="ms-auto me-4">
                        <span id="historyTotalTimeBadge" class="badge bg-white text-primary px-2 px-md-3 py-1 py-md-2 shadow-sm" style="font-size: 0.75rem; border-radius: 20px; display: none;">
                            <i class="fas fa-clock me-1"></i> <span id="historyTotalTime"></span>
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 15px; right: 15px;"></button>
            </div>
            <div class="modal-body p-0 bg-white" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                <div id="historyCurrentInfo" style="display:none;"></div>

                <div id="historySpinner" class="text-center py-5">
                    <div class="spinner-grow text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted fw-bold">Consultando trazabilidad...</p>
                </div>

                <div id="timelineContent" class="timeline-container-premium py-4 px-2 px-md-4" style="display: none;">
                    <!-- El contenido se cargará dinámicamente -->
                </div>

                <div id="historyEmpty" class="text-center py-5 px-3 px-md-4" style="display: none;">
                    <div class="empty-state-icon mb-3 mb-md-4" style="font-size: 4rem; color: #e5e7eb;">
                        <i class="fas fa-route"></i>
                    </div>
                    <h5 class="text-dark fw-bold mb-2">Sin movimientos registrados</h5>
                    <p class="text-muted mx-auto" style="max-width: 300px; font-size: 0.9rem;">Esta cuenta aún no ha realizado transiciones en el flujo de trabajo.</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 py-md-3">
                <button type="button" class="btn btn-secondary px-3 px-md-4 fw-bold w-100 w-md-auto" data-bs-dismiss="modal" style="border-radius: 8px;">Cerrar</button>
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
    margin-bottom: 24px;
}

.timeline-item-premium:last-child {
    margin-bottom: 0;
}

.item-left {
    min-width: 90px;
    text-align: right;
    padding-right: 20px;
    padding-top: 8px;
    flex-shrink: 0;
}

.item-date {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
}

.item-time {
    font-size: 0.7rem;
    color: #94a3b8;
    font-weight: 500;
    margin-top: 2px;
}

.item-center {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 20px;
    flex-shrink: 0;
}

.item-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #3b82f6;
    border: 2.5px solid #dbeafe;
    z-index: 2;
    margin-top: 10px;
    flex-shrink: 0;
}

.item-line {
    position: absolute;
    top: 22px;
    bottom: -24px;
    width: 2px;
    background: linear-gradient(to bottom, #e2e8f0, #f1f5f9);
    z-index: 1;
}

.timeline-item-premium:last-child .item-line {
    display: none;
}

.item-right {
    flex: 1;
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    min-width: 0;
    word-wrap: break-word;
}

.item-right:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    flex-wrap: wrap;
    gap: 6px;
}

.item-title {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.82rem;
}

.item-transition {
    font-size: 0.78rem;
    color: #475569;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}

.item-transition .fw-bold {
    color: #1e293b;
}

.item-meta {
    display: flex;
    gap: 12px;
    font-size: 0.75rem;
    color: #94a3b8;
    flex-wrap: wrap;
    align-items: center;
}

.item-comment {
    margin-top: 8px;
    padding: 8px 12px;
    background: #ffffff;
    border-left: 3px solid #3b82f6;
    border-radius: 6px;
    font-size: 0.8rem;
    color: #334155;
    word-break: break-word;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 768px) {
    .timeline-item-premium {
        margin-bottom: 18px;
    }

    .item-left {
        min-width: 60px;
        padding-right: 8px;
    }

    .item-center {
        margin-right: 8px;
    }

    .item-right {
        padding: 10px;
    }

    .item-right:hover {
        transform: none;
    }

    .item-title {
        font-size: 0.78rem;
    }

    .item-transition {
        font-size: 0.72rem;
    }

    .item-meta {
        gap: 6px;
        flex-direction: column;
    }

    .item-comment {
        font-size: 0.72rem;
        padding: 6px 10px;
    }

    .item-line {
        bottom: -18px;
    }

    #historyTotalTimeBadge {
        font-size: 0.65rem;
    }
}
</style>
