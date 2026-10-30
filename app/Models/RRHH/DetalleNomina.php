<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class DetalleNomina extends Model
{
    protected $table      = 'detalle_nomina';
    protected $primaryKey = 'id_detalle';
    public $timestamps    = true;

    protected $fillable = [
        'id_periodo',
        'id_empleado',
        'id_empresa',
        'cargo_snapshot',
        'salario_base',
        'dias_trabajados',
        'horas_extra',
        'total_ingresos',
        'total_deducciones',
        'liquido_pagar',
        'cuota_igss_emp',
        'cuota_igss_pat',
        'isr_retenido',
        'estado_pago',
        'numero_cheque',
        'banco_destino',
        'cuenta_destino',
        'fecha_pago',
    ];

    protected $casts = [
        'salario_base'      => 'decimal:4',
        'dias_trabajados'   => 'decimal:2',
        'horas_extra'       => 'decimal:2',
        'total_ingresos'    => 'decimal:4',
        'total_deducciones' => 'decimal:4',
        'liquido_pagar'     => 'decimal:4',
        'cuota_igss_emp'    => 'decimal:4',
        'cuota_igss_pat'    => 'decimal:4',
        'isr_retenido'      => 'decimal:4',
        'fecha_pago'        => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function periodo()
    {
        return $this->belongsTo(PeriodoNomina::class, 'id_periodo');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function conceptos()
    {
        return $this->hasMany(DetalleNominaConcepto::class, 'id_detalle');
    }

    public function ingresos()
    {
        return $this->hasMany(DetalleNominaConcepto::class, 'id_detalle')
                    ->where('tipo', 'INGRESO');
    }

    public function deducciones()
    {
        return $this->hasMany(DetalleNominaConcepto::class, 'id_detalle')
                    ->where('tipo', 'DEDUCCION');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function marcarComoPagado(
        string $numeroCheque,
        string $banco,
        string $cuenta
    ): void {
        $this->update([
            'estado_pago'   => 'PAGADO',
            'numero_cheque' => $numeroCheque,
            'banco_destino' => $banco,
            'cuenta_destino'=> $cuenta,
            'fecha_pago'    => today(),
        ]);
    }

    public function recalcularTotales(): void
    {
        $ingresos    = $this->ingresos()->sum('monto');
        $deducciones = $this->deducciones()->sum('monto');

        $this->update([
            'total_ingresos'    => $ingresos,
            'total_deducciones' => $deducciones,
            'liquido_pagar'     => max(0, $ingresos - $deducciones),
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorPeriodo($query, $idPeriodo)
    {
        return $query->where('id_periodo', $idPeriodo);
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }

    public function scopePendientesPago($query)
    {
        return $query->whereIn('estado_pago', ['CALCULADO', 'APROBADO']);
    }
}