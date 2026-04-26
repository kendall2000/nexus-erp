<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class CampanaContacto extends Model
{
    protected $table      = 'campana_contacto';
    protected $primaryKey = 'id_contacto_campana';
    public $timestamps    = false;

    protected $fillable = [
        'id_campana',
        'tipo_contacto',
        'id_prospecto',
        'id_cliente',
        'estado_envio',
        'fecha_envio',
        'fecha_apertura',
        'resultado',
    ];

    protected $casts = [
        'fecha_envio'    => 'datetime',
        'fecha_apertura' => 'datetime',
    ];

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'id_campana');
    }

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'id_prospecto');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes\Cliente::class, 'id_cliente');
    }

    public function scopeEnviados($query)
    {
        return $query->where('estado_envio', 'ENVIADO');
    }

    public function scopeAbiertos($query)
    {
        return $query->where('estado_envio', 'ABIERTO');
    }
}