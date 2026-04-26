<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Oportunidad extends Model
{
    protected $table      = 'oportunidad';
    protected $primaryKey = 'id_oportunidad';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_prospecto',
        'id_etapa',
        'id_responsable',
        'id_linea',
        'nombre',
        'descripcion',
        'valor_estimado',
        'moneda',
        'probabilidad',
        'fecha_cierre_estimada',
        'fecha_cierre_real',
        'razon_cierre',
        'competidores',
        'created_by',
    ];

    protected $casts = [
        'valor_estimado'       => 'decimal:4',
        'probabilidad'         => 'integer',
        'fecha_cierre_estimada'=> 'date',
        'fecha_cierre_real'    => 'date',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes\Cliente::class, 'id_cliente');
    }

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'id_prospecto');
    }

    public function etapa()
    {
        return $this->belongsTo(EtapaFunnel::class, 'id_etapa');
    }

    public function responsable()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_responsable');
    }

    public function lineaNegocio()
    {
        return $this->belongsTo(\App\Models\Core\LineaNegocio::class, 'id_linea');
    }

    public function propuestas()
    {
        return $this->hasMany(Propuesta::class, 'id_oportunidad');
    }

    public function propuestaActiva()
    {
        return $this->hasOne(Propuesta::class, 'id_oportunidad')
                    ->whereIn('estado', ['ENVIADA', 'EN_REVISION']);
    }

    public function actividades()
    {
        return $this->hasMany(ActividadVenta::class, 'id_oportunidad');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getValorPonderadoAttribute(): float
    {
        return round($this->valor_estimado * $this->probabilidad / 100, 4);
    }

    public function estaGanada(): bool
    {
        return $this->etapa && $this->etapa->es_ganada;
    }

    public function estaPerdida(): bool
    {
        return $this->etapa && $this->etapa->es_perdida;
    }

    public function diasParaCierre(): ?int
    {
        if (!$this->fecha_cierre_estimada) return null;
        return now()->diffInDays($this->fecha_cierre_estimada, false);
    }

    public function moverEtapa(int $idEtapa): void
    {
        $this->update(['id_etapa' => $idEtapa]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeAbiertas($query)
    {
        return $query->whereHas('etapa', fn($q) =>
            $q->where('es_ganada', false)->where('es_perdida', false)
        );
    }

    public function scopeGanadas($query)
    {
        return $query->whereHas('etapa', fn($q) => $q->where('es_ganada', true));
    }

    public function scopePerdidas($query)
    {
        return $query->whereHas('etapa', fn($q) => $q->where('es_perdida', true));
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorVendedor($query, $idEmpleado)
    {
        return $query->where('id_responsable', $idEmpleado);
    }

    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_cierre_estimada', [$inicio, $fin]);
    }
}