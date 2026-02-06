<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'tipo_dato',
        'descripcion',
        'modificado_por_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function modificadoPor()
    {
        return $this->belongsTo(Usuario::class, 'modificado_por_id');
    }

    public function getValorTipadoAttribute()
    {
        return match($this->tipo_dato) {
            'INT' => (int) $this->valor,
            'BOOL' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'JSON' => json_decode($this->valor, true),
            default => $this->valor
        };
    }
}
