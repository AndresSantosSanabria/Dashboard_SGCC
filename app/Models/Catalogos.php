<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    use HasFactory;

    protected $table = 'modalidades';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_activa',
    ];

    protected $casts = [
        'es_activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'modalidad_id');
    }
}

class Concepto extends Model
{
    use HasFactory;

    protected $table = 'conceptos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_activo',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'concepto_id');
    }
}

class Planta extends Model
{
    use HasFactory;

    protected $table = 'plantas';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'es_activa',
    ];

    protected $casts = [
        'es_activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'planta_id');
    }
}

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
}
