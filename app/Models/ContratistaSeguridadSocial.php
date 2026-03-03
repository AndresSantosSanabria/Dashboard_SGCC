<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratistaSeguridadSocial extends Model
{
    use Auditable, HasFactory;

    protected $table = 'contratista_seguridad_social';

    protected $fillable = [
        'contratista_id',
        'entidad_salud_id',
        'entidad_pension_id',
        'entidad_arl_id',
        'fecha_inicio',
        'fecha_fin',
        'es_vigente',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_vigente' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function contratista()
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function entidadSalud()
    {
        return $this->belongsTo(EntidadSeguridadSocial::class, 'entidad_salud_id');
    }

    public function entidadPension()
    {
        return $this->belongsTo(EntidadSeguridadSocial::class, 'entidad_pension_id');
    }

    public function entidadArl()
    {
        return $this->belongsTo(EntidadSeguridadSocial::class, 'entidad_arl_id');
    }

    // Scopes
    public function scopeVigente($query)
    {
        return $query->where('es_vigente', true);
    }
}
