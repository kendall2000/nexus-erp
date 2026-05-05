<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class DetalleRecepcion extends Model
{
    protected $table      = 'detalle_recepcion';
    protected $primaryKey = 'id_detalle_rec';
    public $timestamps    = false;

    protected $fillable = [
        'id_recepcion', 'id_linea', 'id_producto',
        'cantidad_recibida', 'costo_unitario', 'subtotal',
    ];

    protected $casts = [
        'cantidad_recibida' => 'decimal:4',
        'costo_unitario'    => 'decimal:4',
        'subtotal'          => 'decimal:4',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function lineaOC()
    {
        return $this->belongsTo(DetalleOrdenCompra::class, 'id_linea');
    }
}