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

            // 2. Crear Contratistas
            $contratistasData = [
                ['nit' => '900123456-1', 'razon_social' => 'CONSTRUCCIONES ABC SAS', 'tipo_persona' => 'JURIDICA'],
                ['nit' => '800987654-2', 'razon_social' => 'TECNOLOGÍA Y DESARROLLO LTDA', 'tipo_persona' => 'JURIDICA'],
                ['nit' => '1020304050', 'razon_social' => 'MARIA FERNANDA RODRIGUEZ', 'tipo_persona' => 'NATURAL'],
                ['nit' => '50607080', 'razon_social' => 'CARLOS ANDRES GOMEZ', 'tipo_persona' => 'NATURAL'],
                ['nit' => '901222333-0', 'razon_social' => 'SERVICIOS INTEGRALES DE SALUD', 'tipo_persona' => 'JURIDICA'],
            ];

            $contratistas = [];
            foreach ($contratistasData as $data) {
                $contratistas[] = Contratista::firstOrCreate(['nit' => $data['nit']], $data);
            }

            // 3. Crear Supervisores
            $supervisoresData = [
                ['nombres' => 'SERGIO', 'apellidos' => 'MONCALEANO', 'cargo' => 'JEFE DE AREA'],
                ['nombres' => 'CONSUELO', 'apellidos' => 'MARTINEZ', 'cargo' => 'COORDINADORA'],
                ['nombres' => 'JUAN', 'apellidos' => 'PABLO DUARTE', 'cargo' => 'SUPERVISOR TÉCNICO'],
            ];

            $supervisores = [];
            foreach ($supervisoresData as $data) {
                $supervisores[] = Supervisor::firstOrCreate(['nombres' => $data['nombres'], 'apellidos' => $data['apellidos']], $data);
            }

            // 4. Crear Contratos y Cuentas de Cobro
            $bloques = BloqueWorkflow::orderBy('orden')->get();
            $estados = EstadoWorkflow::all()->groupBy('bloque_id');

            // Limitamos a 3 contratos exactos como pidió el usuario
            for ($i = 1; $i <= 3; $i++) {
                $contratista = $contratistas[$i-1]; // Usar los primeros 3 contratistas
                $supervisor = $supervisores[array_rand($supervisores)];
                
                $montoTotal = rand(5000000, 20000000);
                $numContrato = "CONT-2026-" . str_pad($i, 3, '0', STR_PAD_LEFT);

                $contrato = Contrato::create([
                    'numero_contrato' => $numContrato,
                    'contratista_id' => $contratista->id,
                    'supervisor_id' => $supervisor->id,
                    'modalidad_id' => $modalidad->id,
                    'planta_id' => $planta->id,
                    'concepto_id' => $concepto->id,
                    'fecha_inicio' => Carbon::now()->subMonths($i),
                    'fecha_fin' => Carbon::now()->addMonths(12),
                    'monto_total' => $montoTotal,
                    'es_activo' => true,
                ]);

                // Registro Presupuestal
                RegistroPresupuestal::create([
                    'numero_rp' => "RP-" . rand(1000, 9999),
                    'contrato_id' => $contrato->id,
                    'fecha_rp' => $contrato->fecha_inicio,
                    'valor_rp' => $montoTotal,
                ]);

                // 1 cuenta por contrato para simplicidad en la prueba
                $bloqueIndex = $i - 1; // Ponerlos en diferentes bloques para probar (1 en REV1, 1 en SAP, 1 en FAC)
                $bloqueActual = $bloques[$bloqueIndex];
                $estadoActual = $estados[$bloqueActual->id]->where('es_inicial', true)->first();
                
                $finalizada = ($bloqueActual->codigo === 'FIN');

                $cuenta = CuentaCobro::create([
                    'contrato_id' => $contrato->id,
                    'numero_cuenta' => 1,
                    'valor_cobro' => $montoTotal / 12,
                    'fecha_radicacion' => Carbon::now()->subDays(rand(1, 15)),
                    'numero_pagos_totales' => 12,
                    'numero_facturas_radicadas' => ($finalizada ? 1 : 0),
                    'porcentaje_cuentas' => ($finalizada ? 100 : 0),
                    'radicado_por' => $usuario->user,
                    'bloque_actual_id' => $bloqueActual->id,
                    'estado_actual_id' => $estadoActual->id,
                    'responsable_actual_id' => $usuario->id,
                    'finalizada' => $finalizada,
                    'observaciones' => "Cuenta de prueba " . $i,
                ]);

                // Poblar historial de bloques
                for ($k = 0; $k <= $bloqueIndex; $k++) {
                    $b = $bloques[$k];
                    $e = $estados[$b->id]->where('es_inicial', true)->first();
                    
                    EstadoBloqueCuenta::create([
                        'cuenta_cobro_id' => $cuenta->id,
                        'bloque_id' => $b->id,
                        'estado_actual_id' => ($k === $bloqueIndex ? $estadoActual->id : $estados[$b->id]->where('es_final', true)->first()?->id ?? $e->id),
                        'fecha_ingreso_bloque' => Carbon::now()->subDays(20 - ($k * 5)),
                        'fecha_completado_bloque' => ($k < $bloqueIndex ? Carbon::now()->subDays(20 - ($k * 5) - 2) : null),
                        'bloque_completado' => ($k < $bloqueIndex),
                        'responsable_id' => $usuario->id,
                    ]);
                }

                // Planilla
                PlanillaSeguridadSocial::create([
                    'cuenta_cobro_id' => $cuenta->id,
                    'numero_planilla' => "PLAN-" . rand(100000, 999999),
                    'mes_planilla' => strtoupper(Carbon::now()->subMonth()->translatedFormat('F')),
                    'es_ultima' => true,
                ]);
            }

            DB::commit();
            echo "✅ Datos de simulación generados exitosamente.\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
