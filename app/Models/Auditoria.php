<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id',
        'tabla_afectada',
        'registro_id',
        'accion',
        'payload_anterior',
        'payload_nuevo',
        'ip_origen',
        'user_agent',
    ];

    protected $casts = [
        'registro_id' => 'integer',
        'payload_anterior' => 'array',
        'payload_nuevo' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Scopes
    public function scopePorTabla($query, $tabla)
    {
        return $query->where('tabla_afectada', $tabla);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }
}
