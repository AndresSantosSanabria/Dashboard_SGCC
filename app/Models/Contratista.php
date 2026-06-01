<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
     * Decrypts NIT on read and keeps the blind index updated on write.
     */
    public function getNitAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value; // Return as is if not encrypted (e.g. during migration)
        }
    }

    public function setNitAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['nit'] = $value;
            if (Schema::hasColumn($this->table, 'nit_blind_index')) {
                $this->attributes['nit_blind_index'] = null;
            }

            return;
        }

        $this->attributes['nit'] = encrypt($value);
        if (Schema::hasColumn($this->table, 'nit_blind_index')) {
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
        if (Schema::hasColumn($this->table, 'nit_blind_index')) {
            return $query->where('nit_blind_index', $this->generateBlindIndex($nit));
        }

        // Fallback seguro para entornos donde la migración de blind index aún no existe.
        // Como el NIT puede estar cifrado, no podemos filtrar por SQL directo;
        // cargamos los registros y comparamos en memoria para evitar el error fatal.
        $matchingIds = $query->get()->filter(function (self $contratista) use ($nit) {
            return (string) $contratista->nit === (string) $nit;
        })->pluck('id');

        return $query->whereIn('id', $matchingIds);
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
