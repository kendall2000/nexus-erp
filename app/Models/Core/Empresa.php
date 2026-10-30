<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table      = 'empresa';
    protected $primaryKey = 'id_empresa';
    public $timestamps    = true;

    protected $fillable = [
        'nombre_legal',
        'nombre_comercial',
        'nit',
        'id_pais',
        'moneda_base',
        'email_corporativo',
        'telefono',
        'direccion',
        'id_municipio',
        'logo_url',
        'fecha_fundacion',
        'activo',
        'tasa_iva',
        'iva_incluido_en_precio',
    ];

    protected $casts = [
        'activo'          => 'boolean',
        'fecha_fundacion' => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'tasa_iva'               => 'decimal:2',
        'iva_incluido_en_precio' => 'boolean',

    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'id_pais');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_base', 'codigo');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'id_municipio');
    }

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class, 'id_empresa');
    }

    public function lineasNegocio()
    {
        return $this->hasMany(LineaNegocio::class, 'id_empresa');
    }

    public function empleados()
    {
        return $this->hasMany(\App\Models\RRHH\Empleado::class, 'id_empresa');
    }

    public function clientes()
    {
        return $this->hasMany(\App\Models\Clientes\Cliente::class, 'id_empresa');
    }

    public function usuarios()
    {
        return $this->hasMany(\App\Models\Core\Usuario::class, 'id_empresa');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function getLogoUrlAttribute($value)
    {
        return $value ?? asset('images/logo-default.png');
    }
    public function getTasaIvaDecimalAttribute(): float
    {
        return (float) $this->tasa_iva / 100;
    }
}