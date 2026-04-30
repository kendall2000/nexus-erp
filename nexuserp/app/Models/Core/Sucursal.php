<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table      = 'sucursal';
    protected $primaryKey = 'id_sucursal';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_pais',
        'id_division',
        'id_municipio',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'es_casa_matriz',
        'activo',
    ];

    protected $casts = [
        'es_casa_matriz' => 'boolean',
        'activo'         => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'id_pais');
    }

    public function division()
    {
        return $this->belongsTo(DivisionGeografica::class, 'id_division');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'id_municipio');
    }

    public function empleados()
    {
        return $this->hasMany(\App\Models\RRHH\Empleado::class, 'id_sucursal');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeCasaMatriz($query)
    {
        return $query->where('es_casa_matriz', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}