<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table      = 'asistencia';
    protected $primaryKey = 'id_asistencia';
    public $timestamps    = true;

    protected $fillable = [
        'id_empleado',
        'id_empresa',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'tipo',
        'estado',
        'minutos_tarde',
        'horas_extra',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'hora_entrada'  => 'datetime',
        'hora_salida'   => 'datetime',
        'minutos_tarde' => 'integer',
        'horas_extra'   => 'decimal:2',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function getHorasTrabajadasAttribute(): ?float
    {
        if (!$this->hora_entrada || !$this->hora_salida) return null;
        return round($this->hora_entrada->diffInMinutes($this->hora_salida) / 60, 2);
    }

    public function estaPresente(): bool
    {
        return $this->estado === 'PRESENTE';
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopePorPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }

    public function scopeAusentes($query)
    {
        return $query->where('estado', 'AUSENTE');
    }

    public function scopeConHorasExtra($query)
    {
        return $query->where('horas_extra', '>', 0);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}