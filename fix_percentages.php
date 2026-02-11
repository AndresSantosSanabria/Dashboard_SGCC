<?php

use App\Models\CuentaCobro;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Recalculating Percentages for All Accounts...\n";

$accounts = CuentaCobro::all();
foreach ($accounts as $cuenta) {
    if (($cuenta->numero_pagos_totales ?? 0) > 0) {
        $nuevoPorcentaje = ($cuenta->numero_cuenta / $cuenta->numero_pagos_totales) * 100;

        // Update if different (using epsilon for float comparison)
        if (abs(($cuenta->porcentaje_cuentas ?? 0) - $nuevoPorcentaje) > 0.001) {
            echo " - Account #{$cuenta->id} (Count {$cuenta->numero_cuenta}/{$cuenta->numero_pagos_totales}): {$cuenta->porcentaje_cuentas}% -> {$nuevoPorcentaje}%\n";
            $cuenta->porcentaje_cuentas = $nuevoPorcentaje;
            $cuenta->save();
        }
    }
}

echo "✅ Recalculation complete.\n";
