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
        'usuario_destino_id'
    ];

    protected $casts = [
        'leida' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class, 'cuenta_cobro_id');
    }

    public function usuarioDestino()
    {
        return $this->belongsTo(Usuario::class, 'usuario_destino_id');
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }
}
