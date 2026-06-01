<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\BusinessTimeService;
use App\Models\Configuracion;

class DebugFormatting extends Command
{
    protected $signature = 'debug:formatting';
    protected $description = 'Debug the formatting functions';

    public function handle()
    {
        $businessTime = app(BusinessTimeService::class);

        // Verificar la configuración
        $rawStart = Configuracion::getValor('HORARIO_LABORAL_INICIO', '06:00');
        $rawEnd = Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00');
        
        $this->info("=== VERIFICACIÓN DE HORARIO LABORAL ===");
        $this->line("HORARIO_LABORAL_INICIO: $rawStart");
        $this->line("HORARIO_LABORAL_FIN: $rawEnd");

        $t1 = Carbon::parse($rawStart);
        $t2 = Carbon::parse($rawEnd);
        
        $secondsInDay = max(3600, $t1->diffInSeconds($t2));
        $this->line("secondsInDay calculado: $secondsInDay segundos = " . ($secondsInDay / 3600) . " horas");

        // Test con 1211760 segundos (valor problemático)
        $testValue = 1211760;
        $this->info("\n=== TEST CON 1211760 SEGUNDOS ===");
        
        $formatted1 = $businessTime->formatInterval($testValue);
        $formatted2 = $businessTime->formatCalendarInterval($testValue);
        
        $this->line("formatInterval(): $formatted1");
        $this->line("formatCalendarInterval(): $formatted2");
        
        // Calcular manualmente
        $this->info("\n=== CÁLCULO MANUAL ===");
        $d = floor($testValue / $secondsInDay);
        $rem = $testValue % $secondsInDay;
        $h = floor($rem / 3600);
        $rem %= 3600;
        $m = floor($rem / 60);
        $s = $rem % 60;
        
        $this->line("Desglose formatInterval():");
        $this->line("  $testValue / $secondsInDay = $d días");
        $this->line("  Resto: $h h, $m m, $s s");
        
        // Con 24 horas
        $d24 = intdiv($testValue, 86400);
        $rem24 = $testValue % 86400;
        $h24 = intdiv($rem24, 3600);
        $rem24 %= 3600;
        $m24 = intdiv($rem24, 60);
        $s24 = $rem24 % 60;
        
        $this->line("\nDesglose formatCalendarInterval() (24h/día):");
        $this->line("  $testValue / 86400 = $d24 días");
        $this->line("  Resto: $h24 h, $m24 m, $s24 s");
    }
}
