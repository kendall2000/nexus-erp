<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class ActividadVenta extends Model
{
    protected $table      = 'actividad_venta';
    protected $primaryKey = 'id_actividad';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_oportunidad',
        'id_prospecto',
        'id_cliente',
        'id_responsable',
        'tipo',
        'titulo',
        'descripcion',
        'fecha_programada',
        'fecha_realizada',
        'duracion_min',
        'resultado',
        'estado',
        'prioridad',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_realizada'  => 'datetime',
        'duracion_min'     => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function oportunidad()
    {
        return $this->belongsTo(Oportunidad::class, 'id_oportunidad');
    }

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'id_prospecto');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes\Cliente::class, 'id_cliente');
    }

    public function responsable()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_responsable');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_programada', today());
    }

    public function scopePorVendedor($query, $idEmpleado)
    {
        return $query->where('id_responsable', $idEmpleado);
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'PENDIENTE')
                     ->where('fecha_programada', '<', now());
    }
}