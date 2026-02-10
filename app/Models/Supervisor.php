<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;

    protected $table = 'supervisores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'cargo',
        'email',
        'telefono',
        'es_activo',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'supervisor_id');
    }

    // Accessor
    public function getNombreCompletoAttribute()
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }
}
