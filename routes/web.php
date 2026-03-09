
<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CuentaCobroController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root: if authenticated, go to dashboard; otherwise show welcome
Route::get('/', function () {
    if (Auth::check()) {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        // Prioridad: dashboard, consolidado, workflow, admin
        if ($user->puedeAccederDashboard()) {
            return redirect()->route('dashboard');
        }
        if ($user->puedeAccederConsolidado()) {
            return redirect()->route('seguimiento.index');
        }
        if ($user->puedeAccederWorkflow()) {
            return redirect()->route('workflow');
        }
        if ($user->isAdmin()) {
            return redirect()->route('configuracion.index');
        }

        // Si no tiene acceso a ninguna sección, mostrar mensaje
        return response('No tienes permisos para acceder a ninguna sección.', 403);
    }

    return view('login.login');
});

// Login form (for guests)
Route::get('/login', function () {
    return view('login.login');
})->middleware('guest')->name('login');

// Authentication actions
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Public Consultation
Route::post('/consultar-estado', [CuentaCobroController::class, 'publicConsultation'])->name('public.consultation');
Route::get('/consultar-historial/{cuenta}', [CuentaCobroController::class, 'publicHistorial'])->name('public.historial');

// Dashboard y Cuentas de Cobro (protected)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [CuentaCobroController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/importar', [CuentaCobroController::class, 'importExcel'])->name('dashboard.importar');
    Route::post('/dashboard/manual', [CuentaCobroController::class, 'storeManual'])->name('dashboard.manual');
    Route::get('/dashboard/plantilla', [CuentaCobroController::class, 'exportTemplate'])->name('dashboard.plantilla');
    Route::get('/dashboard/editar/{id}', [CuentaCobroController::class, 'edit'])->name('dashboard.editar');
    Route::put('/dashboard/actualizar/{id}', [CuentaCobroController::class, 'update'])->name('dashboard.actualizar');
    Route::delete('/dashboard/contrato/{id}', [CuentaCobroController::class, 'destroyContrato'])->name('dashboard.contrato.destroy');
    Route::get('/analitica', [\App\Http\Controllers\AnaliticaController::class, 'index'])->name('analitica');

    // Seguimiento SECOP - SIA OBSERVA
    Route::get('/seguimiento', [\App\Http\Controllers\SeguimientoController::class, 'index'])->name('seguimiento.index');
    Route::get('/seguimiento/export', [\App\Http\Controllers\SeguimientoController::class, 'export'])->name('seguimiento.export');
    Route::post('/seguimiento/status', [\App\Http\Controllers\SeguimientoController::class, 'updateStatus'])->name('seguimiento.update-status');
    Route::post('/seguimiento/store', [\App\Http\Controllers\SeguimientoController::class, 'store'])->name('seguimiento.store');
    Route::put('/seguimiento/{id}', [\App\Http\Controllers\SeguimientoController::class, 'update'])->name('seguimiento.update');
    Route::delete('/seguimiento/{contrato}', [\App\Http\Controllers\SeguimientoController::class, 'destroy'])->name('seguimiento.destroy');

    // Notificaciones
    Route::get('/notificaciones/latest', [\App\Http\Controllers\NotificationController::class, 'getLatest'])->name('notificaciones.latest');
    Route::post('/notificaciones/leer/{id}', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notificaciones.leer');
    Route::post('/notificaciones/leer-todas', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notificaciones.leer-todas');
});

// Workflow (protected)
Route::middleware('auth')->group(function () {
    Route::get('/workflow', [WorkflowController::class, 'index'])->name('workflow');
    Route::get('/workflow/estados-disponibles/{cuenta}', [WorkflowController::class, 'getEstadosDisponibles'])->name('workflow.estados');
    Route::post('/workflow/cambiar-estado/{cuenta}', [WorkflowController::class, 'cambiarEstado'])->name('workflow.cambiar-estado');
    Route::post('/workflow/asignar-responsable/{cuenta}', [WorkflowController::class, 'assignResponsible'])->name('workflow.asignar-responsable');
    Route::get('/workflow/historial/{cuenta}', [WorkflowController::class, 'getHistorial'])->name('workflow.historial');
    Route::get('/workflow/usuarios-responsables/{estadoCodigo}', [WorkflowController::class, 'getUsuariosResponsables'])->name('workflow.usuarios-responsables');
    Route::post('/workflow/iniciar-siguiente-cuenta/{cuenta}', [WorkflowController::class, 'iniciarSiguienteCuenta'])->name('workflow.iniciar-siguiente-cuenta');
});

// Configuración (admin only)
Route::middleware('auth')->prefix('configuracion')->group(function () {
    Route::get('/', [\App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::get('/crear', [\App\Http\Controllers\ConfiguracionController::class, 'create'])->name('configuracion.create');
    Route::post('/store', [\App\Http\Controllers\ConfiguracionController::class, 'store'])->name('configuracion.store');
    Route::get('/editar/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::put('/actualizar/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'update'])->name('configuracion.update');
    Route::post('/toggle-status/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'toggleStatus'])->name('configuracion.toggle-status');

    // Roles Management
    Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index'])->name('configuracion.roles.index');
    Route::get('/roles/crear', [\App\Http\Controllers\RoleController::class, 'create'])->name('configuracion.roles.create');
    Route::post('/roles/store', [\App\Http\Controllers\RoleController::class, 'store'])->name('configuracion.roles.store');
    Route::get('/roles/editar/{id}', [\App\Http\Controllers\RoleController::class, 'edit'])->name('configuracion.roles.edit');
    Route::put('/roles/actualizar/{id}', [\App\Http\Controllers\RoleController::class, 'update'])->name('configuracion.roles.update');
    Route::post('/roles/toggle-status/{id}', [\App\Http\Controllers\RoleController::class, 'toggleStatus'])->name('configuracion.roles.toggle-status');

    // Auditoría
    Route::get('/auditoria', [\App\Http\Controllers\AuditoriaController::class, 'index'])->name('configuracion.auditoria.index');
    Route::get('/auditoria/{id}', [\App\Http\Controllers\AuditoriaController::class, 'show'])->name('configuracion.auditoria.show');

    // Panel de Alertas
    Route::get('/alertas', [\App\Http\Controllers\AlertaAdminController::class, 'index'])->name('configuracion.alertas.index');
    Route::post('/alertas/config', [\App\Http\Controllers\AlertaAdminController::class, 'saveConfig'])->name('configuracion.alertas.config');
    Route::post('/alertas/festivos', [\App\Http\Controllers\AlertaAdminController::class, 'storeFestivo'])->name('configuracion.alertas.festivos.store');
    Route::post('/alertas/festivos/sync', [\App\Http\Controllers\AlertaAdminController::class, 'syncFestivos'])->name('configuracion.alertas.festivos.sync');
    Route::delete('/alertas/festivos/{id}', [\App\Http\Controllers\AlertaAdminController::class, 'destroyFestivo'])->name('configuracion.alertas.festivos.destroy');
    Route::post('/alertas/destinatarios', [\App\Http\Controllers\AlertaAdminController::class, 'storeDestinatario'])->name('configuracion.alertas.destinatarios.store');
    Route::delete('/alertas/destinatarios/{id}', [\App\Http\Controllers\AlertaAdminController::class, 'destroyDestinatario'])->name('configuracion.alertas.destinatarios.destroy');

    // Workflow States Management
    Route::get('/workflow-estados', [\App\Http\Controllers\WorkflowAdminController::class, 'index'])->name('configuracion.workflow.index');
    Route::post('/workflow-estados/store', [\App\Http\Controllers\WorkflowAdminController::class, 'store'])->name('configuracion.workflow.store');
    Route::put('/workflow-estados/actualizar/{id}', [\App\Http\Controllers\WorkflowAdminController::class, 'update'])->name('configuracion.workflow.update');
    Route::delete('/workflow-estados/eliminar/{id}', [\App\Http\Controllers\WorkflowAdminController::class, 'destroy'])->name('configuracion.workflow.destroy');
    Route::post('/workflow-estados/toggle-status/{id}', [\App\Http\Controllers\WorkflowAdminController::class, 'toggleStatus'])->name('configuracion.workflow.toggle-status');
});
