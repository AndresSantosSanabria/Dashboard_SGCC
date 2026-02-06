<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'permisos',
        'es_activo'
    ];

    protected $casts = [
        'permisos' => 'array',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relaciones
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
