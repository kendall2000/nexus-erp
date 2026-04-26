<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    protected $table      = 'detalle_factura';
    protected $primaryKey = 'id_linea';
    public $timestamps    = false;

    protected $fillable = [
        'id_factura',
        'id_tipo_servicio',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
        'es_afecto_iva',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:4',
        'descuento'       => 'decimal:4',
        'subtotal'        => 'decimal:4',
        'es_afecto_iva'   => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function tipoServicio()
    {
        return $this->belongsTo(
            \App\Models\Clientes\TipoServicio::class,
            'id_tipo_servicio'
        );
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function calcularSubtotal(): float
    {
        $bruto = $this->cantidad * $this->precio_unitario;
        return round($bruto - $this->descuento, 4);
    }

    public function getIvaLinea(float $porcentaje = 12.0): float
    {
        if (!$this->es_afecto_iva) return 0;
        return round($this->subtotal * ($porcentaje / 100), 4);
    }
}