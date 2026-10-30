<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $table      = 'bodega';
    protected $primaryKey = 'id_bodega';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'id_sucursal',
        'nombre',
        'ubicacion',
        'responsable_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function sucursal()
    {
        return $this->belongsTo(\App\Models\Core\Sucursal::class, 'id_sucursal');
    }

    public function responsable()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'responsable_id');
    }

    public function stocks()
    {
        return $this->hasMany(StockBodega::class, 'id_bodega');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'id_bodega')
                    ->orderBy('created_at', 'desc');
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'id_bodega');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getValorTotalInventarioAttribute(): float
    {
        return $this->stocks()
            ->selectRaw('SUM(cantidad_actual * costo_promedio) as total')
            ->value('total') ?? 0;
    }

    public function getTotalProductosAttribute(): int
    {
        return $this->stocks()->where('cantidad_actual', '>', 0)->count();
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}