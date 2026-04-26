<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use SoftDeletes;

    protected $table      = 'proveedor';
    protected $primaryKey = 'id_proveedor';
    public $timestamps    = true;
    const DELETED_AT      = 'deleted_at';

    protected $fillable = [
        'id_empresa',
        'id_pais',
        'razon_social',
        'nombre_comercial',
        'nit',
        'email',
        'telefono',
        'direccion',
        'contacto',
        'tipo_proveedor',
        'dias_credito',
        'moneda_pago',
        'activo',
    ];

    protected $casts = [
        'dias_credito' => 'integer',
        'activo'       => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function pais()
    {
        return $this->belongsTo(\App\Models\Core\Pais::class, 'id_pais');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda_pago', 'codigo');
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'id_proveedor');
    }

    public function ordenesVigentes()
    {
        return $this->hasMany(OrdenCompra::class, 'id_proveedor')
                    ->whereIn('estado', ['ENVIADA', 'PARCIAL']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getNombreAttribute(): string
    {
        return $this->nombre_comercial ?? $this->razon_social;
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('razon_social', 'LIKE', "%{$termino}%")
              ->orWhere('nombre_comercial', 'LIKE', "%{$termino}%")
              ->orWhere('nit', 'LIKE', "%{$termino}%");
        });
    }
}