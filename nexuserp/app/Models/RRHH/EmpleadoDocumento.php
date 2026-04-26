<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class EmpleadoDocumento extends Model
{
    protected $table      = 'empleado_documento';
    protected $primaryKey = 'id_doc';
    public $timestamps    = false;

    protected $fillable = [
        'id_empleado',
        'tipo_documento',
        'nombre',
        'url_archivo',
        'fecha_emision',
        'fecha_vencimiento',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
        'created_at'        => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }

    public function diasParaVencer(): ?int
    {
        if (!$this->fecha_vencimiento) return null;
        return now()->diffInDays($this->fecha_vencimiento, false);
    }

    public function scopeVencidos($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->where('fecha_vencimiento', '<', today());
    }

    public function scopeProximosAVencer($query, int $dias = 30)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->whereBetween('fecha_vencimiento', [today(), today()->addDays($dias)]);
    }
}