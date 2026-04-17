<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTimeLog extends Model
{
    use HasFactory;

    protected $table = 'tasks_time_log';

    protected $fillable = [
        'cuenta_cobro_id',
        'estado_id',
        'usuario_id',
        'start_time',
        'end_time',
        'duracion_segundos',
        'tipo_cierre',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duracion_segundos' => 'integer',
    ];

    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class);
    }

    public function estado()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
