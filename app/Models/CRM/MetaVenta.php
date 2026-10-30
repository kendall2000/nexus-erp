<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class MetaVenta extends Model
{
    protected $table      = 'meta_venta';
    protected $primaryKey = 'id_meta';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_empleado',
        'id_linea',
        'anio',
        'mes',
        'meta_valor',
        'moneda',
        'meta_contratos',
        'valor_logrado',
        'contratos_logrados',
    ];

    protected $casts = [
        'anio'               => 'integer',
        'mes'                => 'integer',
        'meta_valor'         => 'decimal:4',
        'valor_logrado'      => 'decimal:4',
        'meta_contratos'     => 'integer',
        'contratos_logrados' => 'integer',
        'updated_at'         => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function empleado()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_empleado');
    }

    public function getPorcentajeCumplimientoAttribute(): float
    {
        if ($this->meta_valor == 0) return 0;
        return round(($this->valor_logrado / $this->meta_valor) * 100, 2);
    }

    public function scopePorPeriodo($query, int $anio, int $mes)
    {
        return $query->where('anio', $anio)->where('mes', $mes);
    }

    public function scopePorVendedor($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }
}