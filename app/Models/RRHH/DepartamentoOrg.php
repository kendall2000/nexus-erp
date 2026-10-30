<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class DepartamentoOrg extends Model
{
    protected $table      = 'departamento_org';
    protected $primaryKey = 'id_depto_org';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_padre',
        'nombre',
        'codigo',
        'centro_costo',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function padre()
    {
        return $this->belongsTo(DepartamentoOrg::class, 'id_padre');
    }

    public function hijos()
    {
        return $this->hasMany(DepartamentoOrg::class, 'id_padre');
    }

    public function cargos()
    {
        return $this->hasMany(Cargo::class, 'id_depto_org');
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'id_depto_org');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getTotalEmpleadosAttribute(): int
    {
        return $this->empleados()->where('estado', 'ACTIVO')->count();
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('id_padre');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}