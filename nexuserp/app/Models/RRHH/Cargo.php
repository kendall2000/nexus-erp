<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table      = 'cargo';
    protected $primaryKey = 'id_cargo';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_depto_org',
        'nombre',
        'descripcion',
        'nivel_jerarquico',
        'salario_min',
        'salario_max',
        'moneda',
        'requiere_vehiculo',
        'activo',
    ];

    protected $casts = [
        'nivel_jerarquico' => 'integer',
        'salario_min'      => 'decimal:4',
        'salario_max'      => 'decimal:4',
        'requiere_vehiculo'=> 'boolean',
        'activo'           => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function departamento()
    {
        return $this->belongsTo(DepartamentoOrg::class, 'id_depto_org');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo');
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'id_cargo');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getNivelNombreAttribute(): string
    {
        return match($this->nivel_jerarquico) {
            1 => 'Operativo',
            2 => 'Supervisión',
            3 => 'Gerencia',
            4 => 'Dirección',
            default => 'Sin definir',
        };
    }

    public function salarioEnRango(float $salario): bool
    {
        if ($this->salario_min && $salario < $this->salario_min) return false;
        if ($this->salario_max && $salario > $this->salario_max) return false;
        return true;
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

    public function scopePorNivel($query, int $nivel)
    {
        return $query->where('nivel_jerarquico', $nivel);
    }
}