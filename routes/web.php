
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
    return view('login');
});

// Login form (for guests)
Route::get('/login', function () {
    return view('login');
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
});

// Workflow (protected)
Route::get('/workflow', [WorkflowController::class, 'index'])->middleware('auth')->name('workflow');
