<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueWorkflow extends Model
{
    use HasFactory;

    protected $table = 'bloques_workflow';

    protected $fillable = [
        'nombre',
        'codigo',
        'orden',
        'sla_horas',
        'requiere_aprobacion',
        'responsable',
        'icono'
    ];

    protected $casts = [
        'requiere_aprobacion' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function estados()
    {
        return $this->hasMany(EstadoWorkflow::class, 'bloque_id');
    }

    public function metricasDiarias()
    {
        return $this->hasMany(MetricaDiaria::class, 'bloque_id');
    }
}
