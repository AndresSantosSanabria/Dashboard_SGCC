<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoCampoValor extends Model
{
    use Auditable, HasFactory;

    protected $table = 'seguimiento_campo_valores';

    protected $fillable = [
        'seguimiento_campo_id',
        'contrato_id',
        'valor_texto',
        'valor_decimal',
        'valor_fecha',
        'valor_json',
    ];

    protected $casts = [
        'valor_decimal' => 'decimal:2',
        'valor_fecha' => 'date',
        'valor_json' => 'array',
    ];

    public function campo()
    {
        return $this->belongsTo(SeguimientoCampo::class, 'seguimiento_campo_id');
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function getValorMostradoAttribute(): string
    {
        if (!is_null($this->valor_texto) && $this->valor_texto !== '') {
            return (string) $this->valor_texto;
        }

        if (!is_null($this->valor_decimal)) {
            return (string) $this->valor_decimal;
        }

        if (!is_null($this->valor_fecha)) {
            return optional($this->valor_fecha)->format('Y-m-d') ?? '';
        }

        return '';
    }
}
