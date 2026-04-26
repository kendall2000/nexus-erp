<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table      = 'factura';
    protected $primaryKey = 'id_factura';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_contrato',
        'id_serie',
        'numero_factura',
        'numero_completo',
        'tipo',
        'fecha_emision',
        'fecha_vencimiento',
        'periodo_servicio_inicio',
        'periodo_servicio_fin',
        'moneda',
        'subtotal',
        'descuento',
        'base_imponible',
        'iva',
        'total',
        'total_pagado',
        'saldo_pendiente',
        'estado',
        'uuid_fel',
        'numero_autorizacion_fel',
        'fecha_certificacion_fel',
        'url_pdf_fel',
        'notas',
        'created_by',
        'anulada_por',
        'fecha_anulacion',
    ];

    protected $casts = [
        'fecha_emision'           => 'date',
        'fecha_vencimiento'       => 'date',
        'periodo_servicio_inicio' => 'date',
        'periodo_servicio_fin'    => 'date',
        'fecha_certificacion_fel' => 'datetime',
        'fecha_anulacion'         => 'datetime',
        'subtotal'                => 'decimal:4',
        'descuento'               => 'decimal:4',
        'base_imponible'          => 'decimal:4',
        'iva'                     => 'decimal:4',
        'total'                   => 'decimal:4',
        'total_pagado'            => 'decimal:4',
        'saldo_pendiente'         => 'decimal:4',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
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

    public function serie()
    {
        return $this->belongsTo(SerieFacturacion::class, 'id_serie');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'id_factura');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_factura');
    }

    public function anuladaPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'anulada_por');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function estaPagada(): bool
    {
        return $this->estado === 'PAGADA';
    }

    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento->isPast()
            && !in_array($this->estado, ['PAGADA', 'ANULADA']);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === 'ANULADA';
    }

    public function tieneFel(): bool
    {
        return !is_null($this->uuid_fel);
    }

    public function getDiasVencidaAttribute(): int
    {
        if (!$this->estaVencida()) return 0;
        return now()->diffInDays($this->fecha_vencimiento);
    }

    public function getAntiguedadAttribute(): string
    {
        $dias = now()->diffInDays($this->fecha_vencimiento, false);
        if ($dias > 0)  return 'VIGENTE';
        if ($dias >= -30)  return '1-30 DIAS';
        if ($dias >= -60)  return '31-60 DIAS';
        if ($dias >= -90)  return '61-90 DIAS';
        return 'MAS DE 90 DIAS';
    }

    public function registrarPago(float $monto): void
    {
        $totalPagado    = $this->total_pagado + $monto;
        $saldoPendiente = $this->total - $totalPagado;

        $this->update([
            'total_pagado'    => $totalPagado,
            'saldo_pendiente' => max(0, $saldoPendiente),
            'estado'          => $saldoPendiente <= 0 ? 'PAGADA' : 'PARCIAL',
        ]);
    }

    public function anular(int $idUsuario): void
    {
        $this->update([
            'estado'         => 'ANULADA',
            'anulada_por'    => $idUsuario,
            'fecha_anulacion'=> now(),
        ]);
    }

    public function calcularIva(float $porcentaje = 12.0): float
    {
        return round($this->base_imponible * ($porcentaje / 100), 4);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('id_cliente', $idCliente);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['EMITIDA', 'ENVIADA', 'PARCIAL', 'VENCIDA']);
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'VENCIDA')
                     ->orWhere(function ($q) {
                         $q->whereIn('estado', ['EMITIDA', 'ENVIADA', 'PARCIAL'])
                           ->where('fecha_vencimiento', '<', today());
                     });
    }

    public function scopeEmitidas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_emision', [$inicio, $fin]);
    }

    public function scopeSinFel($query)
    {
        return $query->whereNull('uuid_fel')
                     ->where('estado', '!=', 'ANULADA');
    }

    public function scopePorAntiguedad($query, string $rango)
    {
        return match($rango) {
            '1-30'   => $query->whereBetween('fecha_vencimiento', [today()->subDays(30), today()]),
            '31-60'  => $query->whereBetween('fecha_vencimiento', [today()->subDays(60), today()->subDays(31)]),
            '61-90'  => $query->whereBetween('fecha_vencimiento', [today()->subDays(90), today()->subDays(61)]),
            '90+'    => $query->where('fecha_vencimiento', '<', today()->subDays(90)),
            default  => $query,
        };
    }
}