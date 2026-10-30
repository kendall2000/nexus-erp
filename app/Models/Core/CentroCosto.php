<?php
// app/Models/Core/CentroCosto.php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table      = 'centro_costo';
    protected $primaryKey = 'id_centro';

    /**
     * La tabla NO tiene created_at ni updated_at según el DDL,
     * así que desactivamos timestamps para evitar errores SQL.
     */
    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    /**
     * Presupuestos asociados a este centro de costo.
     */
    public function presupuestos()
    {
        return $this->hasMany(
            \App\Models\Finanzas\PresupuestoAnual::class,
            'id_centro'
        );
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    /**
     * Etiqueta combinada para selects: "01 — Administración".
     */
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
}