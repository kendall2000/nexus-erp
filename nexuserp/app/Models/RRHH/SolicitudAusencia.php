<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class SolicitudAusencia extends Model
{
    protected $table      = 'solicitud_ausencia';
    protected $primaryKey = 'id_solicitud';
    public $timestamps    = true;

    protected $fillable = [
        'id_empleado',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'dias_habiles',
        'motivo',
        'estado',
        'aprobado_por',
        'fecha_aprobacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio'     => 'date',
        'fecha_fin'        => 'date',
        'dias_habiles'     => 'integer',
        'fecha_aprobacion' => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(Empleado::class, 'aprobado_por');
    }

    public function aprobar(int $idAprobador): void
    {
        $this->update([
            'estado'           => 'APROBADO',
            'aprobado_por'     => $idAprobador,
            'fecha_aprobacion' => now(),
        ]);
    }

    public function rechazar(int $idAprobador, string $observacion): void
    {
        $this->update([
            'estado'           => 'RECHAZADO',
            'aprobado_por'     => $idAprobador,
            'fecha_aprobacion' => now(),
            'observaciones'    => $observacion,
        ]);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    public function scopePorEmpleado($query, $idEmpleado)
    {
        return $query->where('id_empleado', $idEmpleado);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}