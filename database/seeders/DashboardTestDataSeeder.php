<?php

namespace Database\Seeders;

use App\Models\BloqueWorkflow;
use App\Models\Concepto;
use App\Models\Contratista;
use App\Models\ContratistaSeguridadSocial;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\EntidadSeguridadSocial;
use App\Models\EstadoBloqueCuenta;
use App\Models\EstadoWorkflow;
use App\Models\Modalidad;
use App\Models\PlanillaSeguridadSocial;
use App\Models\Planta;
use App\Models\RegistroPresupuestal;
use App\Models\Role;
use App\Models\Supervisor;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DashboardTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles y Usuarios necesarios
        $adminRole = Role::firstOrCreate(['nombre' => 'Administrador']);

        $consuelo = Usuario::firstOrCreate(
            ['user' => 'consuelo'],
            [
                'primer_nombre' => 'Consuelo',
                'primer_apellido' => 'Hidalgo',
                'password' => Hash::make('password123'),
                'rol_id' => $adminRole->id,
                'es_activo' => true,
            ]
        );

        $claudia = Usuario::firstOrCreate(
            ['user' => 'claudia'],
            [
                'primer_nombre' => 'Claudia',
                'primer_apellido' => 'Gestión',
                'password' => Hash::make('password123'),
                'rol_id' => $adminRole->id,
                'es_activo' => true,
            ]
        );

        // 2. Catálogos básicos
        $modalidad = Modalidad::firstOrCreate(['nombre' => 'PRESTACIÓN DE SERVICIOS']);
        $concepto = Concepto::firstOrCreate(['nombre' => 'APOYO A LA GESTIÓN']);
        $planta = Planta::firstOrCreate(['codigo' => 'P001'], ['nombre' => 'PLANTA CENTRAL']);

        // 3. Workflow (Si no existe)
        $bloques = [
            ['orden' => 1, 'nombre' => 'Radicación', 'codigo' => 'RAD'],
            ['orden' => 2, 'nombre' => 'Revisión', 'codigo' => 'REV'],
            ['orden' => 3, 'nombre' => 'SAP', 'codigo' => 'SAP'],
            ['orden' => 4, 'nombre' => 'Facturación', 'codigo' => 'FAC'],
            ['orden' => 5, 'nombre' => 'Firma', 'codigo' => 'FIR'],
        ];

        foreach ($bloques as $b) {
            $bloque = BloqueWorkflow::firstOrCreate(
                ['codigo' => $b['codigo']],
                ['nombre' => $b['nombre'], 'orden' => $b['orden'], 'es_activo' => true]
            );

            // Estado inicial por bloque
            EstadoWorkflow::firstOrCreate(
                ['codigo' => $b['codigo'].'_INI'],
                [
                    'bloque_id' => $bloque->id,
                    'nombre' => 'PENDIENTE '.strtoupper($b['nombre']),
                    'tipo' => 'INICIAL',
                    'es_inicial' => true,
                    'es_activo' => true,
                ]
            );

            // Estado final por bloque
            EstadoWorkflow::firstOrCreate(
                ['codigo' => $b['codigo'].'_FIN'],
                [
                    'bloque_id' => $bloque->id,
                    'nombre' => strtoupper($b['nombre']).' COMPLETADO',
                    'tipo' => 'FINAL',
                    'es_final' => true,
                    'es_activo' => true,
                ]
            );
        }

        // 4. Actores (Laura y Jaime)
        $contratista = Contratista::firstOrCreate(
            ['nit' => '1073169827'],
            [
                'razon_social' => 'LAURA FERNANDA SOTO AMAYA',
                'tipo_persona' => 'NATURAL',
                'es_activo' => true,
            ]
        );

        $supervisor = Supervisor::firstOrCreate(
            ['nombres' => 'JAIME', 'apellidos' => 'MONCALEANO'],
            ['cargo' => 'SUPERVISOR', 'es_activo' => true]
        );

        // 4.5 Seguridad Social de la Contratista
        $famisanar = EntidadSeguridadSocial::firstOrCreate(['nombre' => 'FAMISANAR'], ['tipo' => 'SALUD']);
        $proteccion = EntidadSeguridadSocial::firstOrCreate(['nombre' => 'PROTECCIÓN'], ['tipo' => 'PENSION']);
        $positiva = EntidadSeguridadSocial::firstOrCreate(['nombre' => 'POSITIVA'], ['tipo' => 'ARL']);

        ContratistaSeguridadSocial::firstOrCreate(
            ['contratista_id' => $contratista->id, 'es_vigente' => true],
            [
                'entidad_salud_id' => $famisanar->id,
                'entidad_pension_id' => $proteccion->id,
                'entidad_arl_id' => $positiva->id,
            ]
        );

        // 5. Contrato
        $contrato = Contrato::firstOrCreate(
            ['numero_contrato' => 'STIC-CPS-001-2025'],
            [
                'contratista_id' => $contratista->id,
                'supervisor_id' => $supervisor->id,
                'modalidad_id' => $modalidad->id,
                'planta_id' => $planta->id,
                'concepto_id' => $concepto->id,
                'monto_total' => 64473280,
                'fecha_inicio' => Carbon::create(2025, 1, 14),
                'fecha_fin' => Carbon::create(2025, 9, 13),
                'es_activo' => true,
            ]
        );

        // 6. RP
        RegistroPresupuestal::firstOrCreate(
            ['numero_rp' => '4600027194'],
            [
                'contrato_id' => $contrato->id,
                'fecha_rp' => Carbon::create(2025, 1, 14),
                'valor_rp' => 64473280,
                'estado' => 'ACTIVO',
            ]
        );

        // 7. Cuenta de Cobro
        $bloqueRevisión = BloqueWorkflow::where('codigo', 'REV')->first();
        $estadoPasa = EstadoWorkflow::where('bloque_id', $bloqueRevisión->id)->where('tipo', 'FINAL')->first();

        // Limpiar registros previos para permitir re-ejecución limpia
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CuentaCobro::where('numero_cuenta', '12')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $cuenta = CuentaCobro::create([
            'contrato_id' => $contrato->id,
            'numero_cuenta' => '12',
            'valor_cobro' => 64473280 / 12, // Ejemplo
            'fecha_radicacion' => Carbon::create(2026, 2, 6, 9, 0, 0),
            'numero_pagos_totales' => 12,
            'numero_facturas_radicadas' => 12,
            'porcentaje_cuentas' => 100.00,
            'radicado_por' => 'LAURA SOTO',
            'bloque_actual_id' => $bloqueRevisión->id,
            'estado_actual_id' => $estadoPasa->id,
            'responsable_actual_id' => $consuelo->id,
            'observaciones' => 'NINGUNA',
            'finalizada' => true,
            'ultima_factura_hacienda' => '12',
            'fecha_radicacion_hacienda' => Carbon::create(2026, 2, 6, 15, 0, 0),
            'observacion_hacienda' => 'NINGUNA',
        ]);

        // 8. Planilla SS
        PlanillaSeguridadSocial::create([
            'cuenta_cobro_id' => $cuenta->id,
            'mes_planilla' => 'OCTUBRE',
            'anio_planilla' => 2025,
            'numero_planilla' => '12',
            'valor_total' => 0,
            'fecha_pago' => Carbon::create(2025, 10, 5),
            'es_ultima' => true,
        ]);

        // 9. Estados de bloques (Historial para el dashboard)
        $bloqueRadicacion = BloqueWorkflow::where('codigo', 'RAD')->first();
        $bloqueRevisión = BloqueWorkflow::where('codigo', 'REV')->first();
        $bloqueSap = BloqueWorkflow::where('codigo', 'SAP')->first();
        $bloqueFacturacion = BloqueWorkflow::where('codigo', 'FAC')->first();
        $bloqueFirma = BloqueWorkflow::where('codigo', 'FIR')->first();

        $estadoPasa = EstadoWorkflow::where('bloque_id', $bloqueRevisión->id)->where('tipo', 'FINAL')->first();

        // RADICACIÓN
        EstadoBloqueCuenta::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $bloqueRadicacion->id,
            'estado_actual_id' => EstadoWorkflow::where('bloque_id', $bloqueRadicacion->id)->where('tipo', 'FINAL')->first()->id,
            'responsable_id' => $consuelo->id,
            'fecha_ingreso_bloque' => Carbon::create(2026, 2, 6),
            'fecha_completado_bloque' => Carbon::create(2026, 2, 6),
            'bloque_completado' => true,
        ]);

        // REVISIÓN (Consuelo)
        EstadoBloqueCuenta::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $bloqueRevisión->id,
            'estado_actual_id' => $estadoPasa->id,
            'responsable_id' => $consuelo->id,
            'fecha_ingreso_bloque' => Carbon::create(2026, 2, 6),
            'fecha_completado_bloque' => Carbon::create(2026, 2, 6),
            'bloque_completado' => true,
            'observaciones' => 'PASA',
        ]);

        // SAP (Claudia)
        EstadoBloqueCuenta::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $bloqueSap->id,
            'estado_actual_id' => EstadoWorkflow::where('bloque_id', $bloqueSap->id)->where('tipo', 'FINAL')->first()->id,
            'responsable_id' => $claudia->id,
            'fecha_ingreso_bloque' => Carbon::create(2026, 2, 6),
            'fecha_completado_bloque' => Carbon::create(2026, 2, 6),
            'bloque_completado' => true,
        ]);

        // FACTURACIÓN (Claudia)
        EstadoBloqueCuenta::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $bloqueFacturacion->id,
            'estado_actual_id' => EstadoWorkflow::where('bloque_id', $bloqueFacturacion->id)->where('tipo', 'FINAL')->first()->id,
            'responsable_id' => $claudia->id,
            'fecha_ingreso_bloque' => Carbon::create(2026, 2, 6),
            'fecha_completado_bloque' => Carbon::create(2026, 2, 6),
            'bloque_completado' => true,
            'observaciones' => 'FACTURADA',
        ]);

        // FIRMA
        EstadoBloqueCuenta::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $bloqueFirma->id,
            'estado_actual_id' => EstadoWorkflow::where('bloque_id', $bloqueFirma->id)->where('tipo', 'FINAL')->first()->id,
            'responsable_id' => $consuelo->id, // Ejemplo
            'fecha_ingreso_bloque' => Carbon::create(2026, 2, 6),
            'fecha_completado_bloque' => Carbon::create(2026, 2, 6),
            'bloque_completado' => true,
        ]);

        $this->command->info('Registro de prueba para LAURA SOTO con workflow completo creado correctamente.');
    }
}
