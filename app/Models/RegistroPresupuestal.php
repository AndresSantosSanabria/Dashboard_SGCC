<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class RegistroPresupuestal extends Model
{
    use HasFactory, Auditable;

    protected $table = 'registros_presupuestales';

    protected $fillable = [
        'contrato_id',
        'numero_rp',
        'fecha_rp',
        'valor_rp',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_rp' => 'date',
        'valor_rp' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }
}
