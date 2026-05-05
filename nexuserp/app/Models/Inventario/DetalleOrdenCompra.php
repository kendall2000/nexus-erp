<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class DetalleOrdenCompra extends Model
{
    protected $table      = 'detalle_orden_compra';
    protected $primaryKey = 'id_linea';
    public $timestamps    = false;

    protected $fillable = [
        'id_oc',
        'id_producto',
        'id_centro',          // ← NUEVO: override opcional
        'id_cuenta',          // ← NUEVO: override opcional
        'descripcion',
        'cantidad_pedida',
        'cantidad_recibida',
        'precio_unitario',
        'descuento',
        'subtotal',
    ];

    protected $casts = [
        'cantidad_pedida'   => 'decimal:4',
        'cantidad_recibida' => 'decimal:4',
        'precio_unitario'   => 'decimal:4',
        'descuento'         => 'decimal:4',
        'subtotal'          => 'decimal:4',
    ];

    // ── Relaciones ──────────────────────────────────────────────
    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'id_oc');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function centroCosto()
    {
        return $this->belongsTo(\App\Models\Core\CentroCosto::class, 'id_centro');
    }

    public function cuentaContable()
    {
        return $this->belongsTo(\App\Models\Core\CuentaContable::class, 'id_cuenta');
    }

    // ── Cascade override ────────────────────────────────────────
    public function getCentroEfectivoAttribute(): ?int
    {
        return $this->id_centro ?? $this->producto?->id_centro_default;
    }

    public function getCuentaEfectivaAttribute(): ?int
    {
        return $this->id_cuenta ?? $this->producto?->id_cuenta_gasto;
    }

    // ── Helpers ─────────────────────────────────────────────────
    public function getCantidadPendienteAttribute(): float
    {
        return max(0, $this->cantidad_pedida - $this->cantidad_recibida);
    }

    public function estaCompleto(): bool
    {
        return $this->cantidad_recibida >= $this->cantidad_pedida;
    }

    public function calcularSubtotal(): float
    {
        return round(($this->cantidad_pedida * $this->precio_unitario) - $this->descuento, 4);
    }

    public function recibirCantidad(float $cantidad, int $idBodega, int $creadoPor): void
    {
        $this->increment('cantidad_recibida', $cantidad);

        MovimientoInventario::create([
            'id_empresa'      => $this->ordenCompra->id_empresa,
            'id_producto'     => $this->id_producto,
            'id_bodega'       => $idBodega,
            'tipo_movimiento' => 'ENTRADA',
            'cantidad'        => $cantidad,
            'costo_unitario'  => $this->precio_unitario,
            'moneda'          => $this->ordenCompra->moneda,
            'referencia_tipo' => 'COMPRA',
            'referencia_id'   => $this->id_oc,
            'created_by'      => $creadoPor,
        ]);

        $oc = $this->ordenCompra;
        $oc->refresh();
        $todoRecibido = $oc->detalles()->get()->every(fn($d) => $d->estaCompleto());
        $oc->update(['estado' => $todoRecibido ? 'RECIBIDA' : 'PARCIAL']);
    }
}