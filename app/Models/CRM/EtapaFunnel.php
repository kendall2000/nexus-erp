<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class EtapaFunnel extends Model
{
    protected $table      = 'etapa_funnel';
    protected $primaryKey = 'id_etapa';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'nombre',
        'descripcion',
        'orden',
        'color_hex',
        'probabilidad_cierre',
        'es_ganada',
        'es_perdida',
        'activo',
    ];

    protected $casts = [
        'es_ganada'           => 'boolean',
        'es_perdida'          => 'boolean',
        'activo'              => 'boolean',
        'orden'               => 'integer',
        'probabilidad_cierre' => 'integer',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function oportunidades()
    {
        return $this->hasMany(Oportunidad::class, 'id_etapa');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopeAbiertas($query)
    {
        return $query->where('es_ganada', false)->where('es_perdida', false);
    }
}