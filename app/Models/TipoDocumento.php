<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'tipos_documento';

    protected $fillable = [
        'nombre',
        'es_obligatorio',
        'categoria',
        'es_activo',
    ];

    protected $casts = [
        'es_obligatorio' => 'boolean',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'tipo_documento_id');
    }
}
