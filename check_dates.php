<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$cuenta = DB::table('cuentas_cobro')
    ->join('contratos', 'contratos.id', '=', 'cuentas_cobro.contrato_id')
    ->where('contratos.numero_contrato', 'STD-CD-PSP-004-2026')
    ->select('cuentas_cobro.id', 'cuentas_cobro.created_at', 'cuentas_cobro.fecha_radicacion', 'cuentas_cobro.updated_at')
    ->first();

print_r($cuenta);
