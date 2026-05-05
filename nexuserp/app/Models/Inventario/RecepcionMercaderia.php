<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class RecepcionMercaderia extends Model
{
    protected $table      = 'recepcion_mercaderia';
    protected $primaryKey = 'id_recepcion';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_oc',
        'id_bodega',
        'numero_recepcion',
        'fecha_recepcion',
        'notas',
        'created_by',
    ];

    protected $casts = [
        'fecha_recepcion' => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

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

    public function creadoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'created_by');
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopePorEmpresa($q, $idEmpresa)
    {
        return $q->where('id_empresa', $idEmpresa);
    }

    public function scopePorOC($q, $idOC)
    {
        return $q->where('id_oc', $idOC);
    }
}