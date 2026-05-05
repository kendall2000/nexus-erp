<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table      = 'cliente';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'id_empresa', 'id_industria', 'id_pais', 'id_municipio',
        'razon_social', 'nombre_comercial', 'nit', 'tipo_persona',
        'email_principal', 'telefono_principal', 'sitio_web',
        'direccion_fiscal', 'segmento', 'categoria',
        'moneda_facturacion', 'dias_credito', 'limite_credito',
        'activo', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'limite_credito'=> 'decimal:4',
        'deleted_at'    => 'datetime',
    ];

    public function pais()
    {
        return $this->belongsTo(\App\Models\Core\Pais::class, 'id_pais');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'id_cliente');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_cliente');
    }
}