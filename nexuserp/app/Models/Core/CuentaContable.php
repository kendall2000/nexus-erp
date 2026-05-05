<?php
// app/Models/Core/CuentaContable.php

namespace App\Models\Core;

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

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    /**
     * Cuenta padre (jerarquía contable).
     */
    public function padre()
    {
        return $this->belongsTo(self::class, 'id_padre');
    }

    /**
     * Subcuentas hijas.
     */
    public function hijas()
    {
        return $this->hasMany(self::class, 'id_padre');
    }

    public function presupuestos()
    {
        return $this->hasMany(
            \App\Models\Finanzas\PresupuestoAnual::class,
            'id_cuenta'
        );
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getEtiquetaAttribute(): string
    {
        return "{$this->codigo} — {$this->nombre}";
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopePorEmpresa($q, $idEmpresa)
    {
        return $q->where('id_empresa', $idEmpresa);
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeDeMovimiento($q)
    {
        return $q->where('permite_movimiento', true);
    }

    public function scopeDePresupuesto($q)
    {
        return $q->whereIn('tipo', ['INGRESO', 'GASTO', 'COSTO']);
    }
}