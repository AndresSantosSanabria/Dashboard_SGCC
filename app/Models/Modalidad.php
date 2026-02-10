<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    use HasFactory;

    protected $table = 'modalidades';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_activa',
    ];

    protected $casts = [
        'es_activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'modalidad_id');
    }
}
