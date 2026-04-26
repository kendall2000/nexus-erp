<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table      = 'pago';
    protected $primaryKey = 'id_pago';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_factura',
        'referencia',
        'forma_pago',
        'banco_origen',
        'monto',
        'moneda',
        'fecha_pago',
        'fecha_acreditado',
        'comprobante_url',
        'notas',
        'created_by',
    ];

    protected $casts = [
        'monto'            => 'decimal:4',
        'fecha_pago'       => 'date',
        'fecha_acreditado' => 'date',
        'created_at'       => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes\Cliente::class, 'id_cliente');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function creadoPor()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'created_by');
    }

    // ── Hooks ───────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Al crear un pago, actualiza automáticamente la factura
        static::created(function (Pago $pago) {
            if ($pago->id_factura) {
                $pago->factura->registrarPago($pago->monto);
            }
        });
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('id_cliente', $idCliente);
    }

    public function scopePorFormaPago($query, string $forma)
    {
        return $query->where('forma_pago', $forma);
    }

    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_pago', [$inicio, $fin]);
    }

    public function scopePendientesAcreditar($query)
    {
        return $query->whereNull('fecha_acreditado');
    }
}