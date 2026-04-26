<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table      = 'moneda';
    protected $primaryKey = 'codigo';
    protected $keyType    = 'string';
    public $incrementing  = false;
    public $timestamps    = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'decimales',
        'activo',
    ];

    protected $casts = [
        'decimales' => 'integer',
        'activo'    => 'boolean',
    ];

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'moneda_base', 'codigo');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}