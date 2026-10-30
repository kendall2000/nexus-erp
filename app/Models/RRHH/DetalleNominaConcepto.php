<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class DetalleNominaConcepto extends Model
{
    protected $table      = 'detalle_nomina_concepto';
    protected $primaryKey = 'id_linea';
    public $timestamps    = false;

    protected $fillable = [
        'id_detalle',
        'id_concepto',
        'tipo',
        'monto',
        'descripcion',
    ];

    protected $casts = [
        'monto' => 'decimal:4',
    ];

    public function detalleNomina()
    {
        return $this->belongsTo(DetalleNomina::class, 'id_detalle');
    }

    public function concepto()
    {
        return $this->belongsTo(ConceptoNomina::class, 'id_concepto');
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'INGRESO');
    }

    public function scopeDeducciones($query)
    {
        return $query->where('tipo', 'DEDUCCION');
    }
}