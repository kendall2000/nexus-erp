<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table      = 'centro_costo';
    protected $primaryKey = 'id_centro';
    public $timestamps    = false;

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

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function presupuestos()
    {
        return $this->hasMany(PresupuestoAnual::class, 'id_centro');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getPresupuestoAnioAttribute(): float
    {
        return $this->presupuestos()
            ->where('anio', now()->year)
            ->sum('monto_presupuestado');
    }

    public function getEjecutadoAnioAttribute(): float
    {
        return $this->presupuestos()
            ->where('anio', now()->year)
            ->sum('monto_ejecutado');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}