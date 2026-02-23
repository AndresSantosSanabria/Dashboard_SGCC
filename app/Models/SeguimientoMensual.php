<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contrato;

class SeguimientoMensual extends Model
{
    use HasFactory;

    protected $table = 'seguimiento_mensual';

    protected $fillable = [
        'contrato_id',
        'mes',
        'fuente',
        'estado'
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}
