<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class StockBodega extends Model
{
    protected $table = 'stock_bodega';

    protected $primaryKey = 'id_stock';

    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_bodega',
        'cantidad_actual',
        'costo_promedio',
    ];

    protected $casts = [
        'cantidad_actual' => 'decimal:4',
        'costo_promedio' => 'decimal:4',
        'updated_at' => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getValorTotalAttribute(): float
    {
        return round($this->cantidad_actual * $this->costo_promedio, 4);
    }

    public function estaBajoMinimo(): bool
    {
        return $this->cantidad_actual < $this->producto->stock_minimo;
    }

    /**
     * Fila de existencia del producto en la bodega, creada si no existe y bloqueada hasta que
     * termine la transacción: dos movimientos simultáneos no pisan la cantidad ni el costo
     * promedio. La lectura con bloqueo trae siempre el último valor confirmado.
     */
    public static function bloqueado(int $idProducto, int $idBodega): self
    {
        // createOrFirst: si otro la crea al mismo tiempo, el índice único lo resuelve.
        static::query()->createOrFirst(['id_producto' => $idProducto, 'id_bodega' => $idBodega], ['cantidad_actual' => 0, 'costo_promedio' => 0]);

        return static::query()->where(['id_producto' => $idProducto, 'id_bodega' => $idBodega])->lockForUpdate()->firstOrFail();
    }

    public function actualizarCostoPromedio(float $cantidadNueva, float $costoNuevo): void
    {
        $cantidadActual = $this->cantidad_actual;
        $costoActual = $this->costo_promedio;
        $totalAnterior = $cantidadActual * $costoActual;
        $totalNuevo = $cantidadNueva * $costoNuevo;
        $totalCantidad = $cantidadActual + $cantidadNueva;

        $nuevoCosto = $totalCantidad > 0
            ? round(($totalAnterior + $totalNuevo) / $totalCantidad, 4)
            : $costoNuevo;

        $this->update([
            'cantidad_actual' => $totalCantidad,
            'costo_promedio' => $nuevoCosto,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeConStock($query)
    {
        return $query->where('cantidad_actual', '>', 0);
    }

    public function scopePorBodega($query, $idBodega)
    {
        return $query->where('id_bodega', $idBodega);
    }

    public function scopePorProducto($query, $idProducto)
    {
        return $query->where('id_producto', $idProducto);
    }
}
