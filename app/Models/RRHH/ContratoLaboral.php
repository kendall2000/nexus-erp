<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class ContratoLaboral extends Model
{
    protected $table      = 'contrato_laboral';
    protected $primaryKey = 'id_contrato';
    public $timestamps    = true;

    protected $fillable = [
        'id_empleado',
        'id_empresa',
        'numero_contrato',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'salario_base',
        'moneda',
        'jornada',
        'horas_semana',
        'url_contrato',
        'estado',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
        'salario_base'  => 'decimal:4',
        'horas_semana'  => 'integer',
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

    public function estaVigente(): bool
    {
        return $this->estado === 'VIGENTE';
    }

    public function estaVencido(): bool
    {
        return $this->fecha_fin && $this->fecha_fin->isPast();
    }

    public function getDiasParaVencerAttribute(): ?int
    {
        if (!$this->fecha_fin) return null;
        return now()->diffInDays($this->fecha_fin, false);
    }

    public function scopeVigentes($query)
    {
        return $query->where('estado', 'VIGENTE');
    }

    public function scopeProximosAVencer($query, int $dias = 30)
    {
        return $query->where('estado', 'VIGENTE')
                     ->whereNotNull('fecha_fin')
                     ->whereBetween('fecha_fin', [today(), today()->addDays($dias)]);
    }
}