<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracion;

class AlertaConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'clave' => 'ALERTA_ESTANCAMIENTO_MINUTOS',
                'valor' => '20',
                'tipo_dato' => 'INT',
                'descripcion' => 'Tiempo límite en minutos para considerar un contrato estancado en un estado.',
            ],
            [
                'clave' => 'HORARIO_LABORAL_INICIO',
                'valor' => '08:00',
                'tipo_dato' => 'STRING',
                'descripcion' => 'Hora de inicio de la jornada laboral (HH:mm).',
            ],
            [
                'clave' => 'HORARIO_LABORAL_FIN',
                'valor' => '17:00',
                'tipo_dato' => 'STRING',
                'descripcion' => 'Hora de fin de la jornada laboral (HH:mm).',
            ],
            [
                'clave' => 'ALERTA_ESTANCAMIENTO_ACTIVA',
                'valor' => 'true',
                'tipo_dato' => 'BOOL',
                'descripcion' => 'Activa o desactiva el sistema de alertas por estancamiento.',
            ],
        ];

        foreach ($configs as $config) {
            Configuracion::updateOrCreate(['clave' => $config['clave']], $config);
        }
    }
}
