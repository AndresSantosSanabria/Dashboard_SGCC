/**
 * FRONTEND OPERATIVO - WORKFLOW KANBAN
 * 
 * Gestiona la reactividad del tablero, el cronómetro de las tarjetas 
 * y la lógica de transición de estados vía AJAX.
 */

// 1. MOTOR DE TIEMPO REAL (Card Aging)
// Actualiza los badges de tiempo cada segundo para mostrar cuánto lleva 
// estancada una cuenta en su estado actual.
function updateTimers() {
    document.querySelectorAll('.timer-badge[data-start]').forEach(badge => {
        const startTimeStr = badge.getAttribute('data-start');
        if (!startTimeStr) return;
        
        const startTime = new Date(startTimeStr);
        const diffMs = new Date() - startTime;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        let display = "";
        if (diffDays > 0) display += `${diffDays}d `;
        if (diffHours > 0 || diffDays > 0) display += `${diffHours % 24}h `;
        if (diffMins > 0 || diffHours > 0 || diffDays > 0) display += `${diffMins % 60}m `;
        display += `${diffSecs % 60}s`;
        
        badge.querySelector('.elapsed-time').textContent = display;
    });
}
setInterval(updateTimers, 1000);
updateTimers();

// 2. PERSISTENCIA DE INTERFAZ (UI Memory)
// Recuerda qué bloques ha colapsado el usuario para mantener su espacio de trabajo limpio.
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

    fetch(`/workflow/estados-disponibles/${cuentaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusButtons.innerHTML = '';
                data.estados_disponibles.forEach(estado => {
                    const btn = document.createElement('button');
                    btn.className = `btn-estado ${estado.tipo === 'APROBADO' ? 'btn-aprobado' : (estado.tipo === 'DEVUELTO' ? 'btn-devuelto' : 'btn-proceso')}`;
                    
                    if (estado.color) btn.style.backgroundColor = estado.color;
                    btn.textContent = estado.nombre;
                    btn.onclick = () => cambiarEstado(cuentaId, estado.id, estado.requiere_comentario, estado.nombre);
                    statusButtons.appendChild(btn);
                });
                statusButtons.dataset.loaded = 'true';
            }
        });
});

/**
 * LÓGICA DE TRANSICIÓN:
 * Maneja el cambio de estado, la captura de comentarios con SweetAlert2 
 * y detecta si se requiere un "Handoff" (Asignación de responsable).
 */
async function cambiarEstado(cuentaId, estadoDestinoId, requiereComentario, estadoNombre) {
    let comentario = null;
    if (requiereComentario) {
        const { value: text, isConfirmed } = await Swal.fire({
            title: 'Comentario de Seguimiento',
            input: 'textarea',
            inputLabel: `Justificación para: ${estadoNombre}`,
            inputPlaceholder: 'Escriba aquí su comentario operativo...',
            showCancelButton: true,
            confirmButtonText: 'Confirmar Cambio',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            confirmButtonColor: '#0057b8',
        });

        if (!isConfirmed) return;
        comentario = text;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(`/workflow/cambiar-estado/${cuentaId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ estado_destino_id: estadoDestinoId, comentario: comentario })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // FLUJO ESPECIAL: Handoff de Responsable
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

function abrirModalResponsable(data, cuentaId) {
    document.getElementById('cuentaIdResponsable').value = data.cuenta_id;
    document.getElementById('estadoDestinoIdResponsable').value = data.estado_destino_id;

    fetch(`/workflow/usuarios-responsables/${data.estado_codigo}`)
        .then(res => res.json())
        .then(userData => {
            if (userData.success) {
                const select = document.getElementById('selectResponsable');
                select.innerHTML = '<option value="">-- Seleccione un receptor --</option>';
                userData.usuarios.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = `${u.nombre} (${u.tipo_responsable})`;
                    select.appendChild(opt);
                });

                // UI Feedback: Mostramos quién recibirá la cuenta
                const badge = document.getElementById('responsableBadge');
                if (data.estado_codigo === 'REV1_PASA') {
                    badge.className = 'badge bg-primary';
                    badge.innerHTML = '<i class="fas fa-cogs me-1"></i>Responsable SAP';
                } else {
                    badge.className = 'badge bg-success';
                    badge.innerHTML = '<i class="fas fa-money-bill-wave me-1"></i>Responsable Facturación';
                }

                new bootstrap.Modal(document.getElementById('modalAsignarResponsable')).show();
                const mOld = bootstrap.Modal.getInstance(document.getElementById('modalCuenta' + cuentaId));
                if (mOld) mOld.hide();
            }
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

            if (!responsableId) {
                window.showSnackbar('❌ Debe seleccionar un responsable', 'error');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/workflow/asignar-responsable/${cuentaId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    estado_destino_id: estadoDestinoId,
                    responsable_id: responsableId
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
});
