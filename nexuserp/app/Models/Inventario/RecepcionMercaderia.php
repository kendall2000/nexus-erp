<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\DetalleOrdenCompra;

class RecepcionMercaderia extends Model
{
    protected $table      = 'recepcion_mercaderia';
    protected $primaryKey = 'id_recepcion';

    protected $fillable = [
        'id_empresa', 'id_oc', 'id_bodega',
        'numero_recepcion', 'fecha_recepcion',
        'notas', 'created_by',
    ];

    protected $casts = [
        'fecha_recepcion' => 'date',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'id_oc');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleRecepcion::class, 'id_recepcion');
    }
}