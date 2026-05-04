<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contratista extends Model
{
    use Auditable, HasFactory;

    protected $table = 'contratistas';

    protected $fillable = [
        'razon_social',
        'nit',
        'nit_blind_index', // For secure searching
        'tipo_persona',
        'representante_legal',
        'telefono',
        'email',
        'direccion_fisica',
        'cuenta_bancaria',
        'banco',
        'tipo_cuenta',
        'cdp_codigo',
        'es_activo',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cuenta_bancaria' => 'encrypted',
        'banco' => 'encrypted',
        'tipo_cuenta' => 'encrypted',
        'cdp_codigo' => 'encrypted',
    ];

    /**
     * PHP 8.4 Property Hook for NIT with Blind Index.
     * Automatically updates the blind index when NIT is set.
     */
    public string $nit {
        get {
            $value = $this->attributes['nit'] ?? '';
            try {
                return decrypt($value);
            } catch (\Exception $e) {
                return $value; // Return as is if not encrypted (e.g. during migration)
            }
        }
        set {
            $this->attributes['nit'] = encrypt($value);
            $this->attributes['nit_blind_index'] = $this->generateBlindIndex($value);
        }
    }

    /**
     * Generates a secure hash (Blind Index) for searching encrypted data.
     */
    private function generateBlindIndex(?string $value): ?string
    {
        if (empty($value)) return null;
        // Use a static salt from config for production
        $salt = config('app.key');
        return hash_hmac('sha256', (string)$value, $salt);
    }

    /**
     * Scope for secure searching by NIT.
     */
    public function scopeWhereNit($query, string $nit)
    {
        return $query->where('nit_blind_index', $this->generateBlindIndex($nit));
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return $this->razon_social ?: $this->representante_legal;
    }

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
