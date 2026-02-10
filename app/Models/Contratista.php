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
        'tipo_persona',
        'representante_legal',
        'telefono',
        'email',
        'direccion_fisica',
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
        return $this->hasMany(Contrato::class, 'contratista_id');
    }

    public function seguridadSocial()
    {
        return $this->hasMany(ContratistaSeguridadSocial::class, 'contratista_id');
    }

    public function seguridadSocialVigente()
    {
        return $this->hasOne(ContratistaSeguridadSocial::class, 'contratista_id')
            ->where('es_vigente', true)
            ->latest();
    }
}
