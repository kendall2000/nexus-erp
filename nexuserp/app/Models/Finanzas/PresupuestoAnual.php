<?php
// app/Models/Finanzas/PresupuestoAnual.php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class PresupuestoAnual extends Model
{
    protected $table      = 'presupuesto_anual';
    protected $primaryKey = 'id_presupuesto';
    public    $timestamps = true;

    // ── Constantes para state machine ──────────────────────────
    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_CERRADO  = 'CERRADO';

    public const MESES = [
        1 => 'enero',     2 => 'febrero',  3 => 'marzo',     4 => 'abril',
        5 => 'mayo',      6 => 'junio',    7 => 'julio',     8 => 'agosto',
        9 => 'septiembre',10 => 'octubre', 11 => 'noviembre',12 => 'diciembre',
    ];

    protected $fillable = [
        'id_empresa', 'id_centro', 'id_cuenta', 'anio', 'moneda',
        'pre_enero','pre_febrero','pre_marzo','pre_abril','pre_mayo','pre_junio',
        'pre_julio','pre_agosto','pre_septiembre','pre_octubre','pre_noviembre','pre_diciembre',
        'eje_enero','eje_febrero','eje_marzo','eje_abril','eje_mayo','eje_junio',
        'eje_julio','eje_agosto','eje_septiembre','eje_octubre','eje_noviembre','eje_diciembre',
        'total_presupuestado', 'total_ejecutado',
        'estado', 'aprobado_por', 'fecha_aprobacion',
        'cerrado_por', 'fecha_cierre',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'anio'                => 'integer',
        'total_presupuestado' => 'decimal:4',
        'total_ejecutado'     => 'decimal:4',
        'fecha_aprobacion'    => 'datetime',
        'fecha_cierre'        => 'datetime',
    ];

    // ── Relaciones ─────────────────────────────────────────────
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function centroCosto()
    {
        return $this->belongsTo(\App\Models\Core\CentroCosto::class, 'id_centro');
    }

    public function cuentaContable()
    {
        return $this->belongsTo(\App\Models\Core\CuentaContable::class, 'id_cuenta');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'aprobado_por');
    }

    public function cerradoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'cerrado_por');
    }

    // ── Hooks: recalcular totales automáticamente ──────────────
    protected static function booted(): void
    {
        static::saving(function (self $p) {
            $p->total_presupuestado = $p->calcularTotalPresupuestado();
            $p->total_ejecutado     = $p->calcularTotalEjecutado();
        });
    }

    // ── Helpers de cálculo ─────────────────────────────────────
    public function calcularTotalPresupuestado(): float
    {
        return collect(self::MESES)
            ->sum(fn($m) => (float) $this->{"pre_{$m}"});
    }

    public function calcularTotalEjecutado(): float
    {
        return collect(self::MESES)
            ->sum(fn($m) => (float) $this->{"eje_{$m}"});
    }

    public function getSaldoDisponibleAttribute(): float
    {
        return $this->total_presupuestado - $this->total_ejecutado;
    }

    public function getPorcentajeEjecucionAttribute(): float
    {
        if ($this->total_presupuestado <= 0) return 0;
        return round(($this->total_ejecutado / $this->total_presupuestado) * 100, 2);
    }

    public function getEstadoEjecucionAttribute(): string
    {
        $pct = $this->porcentaje_ejecucion;
        return match (true) {
            $pct > 100 => 'SOBRE_EJECUTADO',
            $pct >= 90 => 'CRITICO',
            $pct >= 75 => 'ALERTA',
            default    => 'NORMAL',
        };
    }

    // ── State Machine ──────────────────────────────────────────
    public function puedeEditarse(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR;
    }

    public function puedeAprobarse(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR
            && $this->total_presupuestado > 0;
    }

    public function puedeCerrarse(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    public function aprobar(int $idUsuario): void
    {
        if (!$this->puedeAprobarse()) {
            throw new \DomainException('El presupuesto no puede ser aprobado en su estado actual.');
        }

        $this->update([
            'estado'           => self::ESTADO_APROBADO,
            'aprobado_por'     => $idUsuario,
            'fecha_aprobacion' => now(),
        ]);
    }

    public function cerrar(int $idUsuario): void
    {
        if (!$this->puedeCerrarse()) {
            throw new \DomainException('Solo presupuestos aprobados pueden cerrarse.');
        }

        $this->update([
            'estado'       => self::ESTADO_CERRADO,
            'cerrado_por'  => $idUsuario,
            'fecha_cierre' => now(),
        ]);
    }

    /**
     * Registra ejecución en un mes específico (manual o automático).
     * Acepta el número de mes (1-12) o el nombre del mes.
     */
    public function registrarEjecucion(int $mes, float $monto): void
    {
        if (!isset(self::MESES[$mes])) {
            throw new \InvalidArgumentException("Mes inválido: {$mes}");
        }

        if (!in_array($this->estado, [self::ESTADO_APROBADO])) {
            throw new \DomainException('Solo presupuestos aprobados aceptan ejecución.');
        }

        $this->increment("eje_" . self::MESES[$mes], $monto);
    }

    // ── Scopes ─────────────────────────────────────────────────
    public function scopePorEmpresa($q, $idEmpresa)
    {
        return $q->where('id_empresa', $idEmpresa);
    }

    public function scopePorAnio($q, int $anio)
    {
        return $q->where('anio', $anio);
    }

    public function scopePorCentro($q, $idCentro)
    {
        return $q->where('id_centro', $idCentro);
    }

    public function scopePorEstado($q, string $estado)
    {
        return $q->where('estado', $estado);
    }

    public function scopeAprobados($q)
    {
        return $q->where('estado', self::ESTADO_APROBADO);
    }

    public function scopeSobreEjecutados($q)
    {
        return $q->whereColumn('total_ejecutado', '>', 'total_presupuestado');
    }
}