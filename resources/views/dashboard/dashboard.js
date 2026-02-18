document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("inputId");
    const fileNameDisplay = document.getElementById("fileNameDisplay");
    const btnImport = document.getElementById("btnImport");
    const importForm = document.getElementById("importForm");
    const importSpinner = document.getElementById("importSpinner");

    const btnSaveManual = document.getElementById("btnSaveManual");
    const manualForm = document.getElementById("manualForm");
    const manualModalElement = document.getElementById("manualEntryModal");
    let manualModal = null;
    if (manualModalElement) {
        manualModal = new bootstrap.Modal(manualModalElement);
    }

    // Manejo de selección de archivo
    if (input) {
        input.addEventListener("change", function (e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = file.name;
                btnImport.disabled = false;
            } else {
                fileNameDisplay.textContent = "Sin archivo seleccionado";
                btnImport.disabled = true;
            }
        });
    }

    // AJAX Import Excel
    if (importForm) {
        importForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            btnImport.disabled = true;
            importSpinner.style.display = "block";

            fetch(
                importForm.dataset.url || "{{ route('dashboard.importar') }}",
                {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector(
                            'input[name="_token"]',
                        ).value,
                        Accept: "application/json",
                    },
                },
            )
                .then((response) =>
                    response.json().catch(() => ({
                        success: false,
                        message: "Respuesta no válida del servidor",
                    })),
                )
                .then((data) => {
                    importSpinner.style.display = "none";
                    if (data.success) {
                        showSnackbar("✅ " + data.message, "success");
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showSnackbar("⚠️ " + data.message, "error");
                        btnImport.disabled = false;
                    }
                })
                .catch((error) => {
                    importSpinner.style.display = "none";
                    showSnackbar("❌ Error: " + error.message, "error");
                    btnImport.disabled = false;
                });
        });
    }

    // AJAX Manual Entry
    if (btnSaveManual) {
        btnSaveManual.addEventListener("click", function () {
            if (!manualForm.reportValidity()) {
                return;
            }
            const formData = new FormData(manualForm);
            const url = manualForm.dataset.url || "{{ route('dashboard.manual') }}";

            // Determinar si es Update (contiene 'actualizar' en la URL)
            const isUpdate = url.includes("actualizar");

            if (isUpdate) {
                formData.append("_method", "PUT");
            }

            btnSaveManual.disabled = true;
            btnSaveManual.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...';

            fetch(url, {
                method: "POST", // Siempre POST para enviar FormData con archivos/datos, Laravel lee _method
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector(
                        'input[name="_token"]',
                    ).value,
                    Accept: "application/json",
                },
            })
                .then((response) =>
                    response.json().catch(() => ({
                        success: false,
                        message: "Respuesta no válida del servidor",
                    })),
                )
                .then((data) => {
                    if (data.success) {
                        showSnackbar("✅ " + data.message, "success");
                        if (manualModal) manualModal.hide();
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showSnackbar("⚠️ " + data.message, "error");
                        btnSaveManual.disabled = false;
                        btnSaveManual.textContent = isUpdate ? "Actualizar Registro" : "Cargar Registro";
                    }
                })
                .catch((error) => {
                    showSnackbar("❌ Error: " + error.message, "error");
                    btnSaveManual.disabled = false;
                    const isUpdate = (manualForm.dataset.url || "").includes("actualizar");
                    btnSaveManual.textContent = isUpdate ? "Actualizar Registro" : "Cargar Registro";
                });
        });
    }

    // Remove loading state on page load
    const container = document.querySelector('.premium-loading-container');
    if (container) {
        setTimeout(() => container.classList.remove('loading'), 100);
    }
});

// --- Lógica de Filtros Instantáneos (Live Search) ---
const filtersForm = document.getElementById("filtersForm");
const tableContainer = document.getElementById("tableContainer");
const resultsCountEl = document.getElementById("resultsCount");
const tableSpinner = document.getElementById("tableSpinner");

let abortController = null;

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

const fetchFilteredData = () => {
    // Cancelar petición previa si existe
    if (abortController) {
        abortController.abort();
    }
    abortController = new AbortController();

    const formData = new FormData(filtersForm);
    const params = new URLSearchParams(formData).toString();
    const url = `${filtersForm.action}?${params}`;

    if (tableSpinner) tableSpinner.style.display = "inline-block";
    tableContainer.style.opacity = "0.5";
    tableContainer.style.pointerEvents = "none";

    fetch(url, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
        signal: abortController.signal,
    })
        .then((response) => {
            const total = response.headers.get("X-Total-Count");
            if (total !== null && resultsCountEl) {
                resultsCountEl.textContent = `Resultados: ${total}`;
            }
            return response.text();
        })
        .then((html) => {
            tableContainer.innerHTML = html;
            if (tableSpinner) tableSpinner.style.display = "none";
            tableContainer.style.opacity = "1";
            tableContainer.style.pointerEvents = "auto";

            // Re-vincular eventos de paginación AJAX
            bindPagination();
            // Re-aplicar estado de columnas
            applyMinimizedColumns();
        })
        .catch((error) => {
            if (error.name === "AbortError") return;
            console.error("Error fetching filtered data:", error);
            if (tableSpinner) tableSpinner.style.display = "none";
            tableContainer.style.opacity = "1";
            tableContainer.style.pointerEvents = "auto";
        });
};

// El refresco automático ha sido eliminado a petición del usuario.
// Ahora se requiere pulsar el botón "Filtrar" o presionar Enter.
if (filtersForm) {
    filtersForm.addEventListener("submit", function (e) {
        e.preventDefault();
        fetchFilteredData();
    });
}

// Manejo de paginación AJAX
function bindPagination() {
    const links = document.querySelectorAll("#pagination-links a");
    links.forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();

            // Cancelar búsquedas en curso si se cambia de página
            if (abortController) abortController.abort();

            const url = this.href;
            if (tableSpinner) tableSpinner.style.display = "inline-block";
            tableContainer.style.opacity = "0.5";

            fetch(url, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.text())
                .then((html) => {
                    tableContainer.innerHTML = html;
                    if (tableSpinner) tableSpinner.style.display = "none";
                    tableContainer.style.opacity = "1";
                    bindPagination();

                    // Re-aplicar estado de columnas
                    applyMinimizedColumns();

                    // Scroll top suave hacia la tabla
                    tableContainer.scrollIntoView({
                        behavior: "smooth",
                        block: "start",
                    });
                })
                .catch((err) => console.error("Error pagination:", err));
        });
    });
}

// --- Lógica de Ocultar Columnas ---
let minimizedColumns = new Set();

window.toggleColumn = function (index) {
    if (minimizedColumns.has(index)) {
        minimizedColumns.delete(index);
    } else {
        minimizedColumns.add(index);
    }
    applyMinimizedColumns();
};

window.resetColumns = function () {
    minimizedColumns.clear();
    applyMinimizedColumns();
};

function applyMinimizedColumns() {
    const table = document.getElementById("cuentasTable");
    const resetBtn = document.getElementById("btnResetColumns");
    if (!table) return;

    // Resetear todo
    table.querySelectorAll(".column-hidden").forEach((el) => el.classList.remove("column-hidden"));

    // Ocultar columnas seleccionadas
    minimizedColumns.forEach((index) => {
        const cells = table.querySelectorAll(`tr > *:nth-child(${index + 1})`);
        cells.forEach((cell) => {
            cell.classList.add("column-hidden");
        });
    });

    // Mostrar/Ocultar botón de reset
    if (resetBtn) {
        resetBtn.style.display = minimizedColumns.size > 0 ? "inline-flex" : "none";
    }
}

// Listener delegado para los botones de ocultar
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".toggle-col-btn");
    if (btn) {
        const th = btn.closest("th");
        if (th) {
            const index = Array.from(th.parentNode.children).indexOf(th);
            toggleColumn(index);
        }
    }
});

// Inicializar estado al cargar
document.addEventListener("DOMContentLoaded", applyMinimizedColumns);

window.showHistory = function (cuentaId, contratoNum) {
    const modalElement = document.getElementById("historyModal");
    const modal = new bootstrap.Modal(modalElement);
    document.getElementById("historyContratoNum").textContent = contratoNum;

    const spinner = document.getElementById("historySpinner");
    const content = document.getElementById("timelineContent");
    const empty = document.getElementById("historyEmpty");

    spinner.style.display = "block";
    content.style.display = "none";
    empty.style.display = "none";
    content.innerHTML = "";

    // Ocultar tiempo total previo
    const timeBadge = document.getElementById("historyTotalTimeBadge");
    if (timeBadge) timeBadge.style.display = "none";

    modal.show();

    fetch(`/workflow/historial/${cuentaId}`)
        .then((response) => response.json())
        .then((data) => {
            spinner.style.display = "none";

            // Mostrar tiempo total siempre
            const timeBadge = document.getElementById("historyTotalTimeBadge");
            const timeSpan = document.getElementById("historyTotalTime");
            if (data.tiempo_total && timeBadge && timeSpan) {
                timeSpan.textContent = data.tiempo_total;
                timeBadge.style.display = "inline-block";
            }

            if (data.success && data.historial.length > 0) {
                content.style.display = "block";

                data.historial.forEach((h) => {
                    const dateObj = new Date(h.fecha_transicion);
                    const dateStr = dateObj.toLocaleDateString("es-ES", {
                        day: "2-digit",
                        month: "2-digit",
                    });
                    const timeStr = dateObj.toLocaleTimeString("es-ES", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: true,
                    });

                    // Determinar color de badge por tipo de estado destino
                    let badgeClass = "bg-info";
                    if (h.estado_destino?.tipo === "DEVUELTO")
                        badgeClass = "bg-danger";
                    if (
                        h.estado_destino?.tipo === "APROBADO" ||
                        h.estado_destino?.tipo === "FINAL"
                    )
                        badgeClass = "bg-success";

                    const item = document.createElement("div");
                    item.className = "timeline-item-premium";

                    item.innerHTML = `
                        <div class="item-left">
                            <div class="item-date fw-bold text-dark" style="font-size: 0.85rem;">${dateStr}</div>
                            <div class="item-time">${timeStr}</div>
                        </div>
                        <div class="item-center">
                            <div class="item-dot"></div>
                            <div class="item-line"></div>
                        </div>
                        <div class="item-right">
                            <div class="item-header">
                                <div class="item-title">${h.bloque?.nombre ?? "Bloque"}</div>
                                <span class="badge ${badgeClass}" style="font-size: 0.7rem; border-radius: 6px;">
                                    ${h.estado_destino?.nombre ?? "N/A"}
                                </span>
                            </div>
                            <div class="item-transition">
                                <span class="text-muted small">Origen:</span> 
                                <span class="fw-bold">${h.estado_origen?.nombre ?? "Inicio"}</span> 
                                <i class="fas fa-long-arrow-alt-right mx-2 text-primary opacity-50"></i> 
                                <span class="text-muted small">Destino:</span> 
                                <span class="fw-bold">${h.estado_destino?.nombre ?? "N/A"}</span>
                            </div>
                            <div class="item-meta">
                                <span><i class="fas fa-user-circle me-1 text-primary"></i> ${h.usuario_accion?.primer_nombre ?? "Sistema"}</span>
                                ${h.accion ? `<span><i class="fas fa-tag me-1 text-primary"></i> ${h.accion}</span>` : ""}
                            </div>
                            ${h.comentarios
                            ? `
                                <div class="item-comment">
                                    <i class="fas fa-quote-left me-2 opacity-25"></i>${h.comentarios}
                                </div>
                            `
                            : ""
                        }
                        </div>
                    `;
                    content.appendChild(item);
                });
            } else {
                empty.style.display = "block";
            }
        })
        .catch((err) => {
            console.error("Error fetching history:", err);
            spinner.style.display = "none";
            empty.style.display = "block";
            empty.querySelector("p").textContent =
                "Error al cargar el historial.";
        });
};

// Inicializar paginación al cargar
document.addEventListener("DOMContentLoaded", bindPagination);

window.editAccount = function (id) {
    const manualModalElement = document.getElementById("manualEntryModal");
    const manualModal = new bootstrap.Modal(manualModalElement);
    const form = document.getElementById("manualForm");
    const modalTitle = document.getElementById("manualEntryModalLabel");
    const btnSave = document.getElementById("btnSaveManual");

    // Cambiar texto del modal
    modalTitle.textContent = "Editar Registro";
    btnSave.textContent = "Actualizar Registro";

    // Cambiar URL del formulario para UPDATE
    const updateUrl = `/dashboard/actualizar/${id}`;
    form.dataset.url = updateUrl;

    // Agregar método PUT simulado si es necesario, o manejarlo en el fetch
    // Laravel soporta _method en POST, pero aquí usaremos PUT directo en fetch

    // Bloquear campos calculados (regla de negocio)
    const readOnlyFields = [
        'NUMERO DE CONTRATO',
        'PORCENTAJE DE CUENTAS',
        'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS'
    ];

    readOnlyFields.forEach(name => {
        const input = form.querySelector(`input[name="${name}"]`);
        if (input) {
            input.readOnly = true;
            input.classList.add('bg-light');
        }
    });

    // Cargar datos
    fetch(`/dashboard/editar/${id}`)
        .then((res) => res.json())
        .then((response) => {
            if (response.success) {
                const data = response.data;

                // Rellenar campos
                Object.keys(data).forEach((key) => {
                    // Buscar input por name
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key] ?? "";
                    }
                });

                manualModal.show();
            } else {
                showSnackbar(
                    "❌ Error al cargar datos: " + response.message,
                    "error",
                );
            }
        })
        .catch((err) => {
            console.error(err);
            showSnackbar("❌ Error de conexión", "error");
        });

    // Listener para cálculo automático de porcentaje
    const inputCuenta = form.querySelector('input[name="NUMERO DE CUENTA EN PROCESO DE CUENTAS"]');
    const inputPagos = form.querySelector('input[name="NUMERO DE PAGOS TOTALES"]');
    const inputPorcentaje = form.querySelector('input[name="PORCENTAJE DE CUENTAS"]');

    function calculatePercentage() {
        const cuenta = parseFloat(inputCuenta.value) || 0;
        const pagos = parseFloat(inputPagos.value) || 0;

        if (pagos > 0) {
            const porcentaje = (pagos / cuenta);
            if (inputPorcentaje) {
                inputPorcentaje.value = porcentaje;
            }
        } else {
            if (inputPorcentaje) inputPorcentaje.value = 0;
        }
    }

    if (inputCuenta) inputCuenta.addEventListener('input', calculatePercentage);
    if (inputPagos) inputPagos.addEventListener('input', calculatePercentage);

    // Resetear modal al cerrar para que sirva para "Crear Nuevo" también
    manualModalElement.addEventListener(
        "hidden.bs.modal",
        function () {
            form.reset();
            const inputCuentaManual = form.querySelector('input[name="NUMERO DE CUENTA EN PROCESO DE CUENTAS"]');
            if (inputCuentaManual) inputCuentaManual.value = 1;
            modalTitle.textContent = "Cargar Información Manualmente";
            btnSave.textContent = "Cargar Registro";
            form.dataset.url = "{{ route('dashboard.manual') }}";

            // Desbloquear campos
            const readOnlyFields = [
                'NUMERO DE CONTRATO',
                'PORCENTAJE DE CUENTAS',
                'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS'
            ];
            readOnlyFields.forEach(name => {
                const input = form.querySelector(`input[name="${name}"]`);
                if (input) {
                    input.readOnly = false;
                    input.classList.remove('bg-light');
                }
            });

            // Remover listeners para evitar duplicados (aunque al ser named functions no es crítico si se reasignan, 
            // pero es buena práctica limpiar si fuera necesario. En este caso simple, basta con que el modal se reconstruye o los inputs se limpian)
        },
        { once: true },
    );
};

window.startNextAccount = function (id, contrato, siguienteCuenta) {
    if (confirm(`¿Desea iniciar formalmente el trámite para la cuenta #${siguienteCuenta} del contrato ${contrato}?`)) {
        fetch(`/workflow/iniciar-siguiente-cuenta/${id}`, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                "Accept": "application/json"
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSnackbar("✅ " + data.message, "success");
                    fetchFilteredData(); // Recargar tabla/dashboard
                } else {
                    showSnackbar("⚠️ " + data.message, "error");
                }
            })
            .catch(err => {
                console.error(err);
                showSnackbar("❌ Error al iniciar el siguiente ciclo", "error");
            });
    }
};


