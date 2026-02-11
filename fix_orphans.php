<?php

use App\Models\EstadoWorkflow;
use App\Models\CuentaCobro;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Fixing Orphaned Accounts...\n";

// Get valid initial state
$defaultState = EstadoWorkflow::where('codigo', 'REV1_REV')->first();

if (!$defaultState) {
    die("❌ Error: Default state 'REV1_REV' not found.\n");
}

$accounts = CuentaCobro::all();
foreach ($accounts as $a) {
    $state = EstadoWorkflow::find($a->estado_actual_id);
    if (!$state) {
        echo " - Fixing Account #{$a->numero_cuenta} (ID: {$a->id}). Was {$a->estado_actual_id} -> Now {$defaultState->id} ({$defaultState->nombre})\n";
        $a->estado_actual_id = $defaultState->id;
        $a->bloque_actual_id = $defaultState->bloque_id;
        $a->save();

        // Also fix the Block State record
        \App\Models\EstadoBloqueCuenta::updateOrCreate(
            ['cuenta_cobro_id' => $a->id, 'bloque_id' => $defaultState->bloque_id],
            [
                'estado_actual_id' => $defaultState->id,
                'responsable_id' => 1,
                'fecha_ultima_actualizacion' => now(),
                'fecha_ingreso_bloque' => now()
            ]
        );
    }
}
echo "✅ Orphans fixed.\n";
