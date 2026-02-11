<?php

use App\Models\CuentaCobro;
use App\Models\EstadoWorkflow;
use App\Models\EstadoBloqueCuenta;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testCleanup()
{
    echo "🔄 Testing State Cleanup on Cycle...\n";

    $cuenta = CuentaCobro::latest()->first();
    if (!$cuenta) {
        echo "❌ No account found.\n";
        return;
    }

    // Setup: Account at end of cycle
    $cuenta->numero_pagos_totales = 20;
    $cuenta->numero_cuenta = 1;
    $cuenta->numero_facturas_radicadas = 0;

    // Create some fake history using valid state IDs
    $validStateIds = EstadoWorkflow::take(3)->pluck('id');
    if ($validStateIds->count() >= 3) {
        EstadoBloqueCuenta::updateOrCreate(['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => 1], ['estado_actual_id' => $validStateIds[0], 'responsable_id' => 1, 'fecha_ingreso_bloque' => now()]);
        EstadoBloqueCuenta::updateOrCreate(['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => 2], ['estado_actual_id' => $validStateIds[1], 'responsable_id' => 1, 'fecha_ingreso_bloque' => now()]);
        EstadoBloqueCuenta::updateOrCreate(['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => 3], ['estado_actual_id' => $validStateIds[2], 'responsable_id' => 1, 'fecha_ingreso_bloque' => now()]);
    }

    // Set to Block 6 Por Confirmar
    $estadoPorConfirmar = EstadoWorkflow::where('codigo', 'FIN_PEND')->first();
    $cuenta->estado_actual_id = $estadoPorConfirmar->id;
    $cuenta->bloque_actual_id = $estadoPorConfirmar->bloque_id;
    $cuenta->save();

    $countBefore = EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)->count();
    echo "Records before cycle: {$countBefore}\n";

    // Trigger Cycle
    echo "Wait confirm...\n";
    $estadoFinalizada = EstadoWorkflow::where('codigo', 'FIN_OK')->first();
    triggerTransition($cuenta, $estadoFinalizada->id);

    $countAfter = EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)->count();
    $cuenta->refresh();

    echo "Records after cycle: {$countAfter}\n";
    echo "New State: {$cuenta->estadoActual->nombre}\n";

    // Should have only 1 record (for the new Block 1)
    if ($countAfter == 1 && $cuenta->bloque_actual_id == 1) {
        echo " ✅ SUCCESS: Cleanup executed. Old records deleted.\n";
    } else {
        echo " ❌ Fail: Records not deleted or wrong count. Found: {$countAfter}\n";
    }
}

function triggerTransition($cuenta, $estadoDestinoId)
{
    $controller = new \App\Http\Controllers\WorkflowController();
    $request = new \Illuminate\Http\Request([
        'estado_destino_id' => $estadoDestinoId,
        'comentario' => 'Testing Cleanup'
    ]);
    try {
        $controller->cambiarEstado($request, $cuenta->id);
    } catch (\Exception $e) {
        echo "Ex: " . $e->getMessage() . "\n";
    }
}

testCleanup();
