<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contratista extends Model
{
    use HasFactory;

    protected $table = 'contratistas';

    protected $fillable = [
        'razon_social',
        'nit',
        'representante_legal',
        'telefono',
        'email',
        'direccion_fisica',
        'entidad_salud',
        'entidad_pension',
        'entidad_arl',
        'es_activo'
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'contratista_id');
    }
}
