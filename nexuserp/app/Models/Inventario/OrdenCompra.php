<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table      = 'orden_compra';
    protected $primaryKey = 'id_oc';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_proveedor',
        'id_bodega',
        'numero_oc',
        'fecha_emision',
        'fecha_entrega_esperada',
        'fecha_entrega_real',
        'moneda',
        'subtotal',
        'iva',
        'total',
        'estado',
        'notas',
        'created_by',
        'aprobado_por',
    ];

    protected $casts = [
        'fecha_emision'         => 'date',
        'fecha_entrega_esperada'=> 'date',
        'fecha_entrega_real'    => 'date',
        'subtotal'              => 'decimal:4',
        'iva'                   => 'decimal:4',
        'total'                 => 'decimal:4',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleOrdenCompra::class, 'id_oc');
    }

    public function creadoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'created_by');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'aprobado_por');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function estaCompleta(): bool
    {
        return $this->estado === 'RECIBIDA';
    }

    public function getPorcentajeRecepcionAttribute(): float
    {
        $pedido  = $this->detalles()->sum('cantidad_pedida');
        $recibido= $this->detalles()->sum('cantidad_recibida');
        if ($pedido == 0) return 0;
        return round(($recibido / $pedido) * 100, 2);
    }

    public function recalcularTotales(): void
    {
        $subtotal = $this->detalles()->sum('subtotal');
        $iva      = round($subtotal * 0.12, 4);
        $this->update([
            'subtotal' => $subtotal,
            'iva'      => $iva,
            'total'    => $subtotal + $iva,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['ENVIADA', 'PARCIAL']);
    }

    public function scopePorProveedor($query, $idProveedor)
    {
        return $query->where('id_proveedor', $idProveedor);
    }

    public function scopeAtrasadas($query)
    {
        return $query->whereIn('estado', ['ENVIADA', 'PARCIAL'])
                     ->whereNotNull('fecha_entrega_esperada')
                     ->where('fecha_entrega_esperada', '<', today());
    }
}