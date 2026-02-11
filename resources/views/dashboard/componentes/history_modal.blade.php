<!-- Modal de Historial de Workflow -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="fas fa-history me-2"></i>Historial de Movimientos - Contrato <span
                        id="historyContratoNum"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" style="max-height: 70vh; overflow-y: auto;">
                <div id="historySpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Obteniendo línea de tiempo...</p>
                </div>
                <div id="timelineContent" class="timeline-container px-3" style="display: none;">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div id="historyEmpty" class="text-center py-5" style="display: none;">
                    <i class="fas fa-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted">No hay registros de movimientos para esta cuenta.</p>
                </div>
            </div>
        </div>
    </div>
</div>
