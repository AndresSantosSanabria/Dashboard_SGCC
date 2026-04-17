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
        'modificado_por_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function modificadoPor()
    {
        return $this->belongsTo(Usuario::class, 'modificado_por_id');
    }

    // Accessor para obtener valor tipado
    public function getValorTipadoAttribute()
    {
        switch ($this->tipo_dato) {
            case 'INT':
                return (int) $this->valor;
            case 'BOOL':
                return filter_var($this->valor, FILTER_VALIDATE_BOOLEAN);
            case 'JSON':
                return json_decode($this->valor, true);
            default:
                return $this->valor;
        }
    }

    /**
     * Obtiene el valor de una configuración por su clave
     */
    public static function getValor($clave, $default = null)
    {
        $config = self::where('clave', $clave)->first();
        return $config ? $config->valor_tipado : $default;
    }
}
