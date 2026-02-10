<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas';

    protected $fillable = [
        'cuenta_cobro_id',
        'nivel',
        'tipo_alerta',
        'mensaje',
        'leida',
        'usuario_destino_id',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class, 'cuenta_cobro_id');
    }

    public function usuarioDestino()
    {
        return $this->belongsTo(Usuario::class, 'usuario_destino_id');
    }

    // Scopes
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeLeidas($query)
    {
        return $query->where('leida', true);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeCriticas($query)
    {
        return $query->where('nivel', 'CRITICAL');
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_destino_id', $usuarioId);
    }
}
