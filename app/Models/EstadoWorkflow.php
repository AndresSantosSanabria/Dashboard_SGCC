<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoWorkflow extends Model
{
    use HasFactory;

    protected $table = 'estados_workflow';

    protected $fillable = [
        'bloque_id',
        'nombre',
        'codigo',
        'tipo',
        'es_inicial',
        'es_final',
        'permite_rechazo',
        'color_hex'
    ];

    protected $casts = [
        'es_inicial' => 'boolean',
        'es_final' => 'boolean',
        'permite_rechazo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function bloque()
    {
        return $this->belongsTo(BloqueWorkflow::class, 'bloque_id');
    }

    public function transicionesOrigen()
    {
        return $this->hasMany(TransicionEstado::class, 'estado_origen_id');
    }

    public function transicionesDestino()
    {
        return $this->hasMany(TransicionEstado::class, 'estado_destino_id');
    }

    public function scopeInicial($query)
    {
        return $query->where('es_inicial', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('es_final', true);
    }
}
