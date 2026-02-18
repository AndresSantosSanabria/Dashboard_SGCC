<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contrato;
use App\Models\Contratista;
use App\Models\Supervisor;
use App\Models\Modalidad;
use App\Models\Concepto;
use App\Models\Planta;
use App\Models\Usuario;
use App\Models\CuentaCobro;
use App\Models\BloqueWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\EstadoBloqueCuenta;
use App\Models\RegistroPresupuestal;
use App\Models\PlanillaSeguridadSocial;
use App\Models\EntidadSeguridadSocial;
use App\Models\ContratistaSeguridadSocial;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimulationDataSeeder extends Seeder
{
    public function run()
    {
        echo "🧪 Generando datos de simulación...\n";

        DB::beginTransaction();
        try {
            // 0. Limpiar datos existentes
            echo "📝 Limpiando datos previos de simulación...\n";
            DB::table('alertas')->delete();
            DB::table('planillas_seguridad_social')->delete();
            DB::table('historial_workflow')->delete();
            DB::table('estado_bloque_cuenta')->delete();
            DB::table('cuentas_cobro')->delete();
            DB::table('registros_presupuestales')->delete();
            DB::table('documentos')->delete();
            DB::table('contratista_seguridad_social')->delete();
            DB::table('entidades_seguridad_social')->delete();
            DB::table('contratos')->delete();
            echo "✓ Datos limpiados\n";

            // 1. Obtener o Crear catálogos básicos
            $modalidad = Modalidad::firstOrCreate(['nombre' => 'PRESTACIÓN DE SERVICIOS']);
            $concepto = Concepto::firstOrCreate(['nombre' => 'APOYO A LA GESTIÓN']);
            $planta = Planta::firstOrCreate(['codigo' => 'P001'], ['nombre' => 'PLANTA CENTRAL']);
            $usuario = Usuario::where('user', 'admin')->first() ?? Usuario::first();

            if (!$usuario) {
                echo "⚠️ No se encontró usuario admin, saltando seeder de datos.\n";
                return;
            }

            // 1.1 Crear Entidades de Seguridad Social
            $entidadSalud = EntidadSeguridadSocial::firstOrCreate(['nombre' => 'REVISOR FISCAL SALUD'], ['tipo' => 'SALUD', 'codigo' => 'RF01', 'es_activa' => true]);
            $entidadPension = EntidadSeguridadSocial::firstOrCreate(['nombre' => 'REVISOR FISCAL PENSION'], ['tipo' => 'PENSION', 'codigo' => 'RF02', 'es_activa' => true]);
            $entidadArl = EntidadSeguridadSocial::firstOrCreate(['nombre' => 'REVISOR FISCAL ARL'], ['tipo' => 'ARL', 'codigo' => 'RF03', 'es_activa' => true]);

            // 2. Crear Contratistas
            $contratistasData = [
                ['nit' => '9001562707', 'razon_social' => 'CORPORACION RED NACIONAL ACADEMICA - RENATA', 'tipo_persona' => 'JURIDICA'],
                ['nit' => '8999992307', 'razon_social' => 'UNIVERSIDAD DISTRITAL FRANCISCO JOSÉ DE CALDAS', 'tipo_persona' => 'JURIDICA'],
                ['nit' => '9007414970', 'razon_social' => 'TECNOPHONE COLOMBIA S A S', 'tipo_persona' => 'JURIDICA'],
            ];

            $contratistas = [];
            foreach ($contratistasData as $data) {
                $contratista = Contratista::updateOrCreate(['nit' => $data['nit']], $data);
                $contratistas[] = $contratista;

                // Asociar Seguridad Social
                ContratistaSeguridadSocial::updateOrCreate(
                    ['contratista_id' => $contratista->id, 'es_vigente' => true],
                    [
                        'entidad_salud_id' => $entidadSalud->id,
                        'entidad_pension_id' => $entidadPension->id,
                        'entidad_arl_id' => $entidadArl->id,
                        'fecha_inicio' => Carbon::now()->subYear(),
                        'fecha_fin' => Carbon::now()->addYear(),
                    ]
                );
            }

            // 3. Crear Supervisores
            $supervisoresData = [
                ['nombres' => 'HERNAN', 'apellidos' => 'RODRIGUEZ GUEVARA', 'cargo' => 'REVISOR FISCAL'],
                ['nombres' => 'ALEJANDRO', 'apellidos' => 'OLARTE CARRILLO', 'cargo' => 'REVISOR FISCAL'],
                ['nombres' => 'ARMANDO', 'apellidos' => 'GONZALEZ', 'cargo' => 'REVISOR FISCAL'],
            ];

            $supervisores = [];
            foreach ($supervisoresData as $data) {
                $supervisores[] = Supervisor::updateOrCreate(['nombres' => $data['nombres'], 'apellidos' => $data['apellidos']], $data);
            }

            // 4. Crear Contratos y Cuentas de Cobro
            $bloques = BloqueWorkflow::orderBy('orden')->get();
            $estadoReserva = EstadoWorkflow::where('nombre', 'reserva')->first() ?? EstadoWorkflow::first();

            $dataCuentas = [
                [
                    'contrato' => 'STD-CD-CVI-070-2025',
                    'contratista_index' => 0,
                    'supervisor_index' => 0,
                    'monto' => 28577658777,
                    'rp' => '40000000',
                    'pagos_totales' => 18,
                    'cuenta_actual' => 0,
                    'observaciones' => null,
                    'fecha_inicio' => Carbon::now()->subMonths(6),
                    'fecha_fin' => Carbon::now()->addMonths(9),
                ],
                [
                    'contrato' => 'STD-CD-CI-087-2025',
                    'contratista_index' => 1,
                    'supervisor_index' => 1,
                    'monto' => 1332000000,
                    'rp' => '38000000',
                    'pagos_totales' => 1,
                    'cuenta_actual' => 0,
                    'observaciones' => null,
                    'fecha_inicio' => Carbon::create(2025, 11, 7),
                    'fecha_fin' => Carbon::create(2025, 11, 7)->addYear(),
                ],
                [
                    'contrato' => 'STD-SA-CV-119-2025 OC 153903',
                    'contratista_index' => 2,
                    'supervisor_index' => 2,
                    'monto' => 386862500,
                    'rp' => '1000000',
                    'pagos_totales' => 1,
                    'cuenta_actual' => 0,
                    'observaciones' => null,
                    'fecha_inicio' => Carbon::create(2025, 1, 1),
                    'fecha_fin' => Carbon::create(2025, 12, 19),
                ]
            ];

            foreach ($dataCuentas as $index => $item) {
                $contratista = $contratistas[$item['contratista_index']];
                $supervisor = $supervisores[$item['supervisor_index']];

                $contrato = Contrato::create([
                    'numero_contrato' => $item['contrato'],
                    'contratista_id' => $contratista->id,
                    'supervisor_id' => $supervisor->id,
                    'modalidad_id' => $modalidad->id,
                    'planta_id' => $planta->id,
                    'concepto_id' => $concepto->id,
                    'fecha_inicio' => $item['fecha_inicio'],
                    'fecha_fin' => $item['fecha_fin'],
                    'monto_total' => $item['monto'],
                    'es_activo' => true,
                ]);

                // Registro Presupuestal
                RegistroPresupuestal::create([
                    'numero_rp' => $item['rp'],
                    'contrato_id' => $contrato->id,
                    'fecha_rp' => $contrato->fecha_inicio,
                    'valor_rp' => $item['monto'],
                ]);

                // 1 cuenta por contrato 
                $bloqueActual = $bloques[0]; // REV1
                $estadoActual = $estadoReserva;

                $cuenta = CuentaCobro::create([
                    'contrato_id' => $contrato->id,
                    'numero_cuenta' => $item['cuenta_actual'],
                    'valor_cobro' => $item['monto'] / $item['pagos_totales'],
                    'fecha_radicacion' => null, // Dejar vacío como pidió el usuario
                    'numero_pagos_totales' => $item['pagos_totales'],
                    'numero_facturas_radicadas' => 0,
                    'porcentaje_cuentas' => ($item['cuenta_actual'] / $item['pagos_totales']) * 100,
                    'radicado_por' => null, // Dejar vacío como pidió el usuario
                    'bloque_actual_id' => $bloqueActual->id,
                    'estado_actual_id' => $estadoActual->id,
                    'responsable_actual_id' => $usuario->id,
                    'finalizada' => false,
                    'observaciones' => null,
                ]);

                // Poblar historial de bloques (solo el actual)
                EstadoBloqueCuenta::create([
                    'cuenta_cobro_id' => $cuenta->id,
                    'bloque_id' => $bloqueActual->id,
                    'estado_actual_id' => $estadoActual->id,
                    'fecha_ingreso_bloque' => Carbon::now(),
                    'bloque_completado' => false,
                    'responsable_id' => $usuario->id,
                ]);

                // Planilla
                PlanillaSeguridadSocial::create([
                    'cuenta_cobro_id' => $cuenta->id,
                    'numero_planilla' => "PLAN-" . rand(100000, 999999),
                    'mes_planilla' => 'RESERVA',
                    'es_ultima' => true,
                ]);
            }

            DB::commit();
            echo "✅ Datos de simulación actualizados exitosamente.\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
