<div class="dashboard-container" x-data>
    <!-- Sidebar Component -->
    <livewire:sidebar />

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-dashboard">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem; font-size: 1.5rem;">Sistema de Consulta de
                Cuentas
            </h2>

            <button type="button" class="excel-btn" @click="$dispatch('open-modal-import')">
                <i class="fas fa-file-excel"></i> Cargue masivo
            </button>

            <a href="{{ route('plantilla.descargar') }}" class="classic-btn"
                style="text-decoration: none; display: inline-flex; justify-content: center; align-items: center;">
                Plantilla
            </a>

            <button type="button" class="classic-btn">
                Filtros
            </button>

            <button type="button" class="create-btn">
                Nuevo registro
            </button>
        </div>



        <div
            style="background-color: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="color: var(--text-gray);">Has iniciado sesión correctamente. Aquí estará el contenido principal.
            </p>
        </div>
        <div>
                <livewire:contratos-report />
            </div>
           
    </main>

    <livewire:cuentas.importador />

</div>
