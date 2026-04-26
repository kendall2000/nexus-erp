<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class SlaConfig extends Model
{
    protected $table      = 'sla_config';
    protected $primaryKey = 'id_sla';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'nombre',
        'prioridad',
        'tiempo_primera_respuesta_hrs',
        'tiempo_resolucion_hrs',
        'aplica_fines_semana',
        'activo',
    ];

    protected $casts = [
        'tiempo_primera_respuesta_hrs' => 'integer',
        'tiempo_resolucion_hrs'        => 'integer',
        'aplica_fines_semana'          => 'boolean',
        'activo'                       => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_sla');
    }

    public function calcularFechaLimiteRespuesta(\DateTime $desde): \DateTime
    {
        return (clone $desde)->modify("+{$this->tiempo_primera_respuesta_hrs} hours");
    }

    public function calcularFechaLimiteResolucion(\DateTime $desde): \DateTime
    {
        return (clone $desde)->modify("+{$this->tiempo_resolucion_hrs} hours");
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorPrioridad($query, string $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }
}