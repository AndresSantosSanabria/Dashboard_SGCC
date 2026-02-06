<?php

use Illuminate\Support\Facades\Route;


Route::get('/', \App\Livewire\Login::class)->name('login')->middleware('guest');

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard')->middleware('auth');
Route::get('/workflow', \App\Livewire\workflow::class)->name('workflow')->middleware('auth');
Route::get('/Analitica', \App\Livewire\Analitica::class)->name('Analitica')->middleware('auth');
Route::get('/reportes/contratos', \App\Livewire\ContratosReport::class)->name('reportes.contratos')->middleware('auth');
Route::get('/reportes/contratos/export', function () {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ContratosReportExport, 'contratos_report.xlsx');
})->name('reportes.contratos.export')->middleware('auth');

Route::get('/plantilla/descargar', function () {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PlantillaCuentasExport, 'plantilla_cuentas.xlsx');
})->name('plantilla.descargar')->middleware('auth');
