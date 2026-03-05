<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Auditable, HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_activo',
        'tipo',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    public function tienePermiso(string $slug): bool
    {
        return $this->permisos()->where('slug', $slug)->exists();
    }

    public function esAdmin(): bool
    {
        return $this->tienePermiso('es_admin') || $this->nombre === 'Administrador';
    }

    public function puedeSerResponsableSap(): bool
    {
        return $this->tienePermiso('responsable_sap');
    }

    public function puedeSerResponsableFac(): bool
    {
        return $this->tienePermiso('responsable_facturacion');
    }
}
