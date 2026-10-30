<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class LineaNegocio extends Model
{
    protected $table      = 'linea_negocio';
    protected $primaryKey = 'id_linea';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function tiposServicio()
    {
        return $this->hasMany(\App\Models\Clientes\TipoServicio::class, 'id_linea');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}