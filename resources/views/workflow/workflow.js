/**
 * FRONTEND OPERATIVO - WORKFLOW KANBAN
 * 
 * Gestiona la reactividad del tablero, el cronómetro de las tarjetas 
 * y la lógica de transición de estados vía AJAX.
 */

// 1. MOTOR DE TIEMPO REAL (Card Aging)
// Actualiza los badges de tiempo cada segundo para mostrar cuánto lleva 
// estancada una cuenta en su estado actual.
window.workflowResponsibleModalState = {
    cuentaId: null,
    estadoDestinoId: null,
    bloqueDestinoId: null,
    bloqueDestinoCodigo: null,
    esDevolucion: false,
    esDevolucionSupervisor: false,
    supervisorContrato: null,
    supervisorUsuario: null,
    comentario: '',
    loadingSupervisor: false,
    error: null,
};

function setWorkflowResponsibleModalState(partial) {
    window.workflowResponsibleModalState = {
        ...window.workflowResponsibleModalState,
        ...partial,
    };
    return window.workflowResponsibleModalState;
}

function getSelectedBloqueDestinoOption() {
    const selectBloque = document.getElementById('selectBloqueDestino');
    if (!selectBloque || selectBloque.selectedIndex < 0) return null;

    const option = selectBloque.options[selectBloque.selectedIndex];
    if (!option || !option.value) return null;

    return {
        id: option.value,
        codigo: option.dataset.codigo || '',
        nombre: option.textContent || '',
    };
}

function toggleDevolucionSupervisorUI(isVisible) {
    const container = document.getElementById('devolucionSupervisorContainer');
    const notice = document.getElementById('devolucionSupervisorNotice');
    const noticeText = document.getElementById('devolucionSupervisorNoticeText');
    const checkbox = document.getElementById('checkDevolucionSupervisor');
    const supportFlag = window.WORKFLOW_SUPERVISOR_PAUSE_SUPPORTED !== false;

    if (container) {
        container.classList.toggle('d-none', !isVisible);
    }

    if (!isVisible && checkbox) {
        checkbox.checked = false;
    }

    if (!isVisible && notice) {
        notice.classList.add('d-none');
    }

    if (checkbox) {
        checkbox.disabled = isVisible && !supportFlag;
    }

    if (isVisible && !supportFlag && noticeText) {
        noticeText.textContent = 'La devolución a supervisor está disponible, pero la pausa del timer aún no está activa en la base de datos. Se guardará la devolución sin congelar el conteo.';
        if (notice) {
            notice.classList.remove('d-none');
            notice.classList.remove('alert-info');
            notice.classList.add('alert-warning');
        }
    }
}

function updateDevolucionSupervisorNotice(message, isWarning = false) {
    const notice = document.getElementById('devolucionSupervisorNotice');
    const noticeText = document.getElementById('devolucionSupervisorNoticeText');

    if (noticeText) {
        noticeText.textContent = message;
    }

    if (notice) {
        notice.classList.remove('d-none');
        notice.classList.remove('alert-warning', 'alert-info');
        notice.classList.add(isWarning ? 'alert-warning' : 'alert-info');
    }
}

function ensureOptionInResponsablesSelect(select, user) {
    if (!select || !user || !user.id) return;

    const exists = Array.from(select.options).some(opt => String(opt.value) === String(user.id));
    if (exists) return;

    const opt = document.createElement('option');
    opt.value = user.id;
    opt.textContent = `${user.nombre} (Supervisor del contrato)`;
    opt.dataset.source = 'supervisor_default';
    select.insertBefore(opt, select.options[1] || null);
}

async function cargarSupervisorPorDefecto(cuentaId) {
    setWorkflowResponsibleModalState({ loadingSupervisor: true, error: null });

    try {
        const response = await window.apiFetch(`/workflow/cuentas/${cuentaId}/supervisor-default`);
        const data = await response.json();

        if (!data.success) {
            setWorkflowResponsibleModalState({
                loadingSupervisor: false,
                supervisorContrato: null,
                supervisorUsuario: null,
                error: data.message || 'No se pudo cargar el supervisor por defecto.',
            });
            updateDevolucionSupervisorNotice('No se encontró un supervisor asociado al contrato; puede elegir manualmente.', true);
            return null;
        }

        setWorkflowResponsibleModalState({
            loadingSupervisor: false,
            supervisorContrato: data.supervisor,
            supervisorUsuario: data.supervisor_usuario,
            error: null,
        });

        if (data.supervisor_usuario) {
            updateDevolucionSupervisorNotice(`Supervisor precargado: ${data.supervisor_usuario.nombre}`, false);
        } else if (data.supervisor) {
            updateDevolucionSupervisorNotice(`Supervisor del contrato: ${data.supervisor.nombre_completo}.`, true);
        } else {
            updateDevolucionSupervisorNotice('No se encontró supervisor asociado al contrato; podrá continuar manualmente.', true);
        }

        return data;
    } catch (error) {
        setWorkflowResponsibleModalState({
            loadingSupervisor: false,
            supervisorContrato: null,
            supervisorUsuario: null,
            error: 'Error al consultar el supervisor por defecto.',
        });
        updateDevolucionSupervisorNotice('Error al consultar el supervisor por defecto. Puede continuar con la asignación manual.', true);
        return null;
    }
}

function updateTimers() {
    const now = new Date();
    const isWeekend = now.getDay() === 0 || now.getDay() === 6;
    
    // Parsear horas:minutos (venimos en formato H:i desde Blade)
    const startStr = window.WORK_START_TIME || "06:00";
    const endStr = window.WORK_END_TIME || "18:00";
    
    const [startH, startM] = startStr.split(':').map(Number);
    const [endH, endM] = endStr.split(':').map(Number);
    
    const startTimeWork = new Date(now);
    startTimeWork.setHours(startH, startM, 0, 0);
    
    const endTimeWork = new Date(now);
    endTimeWork.setHours(endH, endM, 0, 0);

    // El cronómetro sólo avanza si es un horario laboral válido
    const isWorkingTime = !isWeekend && now >= startTimeWork && now < endTimeWork;

    // DETECCIÓN DE CIERRE DE JORNADA (Petición de usuario)
    // Si antes estábamos en horario laboral y ahora no, informamos al servidor.
    // Usamos un flag para evitar bucles de sincronización infinitos si el reloj oscila.
    if (window.lastWorkingState === true && isWorkingTime === false && !window.isSyncingWorkday) {
        console.log("[Workflow] Jornada laboral finalizada. Sincronizando tiempos con el servidor...");
        window.isSyncingWorkday = true;
        window.apiFetch('/workflow/sync-timer', { method: 'POST' })
            .then(() => {
                setTimeout(() => { window.isSyncingWorkday = false; }, 60000); // Bloquear re-sync por 1 minuto
                if (window.recargarKanban) window.recargarKanban();
            })
            .catch(() => { window.isSyncingWorkday = false; });
    }
    window.lastWorkingState = isWorkingTime;

    // Duración de 1 día laboral según la configuración (en segundos)
    const secondsInDay = Math.max(3600, (endTimeWork - startTimeWork) / 1000);

    document.querySelectorAll('.timer-badge[data-elapsed]').forEach(badge => {
        let elapsed = parseInt(badge.getAttribute('data-elapsed'), 10) || 0;
        const isPaused = badge.getAttribute('data-paused') === '1';
        
        // Simpre incrementamos si estamos en horas laborales
        if (isWorkingTime && !isPaused) {
            elapsed++;
            badge.setAttribute('data-elapsed', elapsed);
        }

        // Formateo robusto para asegurar que cambie visualmente cada segundo
        const d = Math.floor(elapsed / secondsInDay);
        const rem = elapsed % secondsInDay;
        const h = Math.floor(rem / 3600);
        const m = Math.floor((rem % 3600) / 60);
        const s = rem % 60;
        
        let parts = [];
        if (d > 0) parts.push(`${d}d`);
        if (h > 0 || (d > 0 && (m > 0 || s > 0))) parts.push(`${h}h`);
        if (m > 0 || ((d > 0 || h > 0) && s > 0)) parts.push(`${m}m`);
        if (s > 0 || parts.length === 0) parts.push(`${s}s`);
        
        const textContainer = badge.querySelector('.elapsed-time');
        if (textContainer) {
            textContainer.textContent = isPaused ? 'Pausado' : parts.join(' ');
        }
    });
}
setInterval(updateTimers, 1000);
updateTimers();

// 2. PERSISTENCIA DE INTERFAZ (UI Memory)
// Manejo de tabs/selectores de bloque
window.selectWorkflowBlock = function(blockId) {
    // Actualizar selectores
    document.querySelectorAll('.workflow-selector').forEach(s => s.classList.remove('active'));
    const activeSelector = document.querySelector(`.workflow-selector[data-block-id="${blockId}"]`);
    if (activeSelector) activeSelector.classList.add('active');
    
    // Actualizar paneles
    document.querySelectorAll('.workflow-panel').forEach(p => p.classList.remove('active'));
    const activePanel = document.getElementById(`panel-${blockId}`);
    if (activePanel) activePanel.classList.add('active');
    
    // Guardar preferencia
    localStorage.setItem('selectedWorkflowBlock', blockId);
};

// Restaurar bloque seleccionado
window.restoreSelectedBlock = function() {
    const savedBlock = localStorage.getItem('selectedWorkflowBlock');
    if (savedBlock && document.getElementById(`panel-${savedBlock}`)) {
        window.selectWorkflowBlock(savedBlock);
    }
}

window.toggleBlockVisibility = function (btn, blockKey) {
    const block = btn.closest('.workflow-block');
    block.classList.toggle('collapsed');
    
    const collapsedBlocks = JSON.parse(localStorage.getItem('collapsedBlocks') || '{}');
    collapsedBlocks[blockKey] = block.classList.contains('collapsed');
    localStorage.setItem('collapsedBlocks', JSON.stringify(collapsedBlocks));
}

// 3. MOTOR DE ESTADOS DINÁMICOS (On-Demand Loading)
// Cuando se abre el detalle de una cuenta, consultamos al servidor qué movimientos 
// son válidos legalmente para ese estado específico.
document.addEventListener('show.bs.modal', function (event) {
    const modal = event.target;
    if (!modal.id.startsWith('modalCuenta')) return;

    const cuentaId = modal.id.replace('modalCuenta', '');
    const statusButtons = document.getElementById('statusButtons' + cuentaId);

    if (!statusButtons || statusButtons.dataset.loaded === 'true') return;

    console.log(`[Workflow] Cargando estados para Cuenta ${cuentaId} desde: /workflow/estados-disponibles/${cuentaId}`);
    window.apiFetch(`/workflow/estados-disponibles/${cuentaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusButtons.innerHTML = '';
                if (data.estados_disponibles.length === 0) {
                    statusButtons.innerHTML = '<span class="text-white-50 small">Sin acciones disponibles</span>';
                }
                data.estados_disponibles.forEach(estado => {
                    const btn = document.createElement('button');
                    btn.className = `btn-estado ${estado.tipo === 'APROBADO' ? 'btn-aprobado' : (estado.tipo === 'DEVUELTO' ? 'btn-devuelto' : 'btn-proceso')}`;
                    
                    if (estado.color) btn.style.backgroundColor = estado.color;
                    btn.textContent = estado.nombre;
                    btn.onclick = () => cambiarEstado(cuentaId, estado.id, estado.requiere_comentario, estado.nombre);
                    statusButtons.appendChild(btn);
                });
                statusButtons.dataset.loaded = 'true';
            } else {
                statusButtons.innerHTML = `<span class="text-warning small"><i class="fas fa-exclamation-triangle me-1"></i> ${data.message || 'Error al cargar'}</span>`;
            }
        })
        .catch(err => {
            console.error('[Workflow] Error al cargar estados:', err);
            statusButtons.innerHTML = '<span class="text-danger small"><i class="fas fa-exclamation-circle me-1"></i>Error de conexión</span>';
        });
});

/**
 * LÓGICA DE TRANSICIÓN:
 * Maneja el cambio de estado, la captura de comentarios con SweetAlert2 
 * y detecta si se requiere un "Handoff" (Asignación de responsable)
 * o si la transición Sin Tramite → En Revision requiere actualizar SS.
 */
async function cambiarEstado(cuentaId, estadoDestinoId, requiereComentario, estadoNombre) {
    let comentario = null;

    window.apiFetch(`/workflow/cambiar-estado/${cuentaId}`, {
        method: 'POST',
        body: JSON.stringify({ estado_destino_id: estadoDestinoId, comentario: comentario })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // FLUJO ESPECIAL 1: Requiere mes de SS Última Cuenta (Sin Tramite → En Revision)
            if (data.requires_ss_cuenta) {
                abrirModalSsCuenta(data);
                return;
            }
            // FLUJO ESPECIAL 2: Handoff de Responsable (cambio de bloque)
            if (data.requires_responsible) {
                abrirModalResponsable(data, cuentaId);
            } else {
                actualizarInterfazWorkflow(data.message, cuentaId);
            }
        } else {
            window.showSnackbar(`❌ ${data.message}`, 'error');
        }
    });
}

/**
 * Abre el modal de selección de mes (SS Última Cuenta).
 * Se llama cuando el backend responde con requires_ss_cuenta = true.
 */
function abrirModalSsCuenta(data) {
    // Guardar datos en los campos ocultos del modal
    document.getElementById('ssCuentaId').value = data.cuenta_id;
    document.getElementById('ssCuentaEstadoDestinoId').value = data.estado_destino_id;
    document.getElementById('ssCuentaMesValor').value = '';

    // Limpiar selección previa de botones de mes
    document.querySelectorAll('.mes-ss-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '';
        btn.style.color = '';
        btn.style.borderColor = '';
    });

    // Ocultar preview de mes
    const preview = document.getElementById('mesSeleccionadoPreview');
    if (preview) preview.style.display = 'none';
    const textoPreview = document.getElementById('mesSeleccionadoTexto');
    if (textoPreview) textoPreview.textContent = '—';

    // Deshabilitar el botón confirmar hasta que se elija mes
    const btnConfirmar = document.getElementById('btnConfirmarSsCuenta');
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.style.opacity = '0.5';
        btnConfirmar.style.cursor = 'not-allowed';
    }

    // Ocultar modal de cuenta si estaba abierto
    const modalCuenta = document.querySelector('.modal.show[id^="modalCuenta"]');
    if (modalCuenta) {
        const mInst = bootstrap.Modal.getInstance(modalCuenta);
        if (mInst) mInst.hide();
    }

    // Mostrar modal SS
    const modalSs = document.getElementById('modalSsUltimaCuenta');
    if (modalSs) {
        new bootstrap.Modal(modalSs).show();
    }
}


function abrirModalResponsable(data, cuentaId) {
    document.getElementById('cuentaIdResponsable').value = data.cuenta_id;
    document.getElementById('estadoDestinoIdResponsable').value = data.estado_destino_id;
    document.getElementById('isDevolucionResponsable').value = data.es_devolucion ? '1' : '0';
    document.getElementById('isDevolucionSupervisor').value = '0';

    setWorkflowResponsibleModalState({
        cuentaId: data.cuenta_id,
        estadoDestinoId: data.estado_destino_id,
        bloqueDestinoId: data.target_bloque_id || null,
        bloqueDestinoCodigo: data.target_bloque_codigo || null,
        esDevolucion: !!data.es_devolucion,
        esDevolucionSupervisor: false,
        supervisorContrato: null,
        supervisorUsuario: null,
        comentario: '',
        loadingSupervisor: false,
        error: null,
    });

    // Personalizar etiquetas según si es devolución o avance
    const labelComentario = document.getElementById('labelComentario');
    const inputComentario = document.getElementById('comentarioResponsable');
    if (labelComentario && inputComentario) {
        if (data.es_devolucion) {
            labelComentario.innerHTML = 'Motivo de Devolución <span class="text-danger">*</span>:';
            inputComentario.placeholder = 'Explique detalladamente por qué se devuelve la cuenta...';
        } else {
            labelComentario.textContent = 'Comentarios adicionales:';
            inputComentario.placeholder = 'Escriba observaciones opcionales...';
        }
        inputComentario.value = ''; // Limpiar previo
    }

    const selectBloque = document.getElementById('selectBloqueDestino');
    // Poblar el selector de bloques
    if (selectBloque) {
        selectBloque.innerHTML = '';
        if (data.bloques_disponibles) {
            data.bloques_disponibles.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.dataset.codigo = b.codigo;
                opt.textContent = b.nombre;
                if (b.id == data.target_bloque_id) opt.selected = true;
                selectBloque.appendChild(opt);
            });
        }

        // Evento para recargar responsables al cambiar bloque
        selectBloque.onchange = function () {
            const selectedOpt = selectBloque.options[selectBloque.selectedIndex];
            const selectedBloqueCodigo = selectedOpt?.dataset?.codigo || '';
            setWorkflowResponsibleModalState({
                bloqueDestinoId: selectedOpt?.value || null,
                bloqueDestinoCodigo: selectedBloqueCodigo || null,
                esDevolucion: !!window.workflowResponsibleModalState.esDevolucion,
                esDevolucionSupervisor: false,
                supervisorContrato: null,
                supervisorUsuario: null,
            });

            const mostrarSupervisor = window.workflowResponsibleModalState.esDevolucion && selectedBloqueCodigo === 'REV1';
            toggleDevolucionSupervisorUI(mostrarSupervisor);
            document.getElementById('isDevolucionSupervisor').value = '0';

            if (selectedBloqueCodigo) {
                cargarResponsablesPorBloque(selectedBloqueCodigo);
            }
        };
    }

    toggleDevolucionSupervisorUI(!!data.es_devolucion && data.target_bloque_codigo === 'REV1');

    const checkboxSupervisor = document.getElementById('checkDevolucionSupervisor');
    if (checkboxSupervisor) {
        checkboxSupervisor.checked = false;
        checkboxSupervisor.onchange = async function () {
            const bloqueSeleccionado = getSelectedBloqueDestinoOption();
            const esBloqueUno = bloqueSeleccionado?.codigo === 'REV1';
            const esFlujoDevolucion = !!window.workflowResponsibleModalState.esDevolucion;

            if (!esBloqueUno || !esFlujoDevolucion) {
                checkboxSupervisor.checked = false;
                document.getElementById('isDevolucionSupervisor').value = '0';
                setWorkflowResponsibleModalState({ esDevolucionSupervisor: false });
                toggleDevolucionSupervisorUI(false);
                return;
            }

            setWorkflowResponsibleModalState({ esDevolucionSupervisor: checkboxSupervisor.checked });
            document.getElementById('isDevolucionSupervisor').value = checkboxSupervisor.checked ? '1' : '0';

            if (checkboxSupervisor.checked) {
                const supervisorData = await cargarSupervisorPorDefecto(data.cuenta_id);
                const bloqueActual = getSelectedBloqueDestinoOption();
                if (bloqueActual?.codigo) {
                    cargarResponsablesPorBloque(bloqueActual.codigo, supervisorData?.supervisor_usuario || null);
                }
            } else {
                const notice = document.getElementById('devolucionSupervisorNotice');
                if (notice) notice.classList.add('d-none');
            }
        };
    }

    // Carga inicial de responsables para el bloque sugerido
    cargarResponsablesPorBloque(data.target_bloque_codigo);

    // Bloquear el selector de bloque si el servidor lo indica
    const selectBloqueLocked = document.getElementById('selectBloqueDestino');
    if (selectBloqueLocked) {
        selectBloqueLocked.disabled = data.block_locked || false;
    }

    new bootstrap.Modal(document.getElementById('modalAsignarResponsable')).show();
    
    // Ocultar modal de detalle si está abierto
    const modalDetail = document.getElementById('modalCuenta' + cuentaId);
    if (modalDetail) {
        const mOld = bootstrap.Modal.getInstance(modalDetail);
        if (mOld) mOld.hide();
    }
}

function cargarResponsablesPorBloque(bloqueCodigo, supervisorDefault = null) {
    const select = document.getElementById('selectResponsable');
    select.innerHTML = '<option value="">-- Cargando responsables --</option>';
    select.disabled = true;

    window.apiFetch(`/workflow/usuarios-responsables?bloque_codigo=${bloqueCodigo}`)
        .then(res => res.json())
        .then(userData => {
            select.innerHTML = '<option value="">-- Seleccione un receptor --</option>';
            select.disabled = false;
            if (userData.success) {
                userData.usuarios.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = u.nombre;
                    select.appendChild(opt);
                });

                if (supervisorDefault && supervisorDefault.id) {
                    ensureOptionInResponsablesSelect(select, supervisorDefault);
                    select.value = supervisorDefault.id;
                }
            } else {
                window.showSnackbar('No se pudieron cargar responsables para este bloque', 'error');
            }
        })
        .catch(err => {
            select.disabled = false;
            select.innerHTML = '<option value="">Error al cargar</option>';
        });
}

function actualizarInterfazWorkflow(message, cuentaId) {
    window.showSnackbar(`✅ ${message}`, 'success');
    const mOld = bootstrap.Modal.getInstance(document.getElementById('modalCuenta' + cuentaId));
    if (mOld) mOld.hide();
    
    // Refrescamos sólo el tablero si tenemos el motor AJAX listo
    if (window.recargarKanban) setTimeout(() => window.recargarKanban(), 800);
    else setTimeout(() => location.reload(), 1200);
}


// Handle responsible assignment confirmation
document.addEventListener('DOMContentLoaded', function () {
    const btnConfirmar = document.getElementById('btnConfirmarResponsable');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function () {
            const cuentaId = document.getElementById('cuentaIdResponsable').value;
            const estadoDestinoId = document.getElementById('estadoDestinoIdResponsable').value;
            const responsableId = document.getElementById('selectResponsable').value;
            const bloqueId = document.getElementById('selectBloqueDestino').value;
            const comentario = document.getElementById('comentarioResponsable').value;
            const esDevolucion = document.getElementById('isDevolucionResponsable').value === '1';
            const esDevolucionSupervisor = document.getElementById('isDevolucionSupervisor').value === '1';

            if (!bloqueId) {
                window.showSnackbar('❌ Debe seleccionar un bloque de destino', 'error');
                return;
            }

            if (!responsableId) {
                window.showSnackbar('❌ Debe seleccionar un responsable', 'error');
                return;
            }

            if (esDevolucion && (!comentario || comentario.trim().length < 10)) {
                window.showSnackbar(`❌ El motivo de devolución debe tener al menos 10 caracteres (actualmente: ${comentario ? comentario.trim().length : 0})`, 'error');
                return;
            }

            window.apiFetch(`/workflow/asignar-responsable/${cuentaId}`, {
                method: 'POST',
                body: JSON.stringify({
                    estado_destino_id: estadoDestinoId,
                    bloque_id: bloqueId,
                    responsable_id: responsableId,
                    comentario: comentario,
                    es_devolucion_supervisor: esDevolucionSupervisor,
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.showSnackbar('✅ ' + data.message, 'success');

                        // Cierra el modal de responsable
                        const modalResp = bootstrap.Modal.getInstance(document.getElementById('modalAsignarResponsable'));
                        if (modalResp) modalResp.hide();

                        if (window.recargarKanban) {
                            setTimeout(() => window.recargarKanban(), 1000);
                        } else {
                            setTimeout(() => location.reload(), 1500);
                        }
                    } else {
                        window.showSnackbar('❌ Error: ' + data.message, 'error');
                    }
                });
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // MODAL SS ÚLTIMA CUENTA — Eventos
    // ══════════════════════════════════════════════════════════════════════

    // Selección de mes via botones en el grid
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.mes-ss-btn');
        if (!btn) return;

        // Deseleccionar todos
        document.querySelectorAll('.mes-ss-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = '';
            b.style.color = '';
            b.style.borderColor = '';
            b.style.fontWeight = '';
        });

        // Seleccionar el clickeado
        btn.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
        btn.style.color = '#fff';
        btn.style.borderColor = '#667eea';
        btn.style.fontWeight = 'bold';

        const mes = btn.dataset.mes;
        document.getElementById('ssCuentaMesValor').value = mes;

        // Actualizar preview
        const preview = document.getElementById('mesSeleccionadoPreview');
        const textoPreview = document.getElementById('mesSeleccionadoTexto');
        if (preview) preview.style.display = 'block';
        if (textoPreview) textoPreview.textContent = mes;

        // Habilitar el botón confirmar
        const btnConf = document.getElementById('btnConfirmarSsCuenta');
        if (btnConf) {
            btnConf.disabled = false;
            btnConf.style.opacity = '1';
            btnConf.style.cursor = 'pointer';
        }
    });

    // Confirmar: re-enviar la transición con el mes seleccionado
    const btnConfSs = document.getElementById('btnConfirmarSsCuenta');
    if (btnConfSs) {
        btnConfSs.addEventListener('click', function () {
            const cuentaId        = document.getElementById('ssCuentaId').value;
            const estadoDestinoId = document.getElementById('ssCuentaEstadoDestinoId').value;
            const mes             = document.getElementById('ssCuentaMesValor').value;

            if (!mes) {
                window.showSnackbar('❌ Debe seleccionar un mes', 'error');
                return;
            }

            // Deshabilitar para evitar doble click
            btnConfSs.disabled = true;
            btnConfSs.style.opacity = '0.7';
            btnConfSs.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Procesando...';

            window.apiFetch(`/workflow/cambiar-estado/${cuentaId}`, {
                method: 'POST',
                body: JSON.stringify({
                    estado_destino_id: estadoDestinoId,
                    ss_ultima_cuenta: mes,
                    comentario: null
                })
            })
            .then(r => r.json())
            .then(data => {
                // Cerrar modal SS
                const modalSs = bootstrap.Modal.getInstance(document.getElementById('modalSsUltimaCuenta'));
                if (modalSs) modalSs.hide();

                if (data.success) {
                    if (data.requires_responsible) {
                        abrirModalResponsable(data, cuentaId);
                        return;
                    }
                    window.showSnackbar(`✅ Pasado a En Revisión. SS: ${mes}`, 'success');
                    if (window.recargarKanban) setTimeout(() => window.recargarKanban(), 800);
                    else setTimeout(() => location.reload(), 1200);
                } else {
                    window.showSnackbar(`❌ ${data.message}`, 'error');
                    // Restaurar botón
                    btnConfSs.disabled = false;
                    btnConfSs.style.opacity = '1';
                    btnConfSs.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar y Pasar a En Revisión';
                }
            })
            .catch(() => {
                window.showSnackbar('❌ Error de red. Intente de nuevo.', 'error');
                btnConfSs.disabled = false;
                btnConfSs.style.opacity = '1';
                btnConfSs.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar y Pasar a En Revisión';
            });
        });
    }

    // Cancelar: simplemente cierra el modal SS
    const btnCancelSs = document.getElementById('btnCancelarSsCuenta');
    if (btnCancelSs) {
        btnCancelSs.addEventListener('click', function () {
            const modalSs = bootstrap.Modal.getInstance(document.getElementById('modalSsUltimaCuenta'));
            if (modalSs) modalSs.hide();
        });
    }

    // 3. Inicialización de tabs
    if (typeof window.restoreSelectedBlock === 'function') window.restoreSelectedBlock();
});

/**
 * Crea una cuenta paralela y refresca el tablero Kanban.
 * Disponible desde el modal de gestión en Workflow y Dashboard.
 */
window.startParallelAccount = function (id, contrato, siguienteCuenta) {
    Swal.fire({
        title: '¿Iniciar cuenta paralela?',
        text: `¿Desea iniciar un nuevo trámite paralelo para la cuenta #${siguienteCuenta} del contrato ${contrato}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#004884',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, iniciar paralela',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: {
            popup: 'premium-swal-popup',
            title: 'premium-swal-title'
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        window.apiFetch(`/workflow/iniciar-siguiente-cuenta/${id}`, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    window.showSnackbar('⚠️ ' + (data.message || 'No se pudo crear la cuenta paralela'), 'error');
                    return;
                }

                window.showSnackbar(`✅ Cuenta paralela #${data.numero_cuenta} creada correctamente`, 'success');
                window.pendingOpenCuentaId = data.id;
                window.pendingOpenCuentaBanner = '⚠️ Esta es una cuenta paralela recién creada';

                if (typeof window.recargarKanban === 'function') {
                    window.recargarKanban(data.bloque_inicial_id || null, {
                        openCuentaId: data.id,
                        resetFilters: true,
                    });
                } else if (typeof fetchFilteredData === 'function') {
                    fetchFilteredData();
                } else {
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error(err);
                window.showSnackbar('❌ Error al iniciar la cuenta paralela', 'error');
            });
    });
};
