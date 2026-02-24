// Update timers
function updateTimers() {
    document.querySelectorAll('.timer-badge[data-start]').forEach(badge => {
        const startTimeStr = badge.getAttribute('data-start');
        if (!startTimeStr) return;
        const startTime = new Date(startTimeStr);
        const now = new Date();
        const diffMs = now - startTime;
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

// Function to toggle block visibility
window.toggleBlockVisibility = function (btn, blockKey) {
    const block = btn.closest('.workflow-block');
    block.classList.toggle('collapsed');

    // Save state to localStorage
    const collapsedBlocks = JSON.parse(localStorage.getItem('collapsedBlocks') || '{}');
    collapsedBlocks[blockKey] = block.classList.contains('collapsed');
    localStorage.setItem('collapsedBlocks', JSON.stringify(collapsedBlocks));
}

// Restore collapsed blocks on load
document.addEventListener('DOMContentLoaded', function () {
    // Remove loading state
    const container = document.querySelector('.premium-loading-container');
    if (container) container.classList.remove('loading');

    const collapsedBlocks = JSON.parse(localStorage.getItem('collapsedBlocks') || '{}');
    document.querySelectorAll('.workflow-block').forEach((block) => {
        const btn = block.querySelector('.btn-collapse');
        if (!btn) return;

        const onclickAttr = btn.getAttribute('onclick');
        if (!onclickAttr) return;
        const keyMatch = onclickAttr.match(/'([^']+)'/);
        if (keyMatch && collapsedBlocks[keyMatch[1]]) {
            block.classList.add('collapsed');
        }
    });
});

// Workflow Actions logic
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
                    btn.className = 'btn-estado';

                    // Determinar color por tipo
                    if (estado.tipo === 'APROBADO' || estado.tipo === 'FINAL') btn.classList.add('btn-aprobado');
                    else if (estado.tipo === 'DEVUELTO') btn.classList.add('btn-devuelto');
                    else btn.classList.add('btn-proceso');

                    if (estado.color) btn.style.backgroundColor = estado.color;

                    btn.textContent = estado.nombre;
                    btn.onclick = () => cambiarEstado(cuentaId, estado.id, estado.requiere_comentario, estado.nombre);
                    statusButtons.appendChild(btn);
                });
                statusButtons.dataset.loaded = 'true';
            }
        });
});

function cambiarEstado(cuentaId, estadoDestinoId, requiereComentario, estadoNombre) {
    let comentario = null;
    if (requiereComentario) {
        comentario = prompt(`Ingrese un comentario para el cambio a: ${estadoNombre} (Opcional)`);
        if (comentario === null) return; // User cancelled
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(`/workflow/cambiar-estado/${cuentaId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            estado_destino_id: estadoDestinoId,
            comentario: comentario
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check if responsible assignment is required
                if (data.requires_responsible) {
                    // Store data for later use
                    document.getElementById('cuentaIdResponsable').value = data.cuenta_id;
                    document.getElementById('estadoDestinoIdResponsable').value = data.estado_destino_id;
                    document.getElementById('estadoCodigoResponsable').value = data.estado_codigo;

                    // Fetch users based on estado_codigo
                    fetch(`/workflow/usuarios-responsables/${data.estado_codigo}`)
                        .then(response => response.json())
                        .then(userData => {
                            if (userData.success) {
                                // Populate the select dropdown
                                const select = document.getElementById('selectResponsable');
                                select.innerHTML = '<option value="">-- Seleccione un responsable --</option>';

                                userData.usuarios.forEach(usuario => {
                                    const option = document.createElement('option');
                                    option.value = usuario.id;
                                    option.textContent = `${usuario.nombre} (${usuario.tipo_responsable})`;
                                    option.dataset.tipoResponsable = usuario.tipo_responsable;
                                    select.appendChild(option);
                                });

                                // Update modal message based on block
                                const message = document.getElementById('modalResponsableMessage');
                                const badgeContainer = document.getElementById('responsableBadgeContainer');
                                const badge = document.getElementById('responsableBadge');

                                if (data.estado_codigo === 'REV1_PASA') {
                                    message.innerHTML = '<i class="fas fa-info-circle me-2"></i>Seleccione el responsable para el bloque SAP:';
                                    badge.className = 'badge bg-primary';
                                    badge.innerHTML = '<i class="fas fa-cogs me-1"></i>Responsable SAP';
                                    badgeContainer.classList.remove('d-none');
                                } else if (data.estado_codigo === 'SAP_OK') {
                                    message.innerHTML = '<i class="fas fa-info-circle me-2"></i>Seleccione el responsable para el bloque Facturación:';
                                    badge.className = 'badge bg-success';
                                    badge.innerHTML = '<i class="fas fa-money-bill-wave me-1"></i>Responsable Facturación';
                                    badgeContainer.classList.remove('d-none');
                                }

                                // Show the responsible assignment modal
                                const modalResponsable = new bootstrap.Modal(document.getElementById('modalAsignarResponsable'));
                                modalResponsable.show();

                                // Close the account modal
                                const modalCuenta = bootstrap.Modal.getInstance(document.getElementById('modalCuenta' + cuentaId));
                                if (modalCuenta) {
                                    modalCuenta.hide();
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching users:', error);
                            window.showSnackbar('❌ Error al cargar usuarios', 'error');
                        });

                    return;
                } else {
                    // Normal flow - state changed successfully
                    window.showSnackbar('✅ ' + data.message, 'success');
                    if (window.recargarKanban) {
                        setTimeout(() => window.recargarKanban(), 1000);
                    } else {
                        setTimeout(() => location.reload(), 1500);
                    }
                }
            } else {
                window.showSnackbar('❌ Error: ' + data.message, 'error');
            }
        });
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
