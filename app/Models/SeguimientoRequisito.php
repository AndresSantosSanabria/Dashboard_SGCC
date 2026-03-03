<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line
use Illuminate\Database\Eloquent\Model;

class SeguimientoRequisito extends Model
{
    use Auditable, HasFactory;

    protected $table = 'seguimiento_requisitos';

    protected $fillable = [
        'contrato_id',
        'nombre',
        'estado',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}
