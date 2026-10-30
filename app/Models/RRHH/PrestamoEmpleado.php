<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class PrestamoEmpleado extends Model
{
    protected $table      = 'prestamo_empleado';
    protected $primaryKey = 'id_prestamo';
    public $timestamps    = true;

    protected $fillable = [
        'id_empleado',
        'id_empresa',
        'monto_total',
        'monto_pendiente',
        'cuota_quincenal',
        'moneda',
        'fecha_otorgamiento',
        'motivo',
        'estado',
        'aprobado_por',
    ];

    protected $casts = [
        'monto_total'        => 'decimal:4',
        'monto_pendiente'    => 'decimal:4',
        'cuota_quincenal'    => 'decimal:4',
        'fecha_otorgamiento' => 'date',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function getPorcentajePagadoAttribute(): float
    {
        if ($this->monto_total == 0) return 0;
        $pagado = $this->monto_total - $this->monto_pendiente;
        return round(($pagado / $this->monto_total) * 100, 2);
    }

    public function aplicarCuota(): void
    {
        $nuevo = max(0, $this->monto_pendiente - $this->cuota_quincenal);
        $this->update([
            'monto_pendiente' => $nuevo,
            'estado'          => $nuevo <= 0 ? 'LIQUIDADO' : 'ACTIVO',
        ]);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }
}