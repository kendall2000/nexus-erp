<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table      = 'pais';
    protected $primaryKey = 'id_pais';
    public $timestamps    = false;

    protected $fillable = [
        'codigo_iso2',
        'codigo_iso3',
        'nombre',
        'prefijo_tel',
        'moneda_defecto',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
    ];

    public function divisiones()
    {
        return $this->hasMany(DivisionGeografica::class, 'id_pais');
    }

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'id_pais');
    }

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class, 'id_pais');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_defecto', 'codigo');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}