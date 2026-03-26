<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use Auditable, HasFactory;

    protected $table = 'documentos';

    protected $fillable = [
        'contrato_id',
        'tipo_documento_id',
        'nombre_archivo',
        'url_almacenamiento',
        'estado_validacion',
        'subido_por_id',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function subidoPor()
    {
        return $this->belongsTo(Usuario::class, 'subido_por_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado_validacion', 'PENDIENTE');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado_validacion', 'APROBADO');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado_validacion', 'RECHAZADO');
    }

    public function scopeUltimaVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }
}
