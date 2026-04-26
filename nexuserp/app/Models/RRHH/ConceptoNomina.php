<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class ConceptoNomina extends Model
{
    protected $table      = 'concepto_nomina';
    protected $primaryKey = 'id_concepto';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'codigo',
        'nombre',
        'tipo',
        'afecta_igss',
        'afecta_isr',
        'es_fijo',
        'formula',
        'activo',
    ];

    protected $casts = [
        'afecta_igss' => 'boolean',
        'afecta_isr'  => 'boolean',
        'es_fijo'     => 'boolean',
        'activo'      => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function lineasNomina()
    {
        return $this->hasMany(DetalleNominaConcepto::class, 'id_concepto');
    }

    public function esIngreso(): bool
    {
        return $this->tipo === 'INGRESO';
    }

    public function esDeduccion(): bool
    {
        return $this->tipo === 'DEDUCCION';
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'INGRESO');
    }

    public function scopeDeducciones($query)
    {
        return $query->where('tipo', 'DEDUCCION');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}