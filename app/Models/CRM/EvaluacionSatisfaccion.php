<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class EvaluacionSatisfaccion extends Model
{
    protected $table      = 'evaluacion_satisfaccion';
    protected $primaryKey = 'id_evaluacion';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_contrato',
        'id_ticket',
        'tipo',
        'puntuacion',
        'comentarios',
        'canal',
        'fecha_respuesta',
    ];

    protected $casts = [
        'puntuacion'      => 'integer',
        'fecha_respuesta' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes\Cliente::class, 'id_cliente');
    }

    public function contrato()
    {
        return $this->belongsTo(\App\Models\Clientes\ContratoServicio::class, 'id_contrato');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function getEtiquetaNpsAttribute(): string
    {
        if ($this->puntuacion >= 9) return 'PROMOTOR';
        if ($this->puntuacion >= 7) return 'NEUTRO';
        return 'DETRACTOR';
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopeNps($query)
    {
        return $query->where('tipo', 'NPS');
    }

    public function scopePorPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_respuesta', [$inicio, $fin]);
    }
}