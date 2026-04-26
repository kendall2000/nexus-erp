<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class AsignacionContrato extends Model
{
    protected $table      = 'asignacion_contrato';
    protected $primaryKey = 'id_asignacion';
    public $timestamps    = false;

    protected $fillable = [
        'id_contrato',
        'id_empleado',
        'id_sitio',
        'fecha_inicio',
        'fecha_fin',
        'rol_en_sitio',
        'turno',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
        'created_at'   => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function contrato()
    {
        return $this->belongsTo(ContratoServicio::class, 'id_contrato');
    }

    public function empleado()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_empleado');
    }

    public function sitio()
    {
        return $this->belongsTo(SitioTrabajo::class, 'id_sitio');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function estaActiva(): bool
    {
        return $this->activo &&
               (!$this->fecha_fin || $this->fecha_fin->isFuture());
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorContrato($query, $idContrato)
    {
        return $query->where('id_contrato', $idContrato);
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }

    public function scopePorSitio($query, $idSitio)
    {
        return $query->where('id_sitio', $idSitio);
    }
}