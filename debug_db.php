<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BloqueWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

echo "--- DATABASE DEBUG ---\n";

try {
    $dbName = DB::connection()->getDatabaseName();
    echo "Database: $dbName\n";

    $rad = BloqueWorkflow::where('codigo', 'RAD')->first();
    if ($rad) {
        echo "Bloque RAD: Found (ID: {$rad->id})\n";
        $estadoIni = $rad->estadoInicial;
        if ($estadoIni) {
            echo "Estado Inicial RAD: Found (ID: {$estadoIni->id})\n";
        } else {
            echo "Estado Inicial RAD: MISSING\n";
        }
    } else {
        echo "Bloque RAD: MISSING\n";
    }

    echo "Total Usuarios: " . Usuario::count() . "\n";
    $user = Usuario::first();
    if ($user) {
        echo "First User: " . $user->user . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
