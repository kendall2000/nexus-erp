<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class ContratoServicioDetalle extends Model
{
    protected $table      = 'contrato_servicio_detalle';
    protected $primaryKey = 'id_detalle';
    public $timestamps    = false;

    protected $fillable = [
        'id_contrato',
        'id_tipo_servicio',
        'id_sitio',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento_pct',
        'subtotal',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:4',
        'descuento_pct'   => 'decimal:2',
        'subtotal'        => 'decimal:4',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function contrato()
    {
        return $this->belongsTo(ContratoServicio::class, 'id_contrato');
    }

    public function tipoServicio()
    {
        return $this->belongsTo(TipoServicio::class, 'id_tipo_servicio');
    }

    public function sitio()
    {
        return $this->belongsTo(SitioTrabajo::class, 'id_sitio');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function calcularSubtotal(): float
    {
        $bruto     = $this->cantidad * $this->precio_unitario;
        $descuento = $bruto * ($this->descuento_pct / 100);
        return round($bruto - $descuento, 4);
    }
}