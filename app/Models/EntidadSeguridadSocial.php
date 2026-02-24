<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class EntidadSeguridadSocial extends Model
{
    use HasFactory, Auditable;

    protected $table = 'entidades_seguridad_social';

    protected $fillable = [
        'nombre',
        'tipo',
        'codigo',
        'es_activa',
    ];

    protected $casts = [
        'es_activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function contratistasComoSalud()
    {
        return $this->hasMany(ContratistaSeguridadSocial::class, 'entidad_salud_id');
    }

    public function contratistasComoPension()
    {
        return $this->hasMany(ContratistaSeguridadSocial::class, 'entidad_pension_id');
    }

    public function contratistasComoArl()
    {
        return $this->hasMany(ContratistaSeguridadSocial::class, 'entidad_arl_id');
    }

    // Scopes
    public function scopeSalud($query)
    {
        return $query->where('tipo', 'SALUD');
    }

    public function scopePension($query)
    {
        return $query->where('tipo', 'PENSION');
    }

    public function scopeArl($query)
    {
        return $query->where('tipo', 'ARL');
    }
}
