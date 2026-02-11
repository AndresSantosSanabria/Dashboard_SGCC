<?php

use App\Models\EstadoWorkflow;
use App\Models\CuentaCobro;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Diagnostic:\n";
$states = EstadoWorkflow::all();
echo "Total States: " . $states->count() . "\n";
foreach ($states as $s) {
    echo " - [{$s->id}] {$s->nombre} (Code: {$s->codigo}, Block: {$s->bloque_id})\n";
}

echo "\n📊 Accounts:\n";
$accounts = CuentaCobro::all();
foreach ($accounts as $a) {
    $state = EstadoWorkflow::find($a->estado_actual_id);
    echo " - Account #{$a->numero_cuenta} (ID: {$a->id}) -> State ID: {$a->estado_actual_id} (" . ($state ? $state->nombre : '❌ ORPHANED') . ")\n";
}
