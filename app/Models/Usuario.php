<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

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

    public function individualPermissions()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permiso');
    }

    public function tienePermiso(string $slug): bool
    {
        // 1. Check if user has explicit individual permission
        if ($this->individualPermissions()->where('slug', $slug)->exists()) {
            return true;
        }

        // 2. Administrators have all permissions
        if ($slug !== 'es_admin' && $this->isAdmin()) {
            return true;
        }

        // 3. Fall back to role permissions
        return $this->rol ? $this->rol->tienePermiso($slug) : false;
    }

    public function isAdmin(): bool
    {
        return $this->rol?->nombre === 'Administrador' || $this->tienePermiso('es_admin');
    }

    public function puedeSerResponsableSap(): bool
    {
        return $this->tienePermiso('responsable_sap');
    }

    public function puedeSerResponsableFac(): bool
    {
        return $this->tienePermiso('responsable_facturacion');
    }

    public function puedeAccederDashboard(): bool
    {
        return $this->tienePermiso('acceder_dashboard');
    }

    public function puedeAccederWorkflow(): bool
    {
        return $this->tienePermiso('acceder_workflow');
    }

    public function puedeAccederConsolidado(): bool
    {
        return $this->tienePermiso('acceder_consolidado') || $this->tienePermiso('acceder_dashboard');
    }

    public function puedeAccederSeguimiento(): bool
    {
        return $this->tienePermiso('acceder_seguimiento') || $this->isAdmin() || $this->puedeAccederDashboard();
    }

    public function puedeAccederAnalitica(): bool
    {
        return $this->tienePermiso('acceder_analitica') || $this->isAdmin();
    }

    public function puedeEditarWorkflow(): bool
    {
        return $this->tienePermiso('editar_workflow');
    }

    public function verSoloAsignados(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }
        return $this->tienePermiso('ver_solo_asignados');
    }

    public function bloquesPermitidos()
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Para simplificar esta implementación inicial en 3NF, 
        // buscamos permisos con el patrón 'acceso_bloque_*'
        $permisos = $this->individualPermissions()
            ->where('slug', 'like', 'acceso_bloque_%')
            ->pluck('slug')
            ->map(fn($s) => str_replace('acceso_bloque_', '', $s))
            ->toArray();

        if (empty($permisos) && $this->rol) {
            $permisos = $this->rol->permisos()
                ->where('slug', 'like', 'acceso_bloque_%')
                ->pluck('slug')
                ->map(fn($s) => str_replace('acceso_bloque_', '', $s))
                ->toArray();
        }

        return !empty($permisos) ? $permisos : true;
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
        return implode(' ', array_filter([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido,
        ]));
    }

    // Scopes for filtering users
    public function scopeActivos($query)
    {
        return $query->where('es_activo', true);
    }

    public function scopeResponsablesSap($query)
    {
        return $query->where('es_activo', true)
            ->where(function ($q) {
                $q->whereHas('individualPermissions', function ($sq) {
                    $sq->where('slug', 'responsable_sap');
                })->orWhereHas('rol.permisos', function ($sq) {
                    $sq->where('slug', 'responsable_sap');
                });
            });
    }

    public function scopeResponsablesFac($query)
    {
        return $query->where('es_activo', true)
            ->where(function ($q) {
                $q->whereHas('individualPermissions', function ($sq) {
                    $sq->where('slug', 'responsable_facturacion');
                })->orWhereHas('rol.permisos', function ($sq) {
                    $sq->where('slug', 'responsable_facturacion');
                });
            });
    }
}
