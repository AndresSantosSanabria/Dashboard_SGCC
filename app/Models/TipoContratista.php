<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoContratista extends Model
{
    use HasFactory;

    protected $table = 'tipos_contratista';

    protected $fillable = [
        'nombre',
        'es_activo',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contratistas()
    {
        return $this->hasMany(Contratista::class, 'tipo_contratista_id');
    }
}
