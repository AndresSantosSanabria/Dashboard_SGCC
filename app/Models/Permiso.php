<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'modulo',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuario_permiso');
    }
}
