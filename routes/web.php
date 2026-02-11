
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\CuentaCobroController;
use App\Models\CuentaCobro;

// Root: if authenticated, go to dashboard; otherwise show welcome
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
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

// Dashboard y Cuentas de Cobro (protected)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [CuentaCobroController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/importar', [CuentaCobroController::class, 'importExcel'])->name('dashboard.importar');
    Route::post('/dashboard/manual', [CuentaCobroController::class, 'storeManual'])->name('dashboard.manual');
    Route::get('/dashboard/plantilla', [CuentaCobroController::class, 'exportTemplate'])->name('dashboard.plantilla');
    Route::get('/dashboard/editar/{id}', [CuentaCobroController::class, 'edit'])->name('dashboard.editar');
    Route::put('/dashboard/actualizar/{id}', [CuentaCobroController::class, 'update'])->name('dashboard.actualizar');
});

// Workflow (protected)
Route::middleware('auth')->group(function () {
    Route::get('/workflow', [WorkflowController::class, 'index'])->name('workflow');
    Route::get('/workflow/estados-disponibles/{cuenta}', [WorkflowController::class, 'getEstadosDisponibles'])->name('workflow.estados');
    Route::post('/workflow/cambiar-estado/{cuenta}', [WorkflowController::class, 'cambiarEstado'])->name('workflow.cambiar-estado');
    Route::post('/workflow/asignar-responsable/{cuenta}', [WorkflowController::class, 'assignResponsible'])->name('workflow.asignar-responsable');
    Route::get('/workflow/historial/{cuenta}', [WorkflowController::class, 'getHistorial'])->name('workflow.historial');
    Route::get('/workflow/usuarios-responsables/{estadoCodigo}', [WorkflowController::class, 'getUsuariosResponsables'])->name('workflow.usuarios-responsables');
});

// Configuración (admin only)
Route::middleware('auth')->prefix('configuracion')->group(function () {
    Route::get('/', [\App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::get('/crear', [\App\Http\Controllers\ConfiguracionController::class, 'create'])->name('configuracion.create');
    Route::post('/store', [\App\Http\Controllers\ConfiguracionController::class, 'store'])->name('configuracion.store');
    Route::get('/editar/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::put('/actualizar/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'update'])->name('configuracion.update');
    Route::post('/toggle-status/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'toggleStatus'])->name('configuracion.toggle-status');
});
