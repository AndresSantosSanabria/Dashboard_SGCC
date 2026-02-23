/**
 * Seguimiento SECOP — módulo JS
 * La lógica principal se carga vía @push('scripts') en el blade
 * (ver resources/views/seguimiento/index.blade.php)
 * Este archivo reservado para futuras utilidades reutilizables del módulo.
 */

// Exportado para uso futuro desde el blade
window.SeguimientoModule = {
    version: '2.0.0',
    ready: false,
    init() {
        this.ready = true;
        console.info('[SeguimientoModule] Loaded v' + this.version);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.SeguimientoModule.init();
});
