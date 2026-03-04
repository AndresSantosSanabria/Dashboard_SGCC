<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Festivo extends Model
{
    use HasFactory;

    protected $table = 'festivos';

    protected $fillable = ['fecha', 'descripcion'];

    protected $casts = [
        'fecha' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function esFestivo(\Carbon\Carbon $fecha): bool
    {
        return self::where('fecha', $fecha->format('Y-m-d'))->exists();
    }

    public static function esHabil(\Carbon\Carbon $fecha): bool
    {
        if ($fecha->isWeekend()) return false;
        return !self::esFestivo($fecha);
    }
}
