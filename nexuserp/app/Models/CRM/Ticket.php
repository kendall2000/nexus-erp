<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table      = 'ticket';
    protected $primaryKey = 'id_ticket';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_contrato',
        'id_categoria',
        'id_asignado_a',
        'id_sla',
        'numero_ticket',
        'asunto',
        'descripcion',
        'canal_origen',
        'prioridad',
        'tipo',
        'sla_primera_respuesta_hrs',
        'sla_resolucion_hrs',
        'fecha_apertura',
        'fecha_limite_respuesta',
        'fecha_limite_resolucion',
        'fecha_primera_respuesta',
        'fecha_resolucion',
        'fecha_cierre',
        'estado',
        'calificacion_cliente',
        'comentario_calificacion',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sla_primera_respuesta_hrs' => 'integer',
        'sla_resolucion_hrs'        => 'integer',
        'calificacion_cliente'      => 'integer',
        'fecha_apertura'            => 'datetime',
        'fecha_limite_respuesta'    => 'datetime',
        'fecha_limite_resolucion'   => 'datetime',
        'fecha_primera_respuesta'   => 'datetime',
        'fecha_resolucion'          => 'datetime',
        'fecha_cierre'              => 'datetime',
        'created_at'                => 'datetime',
        'updated_at'                => 'datetime',
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

    public function contrato()
    {
        return $this->belongsTo(\App\Models\Clientes\ContratoServicio::class, 'id_contrato');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaTicket::class, 'id_categoria');
    }

    public function asignadoA()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_asignado_a');
    }

    public function sla()
    {
        return $this->belongsTo(SlaConfig::class, 'id_sla');
    }

    public function comentarios()
    {
        return $this->hasMany(TicketComentario::class, 'id_ticket')
                    ->orderBy('created_at');
    }

    public function comentariosPublicos()
    {
        return $this->hasMany(TicketComentario::class, 'id_ticket')
                    ->where('es_nota_interna', false)
                    ->orderBy('created_at');
    }

    public function adjuntos()
    {
        return $this->hasMany(TicketAdjunto::class, 'id_ticket');
    }

    public function escalaciones()
    {
        return $this->hasMany(EscalacionTicket::class, 'id_ticket');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getEstadoSlaAttribute(): string
    {
        if ($this->fecha_resolucion) return 'RESUELTO';
        if (!$this->fecha_limite_resolucion) return 'SIN_SLA';
        if ($this->fecha_limite_resolucion->isPast()) return 'SLA_VENCIDO';
        if ($this->fecha_limite_resolucion->diffInHours(now()) <= 2) return 'SLA_CRITICO';
        return 'EN_TIEMPO';
    }

    public function getMinutosRestantesSlaAttribute(): ?int
    {
        if (!$this->fecha_limite_resolucion) return null;
        return (int) now()->diffInMinutes($this->fecha_limite_resolucion, false);
    }

    public function estaAbierto(): bool
    {
        return !in_array($this->estado, ['CERRADO', 'RESUELTO']);
    }

    public function registrarPrimeraRespuesta(): void
    {
        if (!$this->fecha_primera_respuesta) {
            $this->update([
                'fecha_primera_respuesta' => now(),
                'estado'                  => 'EN_PROGRESO',
            ]);
        }
    }

    public function resolver(): void
    {
        $this->update([
            'estado'           => 'RESUELTO',
            'fecha_resolucion' => now(),
        ]);
    }

    public function cerrar(?int $calificacion = null, ?string $comentario = null): void
    {
        $this->update([
            'estado'                  => 'CERRADO',
            'fecha_cierre'            => now(),
            'calificacion_cliente'    => $calificacion,
            'comentario_calificacion' => $comentario,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeAbiertos($query)
    {
        return $query->whereNotIn('estado', ['CERRADO']);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('id_cliente', $idCliente);
    }

    public function scopePorAgente($query, $idEmpleado)
    {
        return $query->where('id_asignado_a', $idEmpleado);
    }

    public function scopeSlaVencido($query)
    {
        return $query->whereNotIn('estado', ['RESUELTO', 'CERRADO'])
                     ->where('fecha_limite_resolucion', '<', now());
    }

    public function scopeCriticos($query)
    {
        return $query->where('prioridad', 'CRITICA')
                     ->whereNotIn('estado', ['RESUELTO', 'CERRADO']);
    }
}