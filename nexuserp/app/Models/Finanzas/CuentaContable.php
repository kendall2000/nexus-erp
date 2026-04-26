<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class CuentaContable extends Model
{
    protected $table      = 'cuenta_contable';
    protected $primaryKey = 'id_cuenta';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'id_padre',
        'codigo',
        'nombre',
        'tipo',
        'naturaleza',
        'nivel',
        'permite_movimiento',
        'activo',
    ];

    protected $casts = [
        'nivel'              => 'integer',
        'permite_movimiento' => 'boolean',
        'activo'             => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function padre()
    {
        return $this->belongsTo(CuentaContable::class, 'id_padre');
    }

    public function hijos()
    {
        return $this->hasMany(CuentaContable::class, 'id_padre');
    }

    public function presupuestos()
    {
        return $this->hasMany(PresupuestoAnual::class, 'id_cuenta');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function esDeAgrupacion(): bool
    {
        return !$this->permite_movimiento;
    }

    public function getDescripcionCompleta(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePermiteMovimiento($query)
    {
        return $query->where('permite_movimiento', true)->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('id_padre');
    }
}