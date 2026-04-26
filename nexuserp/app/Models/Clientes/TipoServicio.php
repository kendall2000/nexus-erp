<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class TipoServicio extends Model
{
    protected $table      = 'tipo_servicio';
    protected $primaryKey = 'id_tipo_servicio';
    public $timestamps    = false;

    protected $fillable = [
        'id_linea',
        'nombre',
        'descripcion',
        'unidad_medida',
        'precio_base',
        'moneda',
        'activo',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'precio_base' => 'decimal:4',
        'created_at'  => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function lineaNegocio()
    {
        return $this->belongsTo(\App\Models\Core\LineaNegocio::class, 'id_linea');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo');
    }

    public function detallesContrato()
    {
        return $this->hasMany(ContratoServicioDetalle::class, 'id_tipo_servicio');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorLinea($query, $idLinea)
    {
        return $query->where('id_linea', $idLinea);
    }
}