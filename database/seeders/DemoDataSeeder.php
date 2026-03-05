<?php

namespace Database\Seeders;

use App\Models\Contratista;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\BloqueWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Modalidad;
use App\Models\Planta;
use App\Models\Concepto;
use App\Models\TipoContratista;
use App\Models\Supervisor;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🌱 Insertando datos de prueba (DemoDataSeeder)...\n";

        // 1. Catálogos básicos si no existen
        $modalidad = Modalidad::firstOrCreate(['nombre' => 'Prestación de Servicios'], ['es_activa' => true]);
        $planta = Planta::firstOrCreate(['nombre' => 'Contratista'], ['codigo' => 'CONT', 'es_activa' => true]);
        $concepto = Concepto::firstOrCreate(['nombre' => 'Honorarios'], ['es_activo' => true]);

        $supervisor = Supervisor::updateOrCreate(
            ['email' => 'juan.perez@demo.com'],
            [
                'nombres' => 'JUAN',
                'apellidos' => 'PEREZ SUPERVISOR',
                'cargo' => 'Coordinador de Area',
                'es_activo' => true
            ]
        );

        $abogado = Usuario::first() ?? Usuario::factory()->create();

        // 2. Contratista
        $tipoContratista = \App\Models\TipoContratista::firstOrCreate(['nombre' => 'Persona Jurídica'], ['es_activo' => true]);

        $contratista = Contratista::updateOrCreate(
            ['nit' => '900123456'],
            [
                'razon_social' => 'SOLUCIONES TECH SAS',
                'tipo_persona' => 'JURIDICA',
                'representante_legal' => 'CARLOS PRUEBA',
                'email' => 'carlos@demo.com',
                'telefono' => '3001234567',
            ]
        );

        // 3. Contrato
        $contrato = Contrato::updateOrCreate(
            ['numero_contrato' => 'CONTR-2024-001'],
            [
                'contratista_id' => $contratista->id,
                'modalidad_id' => $modalidad->id,
                'planta_id' => $planta->id,
                'concepto_id' => $concepto->id,
                'supervisor_id' => $supervisor->id,
                'abogado_user_id' => $abogado->id,
                'fecha_inicio' => now()->subMonths(2)->format('Y-m-d'),
                'fecha_fin' => now()->addMonths(10)->format('Y-m-d'),
                'monto_total' => 50000000,
                'objeto' => 'Desarrollo de software y soporte técnico institucional.',
                'es_activo' => true
            ]
        );

        // 4. Cuenta de Cobro 1
        $bloque1 = BloqueWorkflow::where('codigo', 'REV1')->first();
        $estadoInicial = EstadoWorkflow::where('bloque_id', $bloque1->id)->where('es_inicial', true)->first();

        CuentaCobro::updateOrCreate(
            ['contrato_id' => $contrato->id, 'numero_cuenta' => 1],
            [
                'valor_cobro' => 5000000,
                'fecha_radicacion' => now()->subDays(5),
                'numero_pagos_totales' => 10,
                'numero_facturas_radicadas' => 0,
                'bloque_actual_id' => $bloque1->id,
                'estado_actual_id' => $estadoInicial->id,
                'responsable_actual_id' => $abogado->id,
                'finalizada' => false,
                'observaciones' => 'Cuenta de prueba inicial'
            ]
        );

        echo "✓ Datos de prueba insertados.\n";
    }
}
