<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateWorkflowStatesSeeder extends Seeder
{
    public function run()
    {
        echo "🔄 Iniciando actualización de estados del workflow...\n\n";

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::beginTransaction();

        try {
            // 1. Limpiar datos existentes y capturar para migración
            echo "📝 Limpiando datos existentes...\n";
            // Guardar mapping antiguo para restaurar correctamente las cuentas
            $estadosAntiguos = DB::table('estados_workflow')->pluck('codigo', 'id')->toArray();

            DB::table('transiciones_permitidas')->delete();
            DB::table('estados_workflow')->delete();
            DB::table('bloques_workflow')->delete();
            echo "✓ Datos limpiados (y mapeo antiguo capturado)\n\n";

            // 2. Crear Bloques
            echo "📦 Creando bloques...\n";
            DB::table('bloques_workflow')->insert([
                ['id' => 1, 'nombre' => 'ESTADO TRAS PRIMERA REVISIÓN (SERGIO / CONSUELO)', 'codigo' => 'REV1', 'orden' => 1, 'descripcion' => 'Primera fase de revisión', 'es_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'nombre' => 'ENVIADA A INGRESO MERCANCIA SAP', 'codigo' => 'SAP', 'orden' => 2, 'descripcion' => 'Ingreso en sistema SAP', 'es_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 3, 'nombre' => 'EN FACTURACIÓN', 'codigo' => 'FAC', 'orden' => 3, 'descripcion' => 'Proceso de facturación', 'es_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 4, 'nombre' => 'FIRMA SECRETARIO', 'codigo' => 'FIR', 'orden' => 4, 'descripcion' => 'Firma de secretaría', 'es_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 5, 'nombre' => 'RADICADA EN HACIENDA', 'codigo' => 'HAC', 'orden' => 5, 'descripcion' => 'Radicación final', 'es_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 6, 'nombre' => 'FINALIZADA', 'codigo' => 'FIN', 'orden' => 6, 'descripcion' => 'Cuenta completada', 'es_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
            echo "✓ 6 bloques creados\n\n";

            // 3. Crear Estados por Bloque
            echo "🏷️  Creando estados...\n";

            // Estructura de estados
            $estadosPorBloque = [
                1 => [
                    ['nombre' => 'Sin tramite', 'codigo' => 'REV1_SIN', 'tipo' => 'INICIAL', 'es_inicial' => 1],
                    ['nombre' => 'en revision', 'codigo' => 'REV1_REV', 'tipo' => 'EN_PROCESO'],
                    ['nombre' => 'en espera firma jaime moncaleano', 'codigo' => 'REV1_ESP_MON', 'tipo' => 'EN_PROCESO'],
                    ['nombre' => 'pasa', 'codigo' => 'REV1_PASA', 'tipo' => 'APROBADO', 'es_final' => 1],
                    ['nombre' => 'devuelta', 'codigo' => 'REV1_DEV', 'tipo' => 'DEVUELTO'],
                ],
                2 => [
                    ['nombre' => 'en espera ingreso mercancia', 'codigo' => 'SAP_ESP', 'tipo' => 'INICIAL', 'es_inicial' => 1],
                    ['nombre' => 'con ingreso mercancia', 'codigo' => 'SAP_OK', 'tipo' => 'APROBADO', 'es_final' => 1],
                    ['nombre' => 'devuelta', 'codigo' => 'SAP_DEV', 'tipo' => 'DEVUELTO'],
                ],
                3 => [
                    ['nombre' => 'EN ESPERA EN FACTURACION', 'codigo' => 'FAC_ESP', 'tipo' => 'INICIAL', 'es_inicial' => 1],
                    ['nombre' => 'FACTURADA', 'codigo' => 'FAC_OK', 'tipo' => 'APROBADO', 'es_final' => 1],
                    ['nombre' => 'devuelta', 'codigo' => 'FAC_DEV', 'tipo' => 'DEVUELTO'],
                ],
                4 => [
                    ['nombre' => 'En espera', 'codigo' => 'FIR_ESP', 'tipo' => 'INICIAL', 'es_inicial' => 1],
                    ['nombre' => 'Firmada', 'codigo' => 'FIR_OK', 'tipo' => 'APROBADO', 'es_final' => 1],
                    ['nombre' => 'devuelta', 'codigo' => 'FIR_DEV', 'tipo' => 'DEVUELTO'],
                ],
                5 => [
                    ['nombre' => 'En espera', 'codigo' => 'HAC_ESP', 'tipo' => 'INICIAL', 'es_inicial' => 1],
                    ['nombre' => 'Radicada', 'codigo' => 'HAC_OK', 'tipo' => 'APROBADO', 'es_final' => 1],
                    ['nombre' => 'devuelta', 'codigo' => 'HAC_DEV', 'tipo' => 'DEVUELTO'],
                ],
                6 => [
                    ['nombre' => 'Por Confirmar', 'codigo' => 'FIN_PEND', 'tipo' => 'INICIAL', 'es_inicial' => 1, 'es_final' => 0],
                    ['nombre' => 'Finalizada', 'codigo' => 'FIN_OK', 'tipo' => 'APROBADO', 'es_inicial' => 0, 'es_final' => 1],
                ],
            ];

            foreach ($estadosPorBloque as $bloqueId => $estados) {
                foreach ($estados as $est) {
                    DB::table('estados_workflow')->insert([
                        'bloque_id' => $bloqueId,
                        'nombre' => $est['nombre'],
                        'codigo' => $est['codigo'],
                        'tipo' => $est['tipo'],
                        'es_inicial' => $est['es_inicial'] ?? 0,
                        'es_final' => $est['es_final'] ?? 0,
                        'permite_devolucion' => $est['tipo'] === 'DEVUELTO',
                        'color_hex' => $this->getColorPorTipo($est['tipo']),
                        'descripcion' => $est['nombre'],
                        'es_activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $totalEstados = DB::table('estados_workflow')->count();
            echo "✓ {$totalEstados} estados creados\n\n";

            // 4. Crear Transiciones Exhaustivas
            echo "🔗 Creando transiciones permitidas (Extensivas)...\n";
            $this->createExhaustiveTransitions();

            $totalTransiciones = DB::table('transiciones_permitidas')->count();
            echo "✓ {$totalTransiciones} transiciones creadas\n\n";

            DB::commit();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            echo "✅ ¡Actualización completada exitosamente!\n\n";
            $this->showSummary();

            // ── Reparar referencias en cuentas_cobro ──────────────────────────
            echo "\n🔧 Reparando referencias de estado en cuentas_cobro...\n";

            // Obtener el nuevo mapa de código => ID después de recrear las tablas
            $nuevosEstados = DB::table('estados_workflow')->pluck('id', 'codigo')->toArray();

            $cuentas = DB::table('cuentas_cobro')
                ->whereNotNull('bloque_actual_id')
                ->get(['id', 'bloque_actual_id', 'estado_actual_id']);

            $reparadas = 0;
            foreach ($cuentas as $cuenta) {
                // Si el ID antiguo ya no existe, usamos el mapping guardado al inicio del script
                // Pero necesitamos asegurarnos de que la cuenta tenga el estado correcto.
                // $estadosAntiguos lo vamos a guardar ANTES de limpiar los datos (ver próximo paso).

                // Por defecto, si algo sale mal o si no hay estado antiguo mapeable o es un contrato
                // nuevo (estado_actual_id null pero bloque_actual_id 1), asignamos el inicial:
                $nuevoId = null;

                if (isset($estadosAntiguos[$cuenta->estado_actual_id])) {
                    $codigoAntiguo = $estadosAntiguos[$cuenta->estado_actual_id];
                    // Si el estado antiguo era REV1_REV (el viejo inicial), lo pasamos al nuevo REV1_SIN
                    if ($codigoAntiguo === 'REV1_REV') {
                        $codigoAntiguo = 'REV1_SIN';
                    }
                    if (isset($nuevosEstados[$codigoAntiguo])) {
                        $nuevoId = $nuevosEstados[$codigoAntiguo];
                    }
                }

                if (! $nuevoId) {
                    $inicial = DB::table('estados_workflow')
                        ->where('bloque_id', $cuenta->bloque_actual_id)
                        ->where('es_inicial', 1)
                        ->first();
                    if ($inicial) {
                        $nuevoId = $inicial->id;
                    }
                }

                if ($nuevoId && $nuevoId !== $cuenta->estado_actual_id) {
                    DB::table('cuentas_cobro')
                        ->where('id', $cuenta->id)
                        ->update(['estado_actual_id' => $nuevoId]);
                    $reparadas++;
                }
            }
            echo "✓ {$reparadas} cuentas reparadas o actualizadas con el nuevo estado inicial\n";
            // ──────────────────────────────────────────────────────────────────
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            echo '❌ ERROR: '.$e->getMessage()."\n";
            throw $e;
        }
    }

    private function getColorPorTipo($tipo)
    {
        return match ($tipo) {
            'INICIAL' => '#17a2b8',
            'EN_PROCESO' => '#ffc107',
            'APROBADO' => '#28a745',
            'DEVUELTO' => '#dc3545',
            'FINAL' => '#20c997',
            default => '#6c757d',
        };
    }

    private function createExhaustiveTransitions()
    {
        $estados = DB::table('estados_workflow')->get();
        $estadosByBloque = $estados->groupBy('bloque_id');

        foreach ($estados as $origen) {
            // 1. Transiciones INTERNAS (Todo con todo dentro del mismo bloque)
            $mismoBloque = $estadosByBloque->get($origen->bloque_id);
            foreach ($mismoBloque as $destino) {
                if ($origen->id != $destino->id) {
                    // En el Bloque 1 (REV1) sí se permite transicionar al estado 'devuelta' interno
                    // porque no hay bloque anterior al que devolver
                    $esDevueltaInterna = $destino->tipo === 'DEVUELTO';
                    $esBloque1 = $origen->bloque_id == 1;

                    if (! $esDevueltaInterna || $esBloque1) {
                        $accion = $esDevueltaInterna ? 'DEVOLVER' : 'CAMBIAR_ESTADO';
                        $this->insertTransition($origen, $destino, $accion);
                    }
                }
            }

            // 2. Transiciones de AVANCE (Aprobado -> Inicial Siguiente Bloque)
            if ($origen->es_final && $origen->bloque_id < 6) {
                $inicialSiguiente = DB::table('estados_workflow')
                    ->where('bloque_id', $origen->bloque_id + 1)
                    ->where('es_inicial', true)
                    ->first();

                if ($inicialSiguiente) {
                    $this->insertTransition($origen, $inicialSiguiente, 'PASAR_BLOQUE');
                }
            }

            // 3. Transiciones de RETORNO (Cualquier estado -> Devuelta Bloque Anterior)
            // Esto crea el botón "devuelta" de forma controlada hacia el bloque previo
            if ($origen->bloque_id > 1) {
                $devueltaAnterior = DB::table('estados_workflow')
                    ->where('bloque_id', $origen->bloque_id - 1)
                    ->where('tipo', 'DEVUELTO')
                    ->first();

                if ($devueltaAnterior) {
                    $this->insertTransition($origen, $devueltaAnterior, 'DEVOLVER');
                }
            }

            // 4. Especial: Retorno desde el bloque final (OK -> Devuelta Bloque 5)
            if ($origen->codigo === 'FIN_OK') {
                $devueltaAnterior = DB::table('estados_workflow')
                    ->where('bloque_id', 5)
                    ->where('tipo', 'DEVUELTO')
                    ->first();

                if ($devueltaAnterior) {
                    $this->insertTransition($origen, $devueltaAnterior, 'DEVOLVER');
                }
            }
        }
    }

    private function insertTransition($origen, $destino, $accion)
    {
        // Evitar duplicados por si acaso
        $exists = DB::table('transiciones_permitidas')
            ->where('estado_origen_id', $origen->id)
            ->where('estado_destino_id', $destino->id)
            ->exists();

        if (! $exists) {
            DB::table('transiciones_permitidas')->insert([
                'estado_origen_id' => $origen->id,
                'estado_destino_id' => $destino->id,
                'requiere_comentario' => $destino->tipo === 'DEVUELTO' || $accion === 'DEVOLVER',
                'requiere_documento' => false,
                'accion' => $accion,
                'descripcion' => "{$accion} de {$origen->nombre} a {$destino->nombre}",
                'es_activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function showSummary()
    {
        echo "📊 RESUMEN:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $bloques = DB::table('bloques_workflow')->orderBy('orden')->get();
        foreach ($bloques as $bloque) {
            $cantidadEstados = DB::table('estados_workflow')->where('bloque_id', $bloque->id)->count();
            echo "  {$bloque->orden}. {$bloque->nombre}: {$cantidadEstados} estados\n";
        }

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
