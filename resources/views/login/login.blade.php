@extends('layouts.guest')

@section('title', 'Iniciar Sesión - SGCC')

@push('styles')
    @vite(['resources/views/login/login.css'])
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Fix for modal z-index over the premium login screen */
        #historyModal {
            z-index: 12000 !important;
        }

        .modal-backdrop {
            z-index: 11999 !important;
        }
    </style>
@endpush

@section('page-content')
    <div class="barra-superior-govco">
        <a href="https://www.gov.co/" target="_blank" rel=noopener aria-label="Portal del Estado Colombiano - GOV.CO"></a>
        <button class="idioma-btn-barra-superior-govco" aria-label="Button to change the language of the page to English">
        </button>
    </div>

    <div class="login-full-screen">
        <div class="premium-card">
            <!-- Izquierda: Bienvenida Institucional -->
            <div class="premium-left">
                <div class="premium-left-card" id="consultation-card">
                    <h2>Conoce el estado de tu cuenta</h2>
                    <p>Ingresa tu cédula y valida en qué estado se encuentra tu cuenta de manera rápida y segura.</p>

                    <div class="search-field-prem">
                        <label>Número de Cédula / NIT</label>
                        <input type="text" id="consult-nit" class="search-input-prem" placeholder="Ej: 1234567890">
                    </div>

                    <button type="button" id="btn-consultar" class="btn-search-prem">
                        <span id="btn-text">Consultar Estado</span>
                        <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>

                    <div id="results-area" class="results-container d-none">
                        <!-- AJAX Results will appear here -->
                    </div>
                </div>
            </div>

            <!-- Derecha: Formulario White Card -->
            <div class="premium-right">
                <div class="white-login-box">
                    <div class="premium-logo">
                        <img src="{{ asset('assets/img/logo-gobernacion.png') }}" alt="Logo Gobernación"
                            style="width: 100%;">
                    </div>
                    <h1>Iniciar Sesión</h1>
                    <p class="subtitle">Ingresa tus credenciales para continuar</p>

                    @if ($errors->any())
                        <div class="alert alert-danger"
                            style="font-size: 0.75rem; border-radius: 10px; margin-bottom: 20px;">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="form-group-prem">
                            <label for="user">Usuario</label>
                            <input type="text" name="user" value="{{ old('user') }}" class="input-prem"
                                id="user" placeholder="Ej: admin" required autofocus>
                        </div>

                        <div class="form-group-prem">
                            <label for="password">Contraseña</label>
                            <input type="password" name="password" class="input-prem" id="password" placeholder="••••••••"
                                required>
                        </div>

                        <button type="submit" class="btn-prem-login">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal must be OUTSIDE the premium-card (which has overflow:hidden) and outside login-full-screen --}}
    @include('dashboard.componentes.history_modal')

    <script>
        document.getElementById('btn-consultar').addEventListener('click', function() {
            const nit = document.getElementById('consult-nit').value;
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const resultsArea = document.getElementById('results-area');

            if (!nit) {
                alert('Por favor ingresa un NIT o Cédula');
                return;
            }

            // UI Loading state
            btnText.textContent = 'Consultando...';
            btnSpinner.classList.remove('d-none');
            resultsArea.classList.add('d-none');

            window.apiFetch("{{ route('public.consultation') }}", {
                    method: 'POST',
                    body: JSON.stringify({
                        nit: nit
                    })
                })
                .then(async response => {
                    const data = await response.json();
                    btnText.textContent = 'Consultar Estado';
                    btnSpinner.classList.add('d-none');

                    if (!response.ok || data.error) {
                        resultsArea.innerHTML =
                            `<div class="text-warning small">${data.error || 'No se encontró la información'}</div>`;
                    } else {
                        resultsArea.innerHTML = `
                        <div class="result-item">
                            <span class="result-label">Contratista</span>
                            <span class="result-value">${data.contratista}</span>
                        </div>
                        <div class="result-item" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;">
                            <span class="result-label" style="color: #fbfbfbff; opacity: 1;">Estado Actual</span>
                            <span class="status-badge" style="background: #2563eb;">${data.estado}</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">Bloque Actual</span>
                            <span class="result-value">${data.bloque}</span>
                        </div>
                        ${data.responsable ? `
                        <div class="result-item" style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 10px; margin-top: 6px; background: rgba(96,165,250,0.12); border-radius: 8px; padding: 10px 14px;">
                            <span class="result-label" style="color:#93c5fd; font-size:0.72rem; letter-spacing:0.08em;">FUE ASIGNADO A</span>
                            <span class="result-value" style="display:flex;align-items:center;gap:8px;font-size:1rem;font-weight:700;color:#fff;">
                                <i class="fas fa-user-check" style="color:#60a5fa; font-size:1.1rem;"></i>
                                ${data.responsable}
                            </span>
                        </div>
                        ` : ''}
                        <div class="result-item">
                            <span class="result-label">Última Actualización</span>
                            <span class="result-value" style="font-size: 0.8rem; opacity: 0.7;">${data.ultima_actualizacion}</span>
                        </div>
                        <div class="result-item mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                            <button type="button" class="btn-search-prem w-100" onclick="showHistory(${data.id}, 'Tramite Actual')" style="font-size: 0.9rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2); position: relative; z-index: 5;">
                                <i class="fas fa-history me-2"></i> Ver Historial Detallado
                            </button>
                        </div>
                    `;
                    }
                    resultsArea.classList.remove('d-none');
                })
                .catch(error => {
                    btnText.textContent = 'Consultar Estado';
                    btnSpinner.classList.add('d-none');
                    resultsArea.innerHTML =
                        `<div class="text-danger small">Error de conexión con el servidor.</div>`;
                    resultsArea.classList.remove('d-none');
                });
        });

        let historyModalInstance = null;

        // Initialize after everything is loaded (Professional Bootstrap handling)
        window.addEventListener('load', function() {
            const modalElement = document.getElementById("historyModal");
            if (modalElement) {
                // Ensure singleton instance
                const bootstrapObj = window.bootstrap || bootstrap;
                historyModalInstance = new bootstrapObj.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true
                });

                // Cleanup when modal is hidden to prevent locking background (Crucial for UX)
                modalElement.addEventListener('hidden.bs.modal', function() {
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });
            }
        });

        window.showHistory = function(cuentaId, contratoNum) {
            if (!historyModalInstance) {
                const modalElement = document.getElementById("historyModal");
                if (modalElement) {
                    const bootstrapObj = window.bootstrap || bootstrap;
                    historyModalInstance = new bootstrapObj.Modal(modalElement);
                } else {
                    return;
                }
            }

            document.getElementById("historyContratoNum").textContent = contratoNum;
            const spinner = document.getElementById("historySpinner");
            const content = document.getElementById("timelineContent");
            const empty = document.getElementById("historyEmpty");

            spinner.style.display = "block";
            content.style.display = "none";
            empty.style.display = "none";
            content.innerHTML = "";
            const timeBadge = document.getElementById("historyTotalTimeBadge");
            if (timeBadge) timeBadge.style.display = "none";

            historyModalInstance.show();

            window.apiFetch(`{{ route('public.historial', ['cuenta' => ':id']) }}`.replace(':id', cuentaId))
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = "none";
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

                            let badgeClass = "bg-info";
                            const estadoDestino = h.estado_destino || {};
                            if (estadoDestino.tipo === "DEVUELTO") badgeClass = "bg-danger";
                            if (estadoDestino.tipo === "APROBADO" || estadoDestino.tipo === "FINAL")
                                badgeClass = "bg-success";

                            // Detectar si es una transición automática o una asignación
                            const esAutomatismo = h.comentarios && h.comentarios.startsWith("Automatismo:");
                            const esAsignacion = h.comentarios && (h.comentarios.startsWith("Asignado a:") || h.comentarios.startsWith("Fue asignado a:") || h.comentarios.startsWith("Devuelto a:"));

                            // Nombre completo del usuario que realizó la acción
                            let nombreUsuario = "";
                            if (h.usuario_accion) {
                                const pNombre = h.usuario_accion.primer_nombre ?? "";
                                const pApellido = h.usuario_accion.primer_apellido ?? "";
                                nombreUsuario = (pNombre + " " + pApellido).trim() || "Sin nombre";
                            }

                            const usuarioHTML = `
                                <span><i class="fas fa-user-circle me-1 text-primary"></i> ${nombreUsuario || "Sin registro"}</span>
                                ${esAutomatismo ? `<span style="color:#6b7280;font-style:italic;font-size:0.75rem;margin-left:8px;"><i class="fas fa-robot me-1"></i>(Auto)</span>` : ""}
                            `;

                            // Comentario con estilo especial si es asignación o devolución
                            let comentarioHTML = "";
                            if (esAsignacion) {
                                const esDevolucion = h.comentarios.startsWith("Devuelto a:");
                                comentarioHTML = `
                                    <div class="item-comment" style="background:${esDevolucion ? '#fef2f2' : '#eff6ff'}; border-left:3px solid ${esDevolucion ? '#ef4444' : '#2563eb'}; border-radius:6px; padding:8px 12px; margin-top:8px;">
                                        <i class="fas ${esDevolucion ? 'fa-undo' : 'fa-user-check'} me-2" style="color:${esDevolucion ? '#ef4444' : '#2563eb'};"></i>
                                        <strong style="color:${esDevolucion ? '#991b1b' : '#1d4ed8'};">${h.comentarios}</strong>
                                    </div>`;
                            } else if (h.comentarios && !esAutomatismo) {
                                comentarioHTML = `<div class="item-comment"><i class="fas fa-quote-left me-2 opacity-25"></i>${h.comentarios}</div>`;
                            }

                            const item = document.createElement("div");
                            item.className = "timeline-item-premium";
                            item.innerHTML = `
                                <div class="item-left">
                                    <div class="item-date fw-bold text-dark" style="font-size: 0.85rem;">${dateStr}</div>
                                    <div class="item-time">${timeStr}</div>
                                </div>
                                <div class="item-center">
                                    <div class="item-dot" style="${esAsignacion ? 'background:#2563eb;border-color:#bfdbfe;' : ''}"></div>
                                    <div class="item-line"></div>
                                </div>
                                <div class="item-right" style="${esAsignacion ? 'background:#f0f7ff;border:1px solid #bfdbfe;' : ''}">
                                    <div class="item-header">
                                        <div class="item-title">${esAsignacion ? (h.comentarios.startsWith("Devuelto a:") ? '↩️ Devolución' : '👤 Asignación') : (h.bloque?.nombre ?? "Bloque")}</div>
                                        ${!esAsignacion ? `<span class="badge ${badgeClass}" style="font-size: 0.7rem; border-radius: 6px;">${estadoDestino.nombre ?? "N/A"}</span>` : ''}
                                    </div>
                                    ${!esAsignacion ? `
                                    <div class="item-transition">
                                        <span class="text-muted small">Origen:</span> 
                                        <span class="fw-bold">${h.estado_origen?.nombre ?? "Inicio"}</span> 
                                        <i class="fas fa-long-arrow-alt-right mx-2 text-primary opacity-50"></i> 
                                        <span class="text-muted small">Destino:</span> 
                                        <span class="fw-bold">${estadoDestino.nombre ?? "N/A"}</span>
                                    </div>` : ''}
                                    <div class="item-meta">
                                        ${usuarioHTML}
                                        ${h.accion && !esAutomatismo && !esAsignacion ? `<span><i class="fas fa-tag me-1 text-primary"></i> ${h.accion}</span>` : ""}
                                    </div>
                                    ${comentarioHTML}
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
                    empty.querySelector("p").textContent = "Error al cargar el historial.";
                });
        };
    </script>
@endsection
