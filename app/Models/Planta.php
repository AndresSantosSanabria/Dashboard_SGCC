<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class Planta extends Model
{
    use HasFactory, Auditable;

    protected $table = 'plantas';

    protected $fillable = [
        'codigo',
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
        return $this->hasMany(Contrato::class, 'planta_id');
    }
}
