<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class PresupuestoAnual extends Model
{
    protected $table      = 'presupuesto_anual';
    protected $primaryKey = 'id_presupuesto';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_centro',
        'id_cuenta',
        'anio',
        'mes',
        'monto_presupuestado',
        'monto_ejecutado',
        'moneda',
    ];

    protected $casts = [
        'anio'                => 'integer',
        'mes'                 => 'integer',
        'monto_presupuestado' => 'decimal:4',
        'monto_ejecutado'     => 'decimal:4',
        'updated_at'          => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'id_centro');
    }

    public function cuentaContable()
    {
        return $this->belongsTo(CuentaContable::class, 'id_cuenta');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getSaldoDisponibleAttribute(): float
    {
        return $this->monto_presupuestado - $this->monto_ejecutado;
    }

    public function getPorcentajeEjecucionAttribute(): float
    {
        if ($this->monto_presupuestado == 0) return 0;
        return round(($this->monto_ejecutado / $this->monto_presupuestado) * 100, 2);
    }

    public function estasobreejecutado(): bool
    {
        return $this->monto_ejecutado > $this->monto_presupuestado;
    }

    public function registrarEjecucion(float $monto): void
    {
        $this->increment('monto_ejecutado', $monto);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopePorMes($query, int $anio, int $mes)
    {
        return $query->where('anio', $anio)->where('mes', $mes);
    }

    public function scopeSobreEjecutados($query)
    {
        return $query->whereColumn('monto_ejecutado', '>', 'monto_presupuestado');
    }

    public function scopePorCentro($query, $idCentro)
    {
        return $query->where('id_centro', $idCentro);
    }
}