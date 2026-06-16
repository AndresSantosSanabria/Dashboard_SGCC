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
                <div class="mb-3" id="bloqueDestinoContainer">
                    <label for="selectBloqueDestino" class="form-label fw-bold">Bloque de Destino:</label>
                    <select id="selectBloqueDestino" class="form-select form-select-lg" style="border-radius: 8px;">
                        <option value="">-- Seleccione un bloque --</option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="devolucionSupervisorContainer">
                    <div class="form-check form-switch p-0">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="checkDevolucionSupervisor">
                        <label class="form-check-label fw-bold text-danger" for="checkDevolucionSupervisor">
                            ¿Es devolución a supervisor?
                        </label>
                    </div>
                    <div class="small text-muted mt-2" id="devolucionSupervisorHelp">
                        Solo aplica cuando la transición actual es una devolución y el destino es el Bloque 1.
                        Al activarlo, el sistema intentará precargar el supervisor del contrato.
                    </div>
                </div>

                <div class="mb-3 d-none" id="devolucionSupervisorNotice">
                    <div class="alert alert-warning py-2 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="devolucionSupervisorNoticeText">Se precargará el supervisor asociado al contrato.</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="selectResponsable" class="form-label fw-bold">Responsable:</label>
                    <select id="selectResponsable" class="form-select form-select-lg" style="border-radius: 8px;">
                        <option value="">-- Seleccione un receptor --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="comentarioResponsable" class="form-label fw-bold" id="labelComentario">Motivo / Comentarios:</label>
                    <textarea id="comentarioResponsable" class="form-control" rows="3" placeholder="Escriba el motivo del movimiento..." style="border-radius: 8px;"></textarea>
                </div>
                <input type="hidden" id="cuentaIdResponsable" value="">
                <input type="hidden" id="estadoDestinoIdResponsable" value="">
                <input type="hidden" id="isDevolucionResponsable" value="0">
                <input type="hidden" id="isDevolucionSupervisor" value="0">
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

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL SS ÚLTIMA CUENTA — Obligatorio al pasar de "Sin Tramite" → "En Revision"
     Siempre se actualiza aunque el campo ya tenga datos de la cuenta anterior.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalSsUltimaCuenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content" style="border-radius: 18px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">

            {{-- Cabecera con gradiente --}}
            <div class="modal-header border-0 pb-0"
                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem 1.5rem 2.5rem;">
                <div class="w-100 text-center">
                    <div class="mb-2" style="font-size: 2.5rem; line-height:1;">📋</div>
                    <h5 class="modal-title fw-bold text-white mb-1" style="font-size: 1.15rem;">
                        SS – Última Cuenta Finalizada
                    </h5>
                    <p class="text-white-50 mb-0 small">
                        Obligatorio para pasar a <strong class="text-white">En Revisión</strong>
                    </p>
                </div>
            </div>

            <div class="modal-body pt-0" style="background:#fff;">
                {{-- Banner de alerta --}}
                <div class="mx-3 mt-n3 mb-4 p-3 rounded-3 shadow-sm"
                     style="background: linear-gradient(135deg,#fff3cd,#ffe69c); border-left: 4px solid #f0ad4e;">
                    <div class="d-flex align-items-start gap-2">
                        <span style="font-size:1.2rem;">⚠️</span>
                        <div>
                            <div class="fw-bold text-warning-emphasis" style="font-size:.85rem;">Campo requerido</div>
                            <div class="text-muted" style="font-size:.8rem;">
                                Seleccione el mes en que <strong>finalizó la última cuenta de Seguridad Social</strong>.
                                Este campo se actualiza siempre, incluso si ya tenía información.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-3 pb-2">
                    <label for="selectSsUltimaCuenta" class="form-label fw-bold text-dark mb-2">
                        <i class="bi bi-calendar3 me-1 text-purple"></i>
                        Mes de la última cuenta SS:
                        <span class="text-danger">*</span>
                    </label>

                    {{-- Grid de meses estilo botones --}}
                    <div class="row g-2" id="mesesSsGrid">
                        @php
                            $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                                      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        @endphp
                        @foreach($meses as $mes)
                            <div class="col-4">
                                <button type="button"
                                        class="btn btn-outline-secondary w-100 mes-ss-btn"
                                        style="border-radius:10px; font-size:.82rem; padding:.45rem .3rem;"
                                        data-mes="{{ $mes }}">
                                    {{ $mes }}
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Mes seleccionado --}}
                    <div class="mt-3 p-3 rounded-3 text-center" id="mesSeleccionadoPreview"
                         style="display:none; background: linear-gradient(135deg,#667eea22,#764ba222); border: 2px solid #667eea44;">
                        <div class="text-muted small mb-1">Mes seleccionado:</div>
                        <div class="fw-bold" style="font-size:1.1rem; color:#667eea;" id="mesSeleccionadoTexto">—</div>
                    </div>

                    <input type="hidden" id="ssCuentaMesValor" value="">
                    <input type="hidden" id="ssCuentaId" value="">
                    <input type="hidden" id="ssCuentaEstadoDestinoId" value="">
                </div>
            </div>

            <div class="modal-footer border-0 pt-0" style="background:#fff; padding: 1rem 1.5rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnCancelarSsCuenta"
                        style="border-radius:12px;">
                    <i class="bi bi-x-lg me-1"></i> Cancelar
                </button>
                <button type="button" class="btn px-4 fw-bold" id="btnConfirmarSsCuenta"
                        style="border-radius:12px; background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; opacity:.5; cursor:not-allowed;"
                        disabled>
                    <i class="bi bi-check-circle me-1"></i> Confirmar y Pasar a En Revisión
                </button>
            </div>
        </div>
    </div>
</div>
