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
    {{-- GOV.CO Fixed Header (MOBILE ONLY) --}}
    <div class="gov-header d-lg-none">
        <div class="gov-container">
            <div class="gov-logo-wrap">
                <i class="bi bi-star-fill gov-star"></i>
                <span class="gov-pipe">|</span>
                <span class="gov-text">GOV.CO</span>
            </div>
            <div class="gov-lang">
                <button class="btn-lang">EN</button>
            </div>
        </div>
    </div>

    {{-- BARRA SUPERIOR ORIGINAL (DESKTOP ONLY) --}}
    <div class="barra-superior-govco d-none d-lg-flex">
        <a href="https://www.gov.co/" target="_blank" rel=noopener aria-label="Portal del Estado Colombiano - GOV.CO"></a>
        <button class="idioma-btn-barra-superior-govco" aria-label="Button to change the language of the page to English"></button>
    </div>

    <div class="login-wrapper d-lg-none">
        {{-- Blue Hero Section --}}
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <p class="hero-top-label">ACCESO CIUDADANO</p>
                <h1 class="hero-title">Gestión de cuentas<br>de cobro</h1>
                <p class="hero-subtitle">Gobernación de Cundinamarca</p>
            </div>
        </div>

        {{-- White/Gray Main Container --}}
        <div class="login-main-container">
            <div class="content-limit">
                
                {{-- Card 1: Consultation --}}
                <div class="dark-card consultation-card">
                    <p class="card-label">CONSULTAR ESTADO DE CUENTA</p>
                    <div class="search-field-wrap">
                        <i class="bi bi-file-earmark-text"></i>
                        <input type="text" id="consult-nit-mobile" class="card-input" placeholder="1234567890 — Cédula / NIT">
                    </div>
                    <button type="button" id="btn-consultar-mobile" class="card-btn">
                        <i class="bi bi-search me-2"></i>
                        <span id="btn-text-mobile">Consultar estado</span>
                        <span id="btn-spinner-mobile" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>

                    <div id="results-area-mobile" class="results-container d-none">
                        <!-- AJAX Results will appear here -->
                    </div>
                </div>

                <div class="separator-text">o inicia sesión</div>

                {{-- Card 2: Login Form --}}
                <div class="dark-card login-card">
                    <div class="login-header-mini">
                        <div class="icon-box">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="header-texts">
                            <h3>Iniciar sesión</h3>
                            <p>Ingresa tus credenciales para continuar</p>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger-custom">
                            @foreach ($errors->all() as $error)
                                <span>{{ $error }}</span>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="field-group">
                            <label>Usuario</label>
                            <div class="input-wrap">
                                <i class="bi bi-person-fill"></i>
                                <input type="text" name="user" value="{{ old('user') }}" class="card-input" id="user-mobile" placeholder="admin" required autofocus>
                            </div>
                        </div>

                        <div class="field-group">
                            <label>Contraseña</label>
                            <div class="input-wrap">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" name="password" class="card-input" id="password-mobile" placeholder="••••••••" required>
                                <button type="button" class="btn-toggle-pass" onclick="togglePassword('password-mobile')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="card-btn btn-submit">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Ingresar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ORIGINAL DESKTOP DESIGN (REFINED) --}}
    <div class="login-full-screen d-none d-lg-flex">
        <div class="premium-card">
            <!-- Izquierda: Bienvenida Institucional (CIUDADANOS) -->
            <div class="premium-left">
                <div class="premium-left-card">
                    <p class="hero-top-label mb-2">CIUDADANOS</p>
                    <h2>Conoce el estado<br>de tu cuenta</h2>
                    <p class="mb-4">Ingresa tu cédula y valida en qué estado se encuentra tu cuenta de manera rápida y segura.</p>

                    <div class="search-field-prem">
                        <label>NÚMERO DE CÉDULA / NIT</label>
                        <div class="search-input-wrapper">
                            <i class="bi bi-file-earmark-text"></i>
                            <input type="text" id="consult-nit" class="search-input-prem" placeholder="1234567890">
                        </div>
                    </div>

                    <button type="button" id="btn-consultar" class="btn-search-prem">
                        <i class="bi bi-search me-2"></i>
                        <span id="btn-text">Consultar estado</span>
                        <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>

                    <div id="results-area" class="results-container d-none">
                        <!-- AJAX Results will appear here -->
                    </div>
                </div>
            </div>

            <!-- Derecha: Formulario (INICIAR SESIÓN) -->
            <div class="premium-right">
                <div class="white-login-box">
                    <div class="login-right-header">
                        <div class="icon-box-blue">
                            <i class="bi bi-house-door"></i>
                        </div>
                        <div class="header-text-group">
                            <span class="gob-title">Gobernación de Cundinamarca</span>
                            <span class="sys-subtitle">Sistema de gestión de cuentas de cobro</span>
                        </div>
                    </div>

                    <hr class="header-sep">

                    <h1 class="mt-4">Iniciar sesión</h1>
                    <p class="subtitle mb-4">Ingresa tus credenciales para continuar</p>

                    @if ($errors->any())
                        <div class="alert alert-danger-custom mb-3">
                            @foreach ($errors->all() as $error)
                                <span>{{ $error }}</span>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="form-group-prem">
                            <label>Usuario</label>
                            <div class="input-prem-wrapper">
                                <i class="bi bi-person"></i>
                                <input type="text" name="user" value="{{ old('user') }}" class="input-prem" id="user" placeholder="admin" required autofocus>
                            </div>
                        </div>

                        <div class="form-group-prem">
                            <label>Contraseña</label>
                            <div class="input-prem-wrapper">
                                <i class="bi bi-lock"></i>
                                <input type="password" name="password" class="input-prem" id="password" placeholder="••••••••" required>
                                <button type="button" class="btn-toggle-pass-desktop" onclick="togglePassword('password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label text-muted small fw-bold" for="remember">Recordar sesión</label>
                            </div>
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
        function renderAccountCard(account, contratoNum) {
            const statusClass = account.finalizada ? 'bg-success' : 'bg-primary';
            const progress = Number(account.progreso ?? 0);
            const progressText = Number.isFinite(progress) ? `${Math.round(progress)}%` : '0%';

            return `
                <button type="button"
                    class="text-start w-100 border-0 p-0 bg-transparent"
                    onclick="showHistory(${account.id}, '${(contratoNum || '').replace(/'/g, "\\'")}', '${(account.id_tramite ?? account.numero_cuenta ?? account.id).toString().replace(/'/g, "\\'")}')">
                    <div class="result-item" style="background:#fff;border-radius:14px;padding:14px 16px;border:1px solid rgba(15,23,42,.08);box-shadow:0 8px 18px rgba(15,23,42,.06);">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-bold text-dark">Cuenta ${account.numero_cuenta ?? account.id}</div>
                                <div class="text-muted small">ID trámite: ${account.id_tramite ?? account.id}</div>
                            </div>
                            <span class="badge ${statusClass}" style="border-radius:999px;">${account.estado_actual ?? 'En trámite'}</span>
                        </div>
                        <div class="mt-2 small text-muted">Inicio: ${account.fecha_inicio ?? 'N/A'}</div>
                        <div class="mt-1 small text-muted">Actualización: ${account.ultima_actualizacion ?? 'N/A'}</div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Avance</span>
                                <span class="fw-bold">${progressText}</span>
                            </div>
                            <div class="progress" style="height:8px;border-radius:999px;">
                                <div class="progress-bar" role="progressbar" style="width:${Math.max(0, Math.min(progress, 100))}%"></div>
                            </div>
                        </div>
                    </div>
                </button>
            `;
        }

        function renderConsultationResults(data, resultsArea) {
            if (!data.cuentas || data.cuentas.length === 0) {
                resultsArea.innerHTML = `<div class="text-warning small">No se encontraron cuentas asociadas.</div>`;
                return;
            }

            const selectorTitle = data.requires_selection
                ? 'Selecciona una cuenta para ver su historial'
                : 'Cuenta encontrada';

            const cards = data.cuentas.map(account => renderAccountCard(account, data.numero_contrato)).join('');

            resultsArea.innerHTML = `
                <div class="mb-3">
                    <div class="result-item">
                        <span class="result-label">Contratista</span>
                        <span class="result-value">${data.contratista}</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Contrato</span>
                        <span class="result-value">${data.numero_contrato}</span>
                    </div>
                </div>
                <div class="mb-2 fw-bold text-dark">${selectorTitle}</div>
                <div class="d-grid gap-3">
                    ${cards}
                </div>
            `;
        }

        function performConsultation(nitId, textId, spinnerId, resultsId) {
            const nit = document.getElementById(nitId).value;
            const btnText = document.getElementById(textId);
            const btnSpinner = document.getElementById(spinnerId);
            const resultsArea = document.getElementById(resultsId);

            if (!nit) {
                alert('Por favor ingresa un NIT o Cédula');
                return;
            }

            // UI Loading state
            const originalText = btnText.textContent;
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
                    btnText.textContent = originalText;
                    btnSpinner.classList.add('d-none');

                    if (!response.ok || data.error) {
                        resultsArea.innerHTML =
                            `<div class="text-warning small">${data.error || 'No se encontró la información'}</div>`;
                    } else {
                        renderConsultationResults(data, resultsArea);
                    }
                    resultsArea.classList.remove('d-none');
                })
                .catch(error => {
                    btnText.textContent = originalText;
                    btnSpinner.classList.add('d-none');
                    resultsArea.innerHTML =
                        `<div class="text-danger small">Error de conexión con el servidor.</div>`;
                    resultsArea.classList.remove('d-none');
                });
        }

        document.getElementById('btn-consultar').addEventListener('click', () => {
            performConsultation('consult-nit', 'btn-text', 'btn-spinner', 'results-area');
        });

        if (document.getElementById('btn-consultar-mobile')) {
            document.getElementById('btn-consultar-mobile').addEventListener('click', () => {
                performConsultation('consult-nit-mobile', 'btn-text-mobile', 'btn-spinner-mobile', 'results-area-mobile');
            });
        }

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

        window.showHistory = function(cuentaId, contratoNum, cuentaNum = '') {
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
            const cuentaLabel = document.getElementById("historyCuentaNum");
            if (cuentaLabel) cuentaLabel.textContent = cuentaNum || `#${cuentaId}`;
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

        window.togglePassword = function(inputId) {
            const passInput = document.getElementById(inputId);
            const btn = event.currentTarget;
            const icon = btn.querySelector('i');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        };
    </script>
@endsection
