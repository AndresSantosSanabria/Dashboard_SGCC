<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Configuracion;

class FixHorarioLaboral extends Command
{
    protected $signature = 'config:fix-horario {inicio?} {fin?}';
    protected $description = 'Check and fix working hours configuration';

    public function handle()
    {
        $inicio = $this->argument('inicio') ?? '06:00';
        $fin = $this->argument('fin') ?? '18:00';

        $this->info("=== CONFIGURACIÓN DE HORARIOS LABORALES ===\n");

        // Ver configuración actual
        $this->info("Valores actuales en BD:");
        $horarioInicio = Configuracion::getValor('HORARIO_LABORAL_INICIO', '06:00');
        $horarioFin = Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00');
        
        $this->line("  HORARIO_LABORAL_INICIO: $horarioInicio");
        $this->line("  HORARIO_LABORAL_FIN: $horarioFin");

        // Propuesta de corrección
        $this->info("\n¿Deseas cambiar la configuración?");
        $this->line("Valores propuestos:");
        $this->line("  HORARIO_LABORAL_INICIO: $inicio");
        $this->line("  HORARIO_LABORAL_FIN: $fin");

        if ($this->confirm('¿Continuar con los cambios?')) {
            // Actualizar o crear registros
            Configuracion::where('clave', 'HORARIO_LABORAL_INICIO')->delete();
            Configuracion::create([
                'clave' => 'HORARIO_LABORAL_INICIO',
                'valor' => $inicio,
                'tipo' => 'STRING',
                'descripcion' => 'Hora de inicio de jornada laboral (HH:mm)',
            ]);

            Configuracion::where('clave', 'HORARIO_LABORAL_FIN')->delete();
            Configuracion::create([
                'clave' => 'HORARIO_LABORAL_FIN',
                'valor' => $fin,
                'tipo' => 'STRING',
                'descripcion' => 'Hora de fin de jornada laboral (HH:mm)',
            ]);

            $this->info("\n✓ Configuración actualizada correctamente");

            // Mostrar resumen
            $this->info("\nConfiguración guardada:");
            $this->line("  HORARIO_LABORAL_INICIO: $inicio");
            $this->line("  HORARIO_LABORAL_FIN: $fin");
            
            $horas = (int) substr($fin, 0, 2) - (int) substr($inicio, 0, 2);
            $minutos = (int) substr($fin, 3, 2) - (int) substr($inicio, 3, 2);
            if ($minutos < 0) {
                $horas--;
                $minutos += 60;
            }
            $this->line("  Duración del día laboral: $horas h $minutos m");
        } else {
            $this->info("Operación cancelada");
        }
    }
}
