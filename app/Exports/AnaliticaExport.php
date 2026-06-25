<?php

namespace App\Exports;

use App\Models\HistorialWorkflow;
use App\Models\CuentaCobro;
use App\Models\TaskTimeLog;
use App\Models\EstadoWorkflow;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnaliticaExport
{
    public static function download(array $usuariosIds, $fechaInicio, $fechaFin, array $parametros)
    {
        $businessTime = app(\App\Services\BusinessTimeService::class);
        $fileName = 'Informe_Gestion_BI_' . now()->format('Ymd_His') . '.xlsx';
        
        $writer = SimpleExcelWriter::streamDownload($fileName);

        $fechaInicioParsed = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFinParsed = Carbon::parse($fechaFin)->endOfDay();

        $gestiones = [];

        // 1. Obtener tiempos cerrados desde TaskTimeLog (IGUAL AL DASHBOARD)
        $logsQuery = TaskTimeLog::where('duracion_segundos', '>', 0)
            ->whereIn('usuario_id', $usuariosIds)
            // Filtro de fechas aplicado a cuando se registró el tiempo
            ->whereBetween('updated_at', [$fechaInicioParsed, $fechaFinParsed])
            ->whereHas('estado', function ($query) {
                $query->where('codigo', '!=', 'REV1_SIN');
            })
            ->with(['cuentaCobro.contrato.contratista']);

        // Para evitar problemas de memoria, usamos chunk
        $logsQuery->chunk(1000, function($logs) use (&$gestiones) {
            foreach ($logs as $log) {
                if (!$log->cuentaCobro) continue;
                
                $key = $log->cuenta_cobro_id . '_' . $log->estado_id . '_' . $log->usuario_id;
                
                if (!isset($gestiones[$key])) {
                    $gestiones[$key] = [
                        'cuenta_cobro_id' => $log->cuenta_cobro_id,
                        'estado_id' => $log->estado_id,
                        'usuario_id' => $log->usuario_id,
                        'tiempo_segundos' => 0,
                        'es_activo' => false,
                        'cuenta' => $log->cuentaCobro,
                    ];
                }
                
                $gestiones[$key]['tiempo_segundos'] += $log->duracion_segundos;
            }
        });

        // 2. Obtener tiempos volátiles actuales (cuentas activas) (IGUAL AL DASHBOARD)
        $abiertosQuery = CuentaCobro::with(['contrato.contratista'])
            ->where('finalizada', false)
            ->whereNotNull('fecha_ultimo_cambio_estado')
            ->whereIn('responsable_actual_id', $usuariosIds)
            ->whereHas('estadoActual', function ($query) {
                $query->where('codigo', '!=', 'REV1_SIN');
            });
            
        $abiertos = $abiertosQuery->get();

        foreach ($abiertos as $cuenta) {
            $key = $cuenta->id . '_' . $cuenta->estado_actual_id . '_' . $cuenta->responsable_actual_id;
            
            $volatil = $businessTime->getWorkingSecondsBetween($cuenta->fecha_ultimo_cambio_estado, now());
            if ($volatil <= 0) {
                $volatil = abs(now()->diffInSeconds($cuenta->fecha_ultimo_cambio_estado));
            }

            if (!isset($gestiones[$key])) {
                $gestiones[$key] = [
                    'cuenta_cobro_id' => $cuenta->id,
                    'estado_id' => $cuenta->estado_actual_id,
                    'usuario_id' => $cuenta->responsable_actual_id,
                    'tiempo_segundos' => 0,
                    'es_activo' => true,
                    'cuenta' => $cuenta,
                ];
            } else {
                $gestiones[$key]['es_activo'] = true;
            }
            
            $gestiones[$key]['tiempo_segundos'] += $volatil;
        }

        // Si no hay datos
        if (empty($gestiones)) {
            $writer->addRow(['Sin resultados' => 'No se encontraron registros de gestión para los filtros y fechas seleccionadas.']);
            $writer->toBrowser();
            return response()->noContent();
        }

        // Pre-cargar diccionarios para no saturar la BD
        $estadosMap = EstadoWorkflow::with('bloque')->get()->keyBy('id');
        $usuariosMap = DB::table('usuarios')->whereIn('id', $usuariosIds)->get()->keyBy('id');

        // Procesar y escribir las filas
        foreach ($gestiones as $gestion) {
            $row = [];
            
            $usuario = $usuariosMap->get($gestion['usuario_id']);
            $responsable = $usuario ? ($usuario->primer_nombre . ' ' . $usuario->primer_apellido) : 'Desconocido';
            
            if (count($usuariosIds) > 1 || auth()->user()->isAdmin()) {
                $row['Responsable'] = $responsable;
            }

            $cuenta = $gestion['cuenta'];
            $row['N° Contrato'] = $cuenta->contrato->numero_contrato ?? 'N/A';
            $row['N° Cuenta'] = $cuenta->numero_cuenta ?? 'N/A';
            $row['Contratista'] = $cuenta->contratista->nombre_completo ?? 'N/A';
            $row['Identificación'] = $cuenta->contratista->nit ?? 'N/A';

            $estadoObj = $estadosMap->get($gestion['estado_id']);
            $bloqueNombre = $estadoObj && $estadoObj->bloque ? $estadoObj->bloque->nombre : 'Sin bloque';
            $estadoNombre = $estadoObj ? $estadoObj->nombre : 'N/A';
            
            $row['Etapa Gestionada'] = "{$bloqueNombre} -> {$estadoNombre}";
            $row['Estado del Trámite'] = $gestion['es_activo'] ? 'EN PROGRESO' : 'COMPLETADO';

            if (in_array('tiempo_respuesta', $parametros)) {
                $minutos = round($gestion['tiempo_segundos'] / 60);
                if ($minutos > 0) {
                    $horas = floor($minutos / 60);
                    $mins = $minutos % 60;
                    $row['Tiempo de respuesta'] = "{$horas}h {$mins}m";
                } else {
                    $row['Tiempo de respuesta'] = "0h 0m";
                }
            }

            if (in_array('origen', $parametros)) {
                // Buscamos la transición que metió esta cuenta en este estado
                $transicionIngreso = DB::table('historial_workflow')
                    ->where('cuenta_cobro_id', $gestion['cuenta_cobro_id'])
                    ->where('estado_destino_id', $gestion['estado_id'])
                    ->orderBy('id', 'desc')
                    ->first();
                    
                if ($transicionIngreso && $transicionIngreso->usuario_accion_id) {
                    $origenUser = DB::table('usuarios')->where('id', $transicionIngreso->usuario_accion_id)->first();
                    $row['Origen de asignación'] = $origenUser ? ($origenUser->primer_nombre . ' ' . $origenUser->primer_apellido) : 'Sistema';
                } else {
                    $row['Origen de asignación'] = 'Sistema / Inicial';
                }
            }

            if (in_array('destino', $parametros)) {
                if ($gestion['es_activo']) {
                    $row['Destino de gestión'] = 'PENDIENTE (En sus manos)';
                } else {
                    // Buscamos hacia dónde salió esta cuenta desde este estado
                    $transicionSalida = DB::table('historial_workflow')
                        ->where('cuenta_cobro_id', $gestion['cuenta_cobro_id'])
                        ->where('estado_origen_id', $gestion['estado_id'])
                        // Se quita el filtro del usuario accion para cubrir reasignaciones por admins
                        ->orderBy('id', 'desc')
                        ->first();
                        
                    if ($transicionSalida && $transicionSalida->estado_destino_id) {
                        $estadoDestino = $estadosMap->get($transicionSalida->estado_destino_id);
                        if ($estadoDestino) {
                            $bloqueDestino = $estadoDestino->bloque ? $estadoDestino->bloque->nombre : 'Sin bloque';
                            $row['Destino de gestión'] = "{$bloqueDestino} -> {$estadoDestino->nombre}";
                        } else {
                            $row['Destino de gestión'] = 'Estado no encontrado';
                        }
                    } else {
                        $row['Destino de gestión'] = 'Reasignado a otra persona (Misma etapa)';
                    }
                }
            }

            $writer->addRow($row);
        }

        $writer->toBrowser();
        return response()->noContent();
    }
}
