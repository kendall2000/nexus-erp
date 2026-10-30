<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class SeguimientoProspecto extends Model
{
    protected $table      = 'seguimiento_prospecto';
    protected $primaryKey = 'id_seguimiento';
    public $timestamps    = false;

    protected $fillable = [
        'id_prospecto',
        'id_realizado_por',
        'tipo',
        'fecha_hora',
        'duracion_min',
        'resultado',
        'resumen',
        'proxima_accion',
        'fecha_proxima_accion',
    ];

    protected $casts = [
        'fecha_hora'           => 'datetime',
        'fecha_proxima_accion' => 'date',
        'duracion_min'         => 'integer',
        'created_at'           => 'datetime',
    ];

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'id_prospecto');
    }

    public function realizadoPor()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_realizado_por');
    }

    public function scopeConProximaAccion($query)
    {
        return $query->whereNotNull('fecha_proxima_accion')
                     ->where('fecha_proxima_accion', '>=', today());
    }

    public function scopeVencidos($query)
    {
        return $query->whereNotNull('fecha_proxima_accion')
                     ->where('fecha_proxima_accion', '<', today());
    }
}