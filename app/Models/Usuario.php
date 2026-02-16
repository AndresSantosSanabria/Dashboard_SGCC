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
        'permisos',
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
        'permisos' => 'array',
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

    public function tienePermiso(string $permiso): bool
    {
        // 1. Check if user has explicit permission defined
        if (isset($this->permisos[$permiso])) {
            return (bool) $this->permisos[$permiso];
        }

        // 2. Administrators have all permissions by default
        // We check 'es_admin' specifically to prevent infinite loops if isAdmin() is used
        if ($permiso !== 'es_admin' && $this->isAdmin()) {
            return true;
        }

        // 3. Fall back to role permissions
        return $this->rol ? $this->rol->tienePermiso($permiso) : false;
    }

    public function isAdmin(): bool
    {
        return $this->tienePermiso('es_admin');
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

    public function puedeEditarWorkflow(): bool
    {
        return $this->tienePermiso('editar_workflow');
    }

    /**
     * Check if user is restricted to seeing only their assigned accounts
     */
    public function verSoloAsignados(): bool
    {
        // 1. Explicit individual check
        if (isset($this->permisos['ver_solo_asignados'])) {
            return (bool) $this->permisos['ver_solo_asignados'];
        }

        // 2. Admin bypass (admins see everything by default)
        if ($this->isAdmin()) {
            return false;
        }

        // 3. Fallback to role
        return $this->rol ? $this->rol->tienePermiso('ver_solo_asignados') : false;
    }

    /**
     * Get the blocks the user is allowed to see/manage
     * Returns true if all blocks, or an array of block codes
     */
    public function bloquesPermitidos()
    {
        // 1. Check individual user permisos
        $bloquesUsuario = $this->permisos['bloques_permitidos'] ?? null;

        // 2. Admin bypass (admins see all blocks by default)
        if ($this->isAdmin()) {
            return true;
        }

        // 3. Fallback to role permisos if individual is null OR if individual is 'true' but we want to check role restrictions
        // Logic: Individual ARRAY (explicit restriction) > Role (any) > Individual TRUE (all) > TRUE (default)

        // If user has specific individual blocks assigned, use those (highest priority)
        if (is_array($bloquesUsuario)) {
            $filteredBloques = array_filter($bloquesUsuario, function ($b) {
                return $b !== null && $b !== '' && $b !== false;
            });
            return array_values($filteredBloques);
        }

        // If no individual array, check the role
        if ($this->rol && isset($this->rol->permisos['bloques_permitidos'])) {
            $bloquesRole = $this->rol->permisos['bloques_permitidos'];

            if ($bloquesRole === true) {
                return true;
            }

            if (is_array($bloquesRole)) {
                $filteredBloques = array_filter($bloquesRole, function ($b) {
                    return $b !== null && $b !== '' && $b !== false;
                });
                return array_values($filteredBloques);
            }
        }

        // If no role restrictions AND no individual array, fallback to individual 'true' or default true
        if ($bloquesUsuario === true) {
            return true;
        }

        // 4. Default: all blocks
        return true;
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
                $q->where('permisos->es_admin', true)
                    ->orWhere('permisos->responsable_sap', true)
                    ->orWhere(function ($sq) {
                        $sq->whereNull('permisos->responsable_sap')
                            ->whereNull('permisos->es_admin')
                            ->whereHas('rol', function ($r) {
                                $r->where('permisos->responsable_sap', true)
                                    ->orWhere('permisos->es_admin', true);
                            });
                    });
            });
    }

    public function scopeResponsablesFac($query)
    {
        return $query->where('es_activo', true)
            ->where(function ($q) {
                $q->where('permisos->es_admin', true)
                    ->orWhere('permisos->responsable_facturacion', true)
                    ->orWhere(function ($sq) {
                        $sq->whereNull('permisos->responsable_facturacion')
                            ->whereNull('permisos->es_admin')
                            ->whereHas('rol', function ($r) {
                                $r->where('permisos->responsable_facturacion', true)
                                    ->orWhere('permisos->es_admin', true);
                            });
                    });
            });
    }
}
