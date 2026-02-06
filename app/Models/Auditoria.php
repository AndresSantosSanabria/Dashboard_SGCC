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
        'user_agent'
    ];

    protected $casts = [
        'payload_anterior' => 'array',
        'payload_nuevo' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
