<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBusinessDays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    /**
     * MODELO CONTRATO: La base de datos de gestión contractual.
     * 
     * Este modelo centraliza toda la información técnica y financiera de los 
     * contratos activos. Hemos optado por una fuerte tipificación de datos 
     * (Casts) para asegurar que el dinero ($monto_total) y las fechas se 
     * comporten de forma predecible en toda la aplicación.
     */
    use Auditable, HasFactory, HasBusinessDays;

    protected $table = 'contratos';

    /**
     * Fillable: Centralizamos los campos editables. 
     * Prestamos especial atención a los "User IDs" (abogado, contador, ops), 
     * permitiendo que el contrato no sea solo un registro, sino que tenga 
     * actores responsables asignados.
     */
    protected $fillable = [
        'numero_proceso',
        'numero_contrato',
        'modalidad_id',
        'contratista_id',
        'supervisor_id',
        'objeto',
        'monto_total',
        'planta_id',
        'concepto_id',
        'cdp_codigo',
        'fecha_inicio',
        'fecha_fin',
        'es_activo',
        'link_secop',
        'plazo_ejecucion',
        'secop_estado_contrato',
        'aprobado_y_pagado',
        'modificaciones_y_cierre',
        'saldo',
        'observacion_1_razon',
        'observacion_2_accion',
        'razon_no_liquidacion',
        'abogado_user_id',
        'contador_user_id',
        'ops_user_id',
        'abogado_responsable',
        'contador_responsable',
        'tipo_contratista',
        'no_planta',
        'concepto_precontractual',
    ];

    /**
     * Casts: Obligamos al sistema a tratar los montos como decimales de precisión.
     * Esto evita errores de redondeo en operaciones financieras críticas.
     */
    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- RELACIONES ESTRATÉGICAS ---

    /**
     * El contrato es el "padre" de la gestión financiera.
     * Un contrato puede tener múltiples RPs (Registros Presupuestales) 
     * y múltiples Cuentas de Cobro radicadas.
     */
    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }
    public function contratista()
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }
    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }
    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }

    public function registrosPresupuestales()
    {
        return $this->hasMany(RegistroPresupuestal::class, 'contrato_id');
    }
    public function cuentasCobro()
    {
        return $this->hasMany(CuentaCobro::class, 'contrato_id');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'contrato_id');
    }

    /**
     * Seguimientos Normalizados (Nuevo Esquema 3NF)
     * Estos métodos acceden a la información de ejecución mensual sin sobrecargar 
     * la tabla principal de contratos con columnas extras.
     */
    public function seguimientoMensual()
    {
        return $this->hasMany(SeguimientoMensual::class, 'contrato_id');
    }
    public function seguimientoRequisitos()
    {
        return $this->hasMany(SeguimientoRequisito::class, 'contrato_id');
    }

    // --- SCOPES DE GESTIÓN ---

    /**
     * Facilita el filtrado de contratos vigentes en el tiempo actual, 
     * algo crucial para los tableros de control operacional.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('es_activo', true);
    }

    public function scopeVigentes(\Illuminate\Database\Eloquent\Builder $query)
    {
        $hoy = now();
        return $query->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy);
    }
}
