<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class PrestacionLaboral extends Model
{
    protected $table      = 'prestacion_laboral';
    protected $primaryKey = 'id_prestacion';
    public $timestamps    = false;

    protected $fillable = [
        'id_empleado',
        'id_empresa',
        'id_periodo',
        'tipo',
        'periodo_calculo',
        'monto_base',
        'monto_calculado',
        'dias_calculados',
        'estado',
        'fecha_pago',
    ];

    protected $casts = [
        'monto_base'      => 'decimal:4',
        'monto_calculado' => 'decimal:4',
        'dias_calculados' => 'decimal:2',
        'fecha_pago'      => 'date',
        'created_at'      => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function periodoNomina()
    {
        return $this->belongsTo(PeriodoNomina::class, 'id_periodo');
    }

    // Cálculo Bono 14 Guatemala: salario / 365 * días trabajados (jul-jun)
    public static function calcularBono14(float $salarioBase, int $diasTrabajados): float
    {
        return round(($salarioBase / 365) * $diasTrabajados, 4);
    }

    // Cálculo Aguinaldo Guatemala: salario / 365 * días trabajados (dic-nov)
    public static function calcularAguinaldo(float $salarioBase, int $diasTrabajados): float
    {
        return round(($salarioBase / 365) * $diasTrabajados, 4);
    }

    // Cálculo Indemnización Guatemala: salario / 365 * días trabajados
    public static function calcularIndemnizacion(float $salarioBase, int $diasTrabajados): float
    {
        return round(($salarioBase / 365) * $diasTrabajados, 4);
    }

    public function marcarComoPagado(): void
    {
        $this->update([
            'estado'     => 'PAGADO',
            'fecha_pago' => today(),
        ]);
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorPeriodo($query, string $periodo)
    {
        return $query->where('periodo_calculo', $periodo);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['CALCULADO', 'APROBADO']);
    }
}