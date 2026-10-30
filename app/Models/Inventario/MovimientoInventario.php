<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table      = 'movimiento_inventario';
    protected $primaryKey = 'id_movimiento';
    public $timestamps    = false;

    // Solo INSERT — el kardex es inmutable
    protected $fillable = [
        'id_empresa',
        'id_producto',
        'id_bodega',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'moneda',
        'referencia_tipo',
        'referencia_id',
        'numero_lote',
        'fecha_vencimiento',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'cantidad'         => 'decimal:4',
        'costo_unitario'   => 'decimal:4',
        'costo_total'      => 'decimal:4',
        'fecha_vencimiento'=> 'date',
        'created_at'       => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function creadoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'created_by');
    }

    // ── Hooks ───────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (MovimientoInventario $mov) {
            // Calcula costo total automáticamente
            if ($mov->costo_unitario && !$mov->costo_total) {
                $mov->costo_total = round($mov->cantidad * $mov->costo_unitario, 4);
            }
        });

        static::created(function (MovimientoInventario $mov) {
            $stock = StockBodega::firstOrCreate(
                ['id_producto' => $mov->id_producto, 'id_bodega' => $mov->id_bodega],
                ['cantidad_actual' => 0, 'costo_promedio' => 0]
            );

            $esEntrada = in_array($mov->tipo_movimiento, ['ENTRADA', 'DEVOLUCION']);
            $esSalida  = in_array($mov->tipo_movimiento, ['SALIDA', 'BAJA']);

            if ($esEntrada && $mov->costo_unitario) {
                $stock->actualizarCostoPromedio($mov->cantidad, $mov->costo_unitario);
            } elseif ($esEntrada) {
                $stock->increment('cantidad_actual', $mov->cantidad);
            } elseif ($esSalida) {
                $stock->decrement('cantidad_actual', $mov->cantidad);
            }
            // AJUSTE modifica directamente sin aplicar fórmula de costo promedio
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function esEntrada(): bool
    {
        return in_array($this->tipo_movimiento, ['ENTRADA', 'DEVOLUCION']);
    }

    public function esSalida(): bool
    {
        return in_array($this->tipo_movimiento, ['SALIDA', 'BAJA']);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorProducto($query, $idProducto)
    {
        return $query->where('id_producto', $idProducto);
    }

    public function scopePorBodega($query, $idBodega)
    {
        return $query->where('id_bodega', $idBodega);
    }

    public function scopeEntradas($query)
    {
        return $query->whereIn('tipo_movimiento', ['ENTRADA', 'DEVOLUCION']);
    }

    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo_movimiento', ['SALIDA', 'BAJA']);
    }

    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }

    public function scopePorLote($query, string $lote)
    {
        return $query->where('numero_lote', $lote);
    }
}