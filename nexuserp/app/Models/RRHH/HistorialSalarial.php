<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class HistorialSalarial extends Model
{
    protected $table      = 'historial_salarial';
    protected $primaryKey = 'id_historial';
    public $timestamps    = false;

    protected $fillable = [
        'id_empleado',
        'id_cargo',
        'salario_anterior',
        'salario_nuevo',
        'moneda',
        'tipo_cambio',
        'fecha_efectiva',
        'motivo',
        'aprobado_por',
        'created_by',
    ];

    protected $casts = [
        'salario_anterior' => 'decimal:4',
        'salario_nuevo'    => 'decimal:4',
        'fecha_efectiva'   => 'date',
        'created_at'       => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function getPorcentajeAumentoAttribute(): ?float
    {
        if (!$this->salario_anterior || $this->salario_anterior == 0) return null;
        return round((($this->salario_nuevo - $this->salario_anterior) / $this->salario_anterior) * 100, 2);
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado)->orderBy('fecha_efectiva', 'desc');
    }
}