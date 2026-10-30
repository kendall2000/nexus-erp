<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class SitioTrabajo extends Model
{
    protected $table      = 'sitio_trabajo';
    protected $primaryKey = 'id_sitio';
    public $timestamps    = false;

    protected $fillable = [
        'id_cliente',
        'id_municipio',
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'responsable_cliente',
        'tel_responsable',
        'activo',
    ];

    protected $casts = [
        'activo'    => 'boolean',
        'latitud'   => 'decimal:7',
        'longitud'  => 'decimal:7',
        'created_at'=> 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function municipio()
    {
        return $this->belongsTo(\App\Models\Core\Municipio::class, 'id_municipio');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionContrato::class, 'id_sitio');
    }

    public function detallesContrato()
    {
        return $this->hasMany(ContratoServicioDetalle::class, 'id_sitio');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getCoordenadas(): ?array
    {
        if ($this->latitud && $this->longitud) {
            return ['lat' => $this->latitud, 'lng' => $this->longitud];
        }
        return null;
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('id_cliente', $idCliente);
    }
}