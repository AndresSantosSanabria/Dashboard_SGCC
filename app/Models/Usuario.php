<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'user',
        'password',
        'rol_id',
        'es_activo',
        'fecha_inactivacion',
        'ultimo_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'fecha_inactivacion' => 'datetime',
        'ultimo_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function configuracionesModificadas()
    {
        return $this->hasMany(Configuracion::class, 'modificado_por_id');
    }

    public function documentosSubidos()
    {
        return $this->hasMany(Documento::class, 'subido_por_id');
    }

    public function cuentasCobroAsignadas()
    {
        return $this->hasMany(CuentaCobro::class, 'responsable_actual_id');
    }

    public function estadoBloquesCuenta()
    {
        return $this->hasMany(EstadoBloqueCuenta::class, 'responsable_id');
    }

    public function historialAcciones()
    {
        return $this->hasMany(HistorialWorkflow::class, 'usuario_accion_id');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'usuario_destino_id');
    }

    // Accessor para nombre completo
    public function getNombreCompletoAttribute()
    {
        return trim(
            $this->primer_nombre . ' ' .
                $this->segundo_nombre . ' ' .
                $this->primer_apellido . ' ' .
                $this->segundo_apellido
        );
    }

    public function isAdmin(): bool
    {
        return $this->rol && $this->rol->esAdmin();
    }

    public function puedeSerResponsableSap(): bool
    {
        return $this->rol && $this->rol->puedeSerResponsableSap();
    }

    public function puedeSerResponsableFac(): bool
    {
        return $this->rol && $this->rol->puedeSerResponsableFac();
    }

    // Scopes for filtering users
    public function scopeActivos($query)
    {
        return $query->where('es_activo', true);
    }

    public function scopeResponsablesSap($query)
    {
        return $query->whereHas('rol', function ($q) {
            $q->where('permisos->responsable_sap', true);
        })->where('es_activo', true);
    }

    public function scopeResponsablesFac($query)
    {
        return $query->whereHas('rol', function ($q) {
            $q->where('permisos->responsable_facturacion', true);
        })->where('es_activo', true);
    }
}
