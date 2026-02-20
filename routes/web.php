
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\CuentaCobroController;
use App\Http\Controllers\BackupController;
use App\Models\CuentaCobro;

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
            return redirect()->route('consolidado'); // Cambia a la ruta correcta si existe
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
    Route::get('/analitica', [\App\Http\Controllers\AnaliticaController::class, 'index'])->name('analitica');

    // Seguimiento SECOP - SIA OBSERVA
    Route::get('/seguimiento', [\App\Http\Controllers\SeguimientoController::class, 'index'])->name('seguimiento.index');
    Route::post('/seguimiento/status', [\App\Http\Controllers\SeguimientoController::class, 'updateStatus'])->name('seguimiento.update-status');
    Route::post('/seguimiento/store', [\App\Http\Controllers\SeguimientoController::class, 'store'])->name('seguimiento.store');
    Route::put('/seguimiento/{id}', [\App\Http\Controllers\SeguimientoController::class, 'update'])->name('seguimiento.update');
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
});

Route::get('/debug-permisos', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    if (!$user) return 'No logged in user';

    return [
        'user_id' => $user->id,
        'email' => $user->email,
        'rol' => $user->rol,
        'permisos_explicit' => $user->permisos,
        'is_admin_check' => $user->isAdmin(),
        'has_dashboard' => $user->tienePermiso('acceder_dashboard'),
        'has_consolidado' => $user->tienePermiso('acceder_consolidado'),
    ];
});
