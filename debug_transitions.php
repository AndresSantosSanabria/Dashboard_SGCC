<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EstadoWorkflow;
use App\Models\TransicionPermitida;

echo "Check Block 1 (REV1_REV):\n";
$b1 = EstadoWorkflow::where('codigo', 'REV1_REV')->first();
if ($b1) {
    foreach (TransicionPermitida::where('estado_origen_id', $b1->id)->with('estadoDestino')->get() as $t) {
        echo "- To: {$t->estadoDestino->nombre} ({$t->estadoDestino->codigo})\n";
    }
}

echo "\nCheck Block 2 (SAP_ESP):\n";
$b2 = EstadoWorkflow::where('codigo', 'SAP_ESP')->first();
if ($b2) {
    foreach (TransicionPermitida::where('estado_origen_id', $b2->id)->with('estadoDestino')->get() as $t) {
        echo "- To: {$t->estadoDestino->nombre} ({$t->estadoDestino->codigo}) - Action: {$t->accion}\n";
    }
}
