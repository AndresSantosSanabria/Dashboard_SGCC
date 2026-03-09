<?php

use App\Models\Contratista;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Iniciando limpieza de datos...\n";

DB::transaction(function () {
    // 1. Limpiar Contratistas
    $contratistas = Contratista::all();
    echo "Processing " . $contratistas->count() . " contratistas...\n";
    foreach ($contratistas as $c) {
        $cleanNit = strtoupper(trim($c->nit));
        if ($c->nit !== $cleanNit) {
            echo "  Updating NIT: '{$c->nit}' -> '{$cleanNit}'\n";
            $c->update(['nit' => $cleanNit]);
        }
    }

    // 2. Limpiar Contratos
    $contratos = Contrato::all();
    echo "Processing " . $contratos->count() . " contratos...\n";
    foreach ($contratos as $c) {
        $cleanNum = strtoupper(trim($c->numero_contrato));
        if ($c->numero_contrato !== $cleanNum) {
            echo "  Updating Contrato: '{$c->numero_contrato}' -> '{$cleanNum}'\n";
            $c->update(['numero_contrato' => $cleanNum]);
        }
    }

    // 3. Limpiar Cuentas de Cobro (numero_cuenta)
    // Nota: numero_cuenta es integer en el modelo, pero podría tener inconsistencias si se importó mal.
});

echo "✅ Limpieza completada.\n";
