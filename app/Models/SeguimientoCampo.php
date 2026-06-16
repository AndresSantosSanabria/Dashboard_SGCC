<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoCampo extends Model
{
    use Auditable, HasFactory;

    protected $table = 'seguimiento_campos';

    protected $fillable = [
        'clave',
        'etiqueta',
        'tipo',
        'orden',
        'es_activo',
        'configuracion',
    ];

    protected $casts = [
        'orden' => 'integer',
        'es_activo' => 'boolean',
        'configuracion' => 'array',
    ];

    public function valores()
    {
        return $this->hasMany(SeguimientoCampoValor::class, 'seguimiento_campo_id');
    }
}
