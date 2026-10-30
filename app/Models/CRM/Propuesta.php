<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Propuesta extends Model
{
    protected $table      = 'propuesta';
    protected $primaryKey = 'id_propuesta';
    public $timestamps    = true;

    protected $fillable = [
        'id_oportunidad',
        'id_elaborado_por',
        'numero_propuesta',
        'version',
        'titulo',
        'valor_propuesto',
        'moneda',
        'fecha_emision',
        'fecha_vencimiento',
        'url_documento',
        'notas_internas',
        'estado',
        'motivo_rechazo',
    ];

    protected $casts = [
        'valor_propuesto'  => 'decimal:4',
        'version'          => 'integer',
        'fecha_emision'    => 'date',
        'fecha_vencimiento'=> 'date',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function oportunidad()
    {
        return $this->belongsTo(Oportunidad::class, 'id_oportunidad');
    }

    public function elaboradoPor()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_elaborado_por');
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'ENVIADA' && $this->fecha_vencimiento->isFuture();
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeVigentes($query)
    {
        return $query->where('estado', 'ENVIADA')
                     ->where('fecha_vencimiento', '>=', today());
    }
}