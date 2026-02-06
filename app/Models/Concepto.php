<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concepto extends Model
{
    use HasFactory;

    protected $table = 'conceptos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_activo'
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'concepto_id');
    }
}
