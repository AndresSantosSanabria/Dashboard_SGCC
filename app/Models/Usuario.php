<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    // Ajustado a singular según tu script SQL 
    protected $table = 'usuarios';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'usuario',
        'password',
        'rol_id',
        'es_activo',
        'fecha_inactivacion',
        'ultimo_login'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'fecha_inactivacion' => 'datetime',
        'ultimo_login' => 'datetime',
        'password' => 'hashed',
    ];

    // --- MÉTODOS DE AUTENTICACIÓN PERSONALIZADOS ---


    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getAuthIdentifierName()
    {
        return 'usuario';
    }

    // --- RELACIONES BASADAS EN TU ESQUEMA  ---

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function configuracionesModificadas()
    {
        return $this->hasMany(Configuracion::class, 'modificado_por_id');
    }

    public function cuentasCobroResponsable()
    {
        return $this->hasMany(CuentaCobro::class, 'responsable_actual_id');
    }

    public function documentosSubidos()
    {
        return $this->hasMany(Documento::class, 'subido_por_id');
    }

    public function transicionesRealizadas()
    {
        return $this->hasMany(TransicionEstado::class, 'usuario_accion_id');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'usuario_destino_id');
    }

    // --- ACCESSORS ---

    public function getNombreCompletoAttribute()
    {
        $nombre = trim($this->primer_nombre . ' ' . $this->segundo_nombre);
        $apellido = trim($this->primer_apellido . ' ' . $this->segundo_apellido);
        return trim($nombre . ' ' . $apellido);
    }
}
