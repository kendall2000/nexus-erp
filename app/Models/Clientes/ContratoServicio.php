<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class ContratoServicio extends Model
{
    protected $table      = 'contrato_servicio';
    protected $primaryKey = 'id_contrato';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_vendedor',
        'numero_contrato',
        'nombre_proyecto',
        'fecha_inicio',
        'fecha_fin',
        'fecha_firma',
        'valor_mensual',
        'valor_total_estimado',
        'moneda',
        'periodicidad_factura',
        'dia_facturacion',
        'url_contrato',
        'notas',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_inicio'          => 'date',
        'fecha_fin'             => 'date',
        'fecha_firma'           => 'date',
        'valor_mensual'         => 'decimal:4',
        'valor_total_estimado'  => 'decimal:4',
        'dia_facturacion'       => 'integer',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_vendedor');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo');
    }

    public function detalles()
    {
        return $this->hasMany(ContratoServicioDetalle::class, 'id_contrato');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionContrato::class, 'id_contrato');
    }

    public function asignacionesActivas()
    {
        return $this->hasMany(AsignacionContrato::class, 'id_contrato')
                    ->where('activo', true);
    }

    public function facturas()
    {
        return $this->hasMany(\App\Models\Finanzas\Factura::class, 'id_contrato');
    }

    public function tickets()
    {
        return $this->hasMany(\App\Models\CRM\Ticket::class, 'id_contrato');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function estaVigente(): bool
    {
        return $this->estado === 'VIGENTE';
    }

    public function estaVencido(): bool
    {
        return $this->fecha_fin && $this->fecha_fin->isPast();
    }

    public function diasParaVencer(): ?int
    {
        if (!$this->fecha_fin) return null;
        return now()->diffInDays($this->fecha_fin, false);
    }

    public function getTotalPersonalAsignadoAttribute(): int
    {
        return $this->asignacionesActivas()->count();
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeVigentes($query)
    {
        return $query->where('estado', 'VIGENTE');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('id_cliente', $idCliente);
    }

    public function scopeProximosAVencer($query, int $dias = 30)
    {
        return $query->where('estado', 'VIGENTE')
                     ->whereNotNull('fecha_fin')
                     ->whereBetween('fecha_fin', [now(), now()->addDays($dias)]);
    }

    public function scopePorVendedor($query, $idVendedor)
    {
        return $query->where('id_vendedor', $idVendedor);
    }
}