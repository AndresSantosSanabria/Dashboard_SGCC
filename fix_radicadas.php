<?php

use App\Models\CuentaCobro;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Backfilling 'Facturas Radicadas' based on Current Account Number...\n";

// Logic: If current bill is #3, then #1 and #2 were finished. So Radicadas = 2.
// Radicadas = max(0, numero_cuenta - 1)

$accounts = CuentaCobro::all();
foreach ($accounts as $cuenta) {
    if ($cuenta->numero_cuenta > 0) {
        $expectedRadicadas = max(0, $cuenta->numero_cuenta - 1);

        if (($cuenta->numero_facturas_radicadas ?? 0) != $expectedRadicadas) {
            echo " - Account #{$cuenta->id} (Count {$cuenta->numero_cuenta}): Radicadas {$cuenta->numero_facturas_radicadas} -> {$expectedRadicadas}\n";
            $cuenta->numero_facturas_radicadas = $expectedRadicadas;
            $cuenta->save();
        }
    }
}
echo "✅ Backfill complete.\n";
