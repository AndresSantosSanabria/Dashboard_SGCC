<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoMensual extends Model
{
    use Auditable, HasFactory;

    protected $table = 'seguimiento_mensual';

    protected $fillable = [
        'contrato_id',
        'mes',
        'anio',
        'fuente',
        'estado',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}
