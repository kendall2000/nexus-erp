<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table      = 'producto';
    protected $primaryKey = 'id_producto';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_categoria',
        'codigo',
        'nombre',
        'descripcion',
        'unidad_medida',
        'precio_compra',
        'precio_venta',
        'moneda',
        'stock_minimo',
        'stock_maximo',
        'requiere_lote',
        'es_perecedero',
        'activo',
    ];

    protected $casts = [
        'precio_compra'  => 'decimal:4',
        'precio_venta'   => 'decimal:4',
        'stock_minimo'   => 'decimal:2',
        'stock_maximo'   => 'decimal:2',
        'requiere_lote'  => 'boolean',
        'es_perecedero'  => 'boolean',
        'activo'         => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'id_categoria');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo');
    }

    public function stocks()
    {
        return $this->hasMany(StockBodega::class, 'id_producto');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'id_producto')
                    ->orderBy('created_at', 'desc');
    }

    public function detallesOrdenCompra()
    {
        return $this->hasMany(DetalleOrdenCompra::class, 'id_producto');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getStockTotalAttribute(): float
    {
        return $this->stocks()->sum('cantidad_actual');
    }

    public function getStockEnBodega(int $idBodega): float
    {
        $stock = $this->stocks()->where('id_bodega', $idBodega)->first();
        return $stock ? $stock->cantidad_actual : 0;
    }

    public function estaBajoMinimo(): bool
    {
        return $this->stock_total < $this->stock_minimo;
    }

    public function estaAgotado(): bool
    {
        return $this->stock_total <= 0;
    }

    public function getCostoPromedioGlobalAttribute(): float
    {
        $stocks = $this->stocks()->where('cantidad_actual', '>', 0)->get();
        if ($stocks->isEmpty()) return $this->precio_compra ?? 0;
        $totalValor    = $stocks->sum(fn($s) => $s->cantidad_actual * $s->costo_promedio);
        $totalCantidad = $stocks->sum('cantidad_actual');
        return $totalCantidad > 0 ? round($totalValor / $totalCantidad, 4) : 0;
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopeBajoMinimo($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->whereRaw('cantidad_actual < (SELECT stock_minimo FROM producto WHERE id_producto = stock_bodega.id_producto)');
        });
    }

    public function scopeAgotados($query)
    {
        return $query->whereHas('stocks', fn($q) => $q->where('cantidad_actual', '<=', 0))
                     ->orWhereDoesntHave('stocks');
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'LIKE', "%{$termino}%")
              ->orWhere('codigo', 'LIKE', "%{$termino}%");
        });
    }
}