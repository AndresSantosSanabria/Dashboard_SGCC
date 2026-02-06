<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransicionEstado extends Model
{
    use HasFactory;

    protected $table = 'transiciones_estado';

    protected $fillable = [
        'cuenta_cobro_id',
        'estado_origen_id',
        'estado_destino_id',
        'usuario_accion_id',
        'comentarios',
        'tiempo_transcurrido_minutos',
        'accion'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class, 'cuenta_cobro_id');
    }

    public function estadoOrigen()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_origen_id');
    }

    public function estadoDestino()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_destino_id');
    }

    public function usuarioAccion()
    {
        return $this->belongsTo(Usuario::class, 'usuario_accion_id');
    }
}
