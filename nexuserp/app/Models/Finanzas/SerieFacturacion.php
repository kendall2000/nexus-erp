<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class SerieFacturacion extends Model
{
    protected $table      = 'serie_facturacion';
    protected $primaryKey = 'id_serie';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'codigo_serie',
        'tipo',
        'descripcion',
        'ultimo_numero',
        'activo',
    ];

    protected $casts = [
        'ultimo_numero' => 'integer',
        'activo'        => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'id_serie');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function siguienteNumero(): int
    {
        // Incrementa y retorna el siguiente número de forma atómica
        $this->increment('ultimo_numero');
        return $this->fresh()->ultimo_numero;
    }

    public function formatearNumero(int $numero): string
    {
        return $this->codigo_serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}