<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransicionPermitida extends Model
{
    use HasFactory;

    protected $table = 'transiciones_permitidas';

    protected $fillable = [
        'estado_origen_id',
        'estado_destino_id',
        'requiere_comentario',
        'requiere_documento',
        'accion',
        'descripcion',
        'es_activa',
    ];

    protected $casts = [
        'requiere_comentario' => 'boolean',
        'requiere_documento' => 'boolean',
        'es_activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function estadoOrigen()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_origen_id');
    }

    public function estadoDestino()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_destino_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('es_activa', true);
    }
}
