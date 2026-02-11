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
            const formData = new FormData(manualForm);
            btnSaveManual.disabled = true;
            btnSaveManual.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status"></span> Cargando...';

            fetch(manualForm.dataset.url || "{{ route('dashboard.manual') }}", {
                method: "POST",
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
                        btnSaveManual.textContent = "Cargar Registro";
                    }
                })
                .catch((error) => {
                    showSnackbar("❌ Error: " + error.message, "error");
                    btnSaveManual.disabled = false;
                    btnSaveManual.textContent = "Cargar Registro";
                });
        });
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
        })
        .catch((error) => {
            if (error.name === "AbortError") return;
            console.error("Error fetching filtered data:", error);
            if (tableSpinner) tableSpinner.style.display = "none";
            tableContainer.style.opacity = "1";
            tableContainer.style.pointerEvents = "auto";
        });
};

const debouncedSearch = debounce(fetchFilteredData, 250);

// Prevenir envío tradicional del formulario
if (filtersForm) {
    filtersForm.addEventListener("submit", function (e) {
        e.preventDefault();
        fetchFilteredData();
    });
}

// Delegación de eventos para los inputs de filtro
document.addEventListener("input", function (e) {
    if (e.target.classList.contains("filter-input")) {
        debouncedSearch();
    }
});

document.addEventListener("change", function (e) {
    if (
        e.target.classList.contains("filter-input") &&
        e.target.tagName !== "INPUT"
    ) {
        fetchFilteredData();
    }
});

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

    modal.show();

    fetch(`/workflow/historial/${cuentaId}`)
        .then((response) => response.json())
        .then((data) => {
            spinner.style.display = "none";

            if (data.success && data.historial.length > 0) {
                content.style.display = "block";

                data.historial.forEach((h) => {
                    const date = new Date(h.fecha_transicion).toLocaleString(
                        "es-ES",
                        {
                            day: "2-digit",
                            month: "2-digit",
                            year: "numeric",
                            hour: "2-digit",
                            minute: "2-digit",
                            hour12: true,
                        },
                    );

                    const item = document.createElement("div");
                    item.className = "timeline-item";

                    // Determinar color de badge por tipo de estado destino
                    let badgeClass = "bg-info";
                    if (h.estado_destino?.tipo === "DEVUELTO")
                        badgeClass = "bg-danger";
                    if (
                        h.estado_destino?.tipo === "APROBADO" ||
                        h.estado_destino?.tipo === "FINAL"
                    )
                        badgeClass = "bg-success";

                    item.innerHTML = `
                        <div class="timeline-marker-wrapper">
                            <div class="timeline-marker bg-primary">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="timeline-line"></div>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <div class="timeline-title">${h.bloque?.nombre ?? "Bloque"}</div>
                                <div class="timeline-date font-weight-bold">${date}</div>
                            </div>
                            <div class="timeline-transition">
                                <strong>${h.estado_origen?.nombre ?? "Inicio"}</strong> 
                                <i class="fas fa-arrow-right mx-2 text-muted" style="font-size: 0.7rem;"></i> 
                                <span class="badge ${badgeClass}">${h.estado_destino?.nombre ?? "N/A"}</span>
                            </div>
                            <div class="timeline-meta">
                                <span><i class="fas fa-user me-1"></i> ${h.usuario_accion?.primer_nombre ?? "Sistema"}</span>
                                ${h.accion ? `<span><i class="fas fa-tag me-1"></i> ${h.accion}</span>` : ""}
                            </div>
                            ${
                                h.comentarios
                                    ? `
                                <div class="timeline-comment">
                                    "${h.comentarios}"
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

// Modificar el listener de btnSaveManual para soportar PUT
const btnSaveManual = document.getElementById("btnSaveManual");
if (btnSaveManual) {
    // Reemplazar el listener anterior (clonando el nodo para eliminar listeners previos es una opción,
    // pero mejor ajustamos la lógica del fetch existente para usar el dataset.url y method dinámico)

    // NOTA: El listener original ya usa `manualForm.dataset.url`, así que solo falta manejar el método.
    // Vamos a sobreescribir el listener clonando el botón para limpiar el anterior.
    const newBtn = btnSaveManual.cloneNode(true);
    btnSaveManual.parentNode.replaceChild(newBtn, btnSaveManual);

    newBtn.addEventListener("click", function () {
        const manualForm = document.getElementById("manualForm");
        const formData = new FormData(manualForm);

        // Determinar si es Update o Create
        const isUpdate = manualForm.dataset.url.includes("actualizar");
        const method = isUpdate ? "POST" : "POST"; // Usaremos POST con _method si es update, o PUT directo

        if (isUpdate) {
            formData.append("_method", "PUT");
        }

        newBtn.disabled = true;
        newBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...';

        fetch(manualForm.dataset.url, {
            method: "POST", // Siempre POST para FormData, con _method interno
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document.querySelector('input[name="_token"]')
                    .value,
                Accept: "application/json",
            },
        })
            .then((response) =>
                response
                    .json()
                    .catch(() => ({
                        success: false,
                        message: "Error en respuesta del servidor",
                    })),
            )
            .then((data) => {
                newBtn.disabled = false;
                newBtn.textContent = isUpdate
                    ? "Actualizar Registro"
                    : "Cargar Registro";

                if (data.success) {
                    showSnackbar("✅ " + data.message, "success");
                    const modalEl = document.getElementById("manualEntryModal");
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showSnackbar("⚠️ " + data.message, "error");
                }
            })
            .catch((error) => {
                newBtn.disabled = false;
                newBtn.textContent = isUpdate
                    ? "Actualizar Registro"
                    : "Cargar Registro";
                showSnackbar("❌ Error: " + error.message, "error");
            });
    });
}
