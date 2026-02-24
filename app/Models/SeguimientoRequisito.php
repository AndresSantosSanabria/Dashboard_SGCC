<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line

use App\Traits\Auditable;

class SeguimientoRequisito extends Model
{
    use HasFactory, Auditable;

    protected $table = 'seguimiento_requisitos';

    protected $fillable = [
        'contrato_id',
        'nombre',
        'estado'
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}
