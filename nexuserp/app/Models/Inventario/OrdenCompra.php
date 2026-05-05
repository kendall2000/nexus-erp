<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Core\Empresa;
use App\Models\Finanzas\PresupuestoAnual;

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
    public function empresa()       { return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa'); }
    public function proveedor()     { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function bodega()        { return $this->belongsTo(Bodega::class, 'id_bodega'); }
    public function monedaRel()     { return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda', 'codigo'); }
    public function detalles()      { return $this->hasMany(DetalleOrdenCompra::class, 'id_oc'); }
    public function creadoPor()     { return $this->belongsTo(\App\Models\Core\Usuario::class, 'created_by'); }
    public function aprobadoPor()   { return $this->belongsTo(\App\Models\Core\Usuario::class, 'aprobado_por'); }

    // ── Helpers ─────────────────────────────────────────────────────────────
    public function estaCompleta(): bool
    {
        return $this->estado === 'RECIBIDA';
    }

    public function getPorcentajeRecepcionAttribute(): float
    {
        $pedido   = $this->detalles()->sum('cantidad_pedida');
        $recibido = $this->detalles()->sum('cantidad_recibida');
        if ($pedido == 0) return 0;
        return round(($recibido / $pedido) * 100, 2);
    }

    /**
     * Recalcula totales usando la tasa de IVA configurada por empresa.
     * En OC el precio típicamente NO incluye IVA (precio de proveedor),
     * pero respetamos la config de empresa por consistencia.
     */
    public function recalcularTotales(): void
    {
        $empresa     = Empresa::find($this->id_empresa);
        $tasaIva     = $empresa ? $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : false; // OC default: precio sin IVA

        $subtotal = $this->detalles()->sum('subtotal');
        $iva      = round($subtotal * $tasaIva, 4);

        $total = $ivaIncluido
            ? round($subtotal, 4)              // IVA ya incluido en líneas
            : round($subtotal + $iva, 4);      // IVA se suma al final

        $this->update([
            'subtotal' => $subtotal,
            'iva'      => $iva,
            'total'    => $total,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────
    public function scopePorEmpresa($q, $idEmpresa)   { return $q->where('id_empresa', $idEmpresa); }
    public function scopePendientes($q)               { return $q->whereIn('estado', ['ENVIADA', 'PARCIAL']); }
    public function scopePorProveedor($q, $idProv)    { return $q->where('id_proveedor', $idProv); }
    public function scopeAtrasadas($q)
    {
        return $q->whereIn('estado', ['ENVIADA', 'PARCIAL'])
                 ->whereNotNull('fecha_entrega_esperada')
                 ->where('fecha_entrega_esperada', '<', today());
    }

    // ════════════════════════════════════════════════════════════════════════
    // HOOK: actualizar presupuesto al aprobar / cancelar
    // ════════════════════════════════════════════════════════════════════════
    protected static function booted(): void
    {
        static::updated(function (self $oc) {
            // Pasa a ENVIADA (aprobada) → SUMA al ejecutado del presupuesto
            if ($oc->wasChanged('estado') && $oc->estado === 'ENVIADA') {
                self::actualizarPresupuestoPorAprobacion($oc);
            }

            // Pasa a CANCELADA → REVIERTE lo que se sumó
            if ($oc->wasChanged('estado') && $oc->estado === 'CANCELADA') {
                $estadosOriginalSumaron = ['ENVIADA', 'PARCIAL', 'RECIBIDA'];
                $estadoOriginal = $oc->getOriginal('estado');
                if (in_array($estadoOriginal, $estadosOriginalSumaron)) {
                    self::revertirPresupuestoPorCancelacion($oc);
                }
            }
        });
    }

    /**
     * Suma la BASE NETA (sin IVA) de cada línea al presupuesto de gastos.
     */
    private static function actualizarPresupuestoPorAprobacion(self $oc): void
    {
        $oc->load('detalles.producto');

        $empresa     = Empresa::find($oc->id_empresa);
        $tasaIva     = $empresa ? $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : false;

        $mes  = $oc->fecha_emision->month;
        $anio = $oc->fecha_emision->year;

        foreach ($oc->detalles as $linea) {
            $idCentro = $linea->centro_efectivo;
            $idCuenta = $linea->cuenta_efectiva;

            if (!$idCentro || !$idCuenta) continue;

            $subtotal  = (float) $linea->subtotal;
            // En OC asumimos que cada línea es afecta a IVA (los gastos típicamente lo son)
            $montoNeto = self::calcularMontoNeto($subtotal, true, $tasaIva, $ivaIncluido);

            $presupuesto = PresupuestoAnual::where('id_empresa', $oc->id_empresa)
                ->where('id_centro', $idCentro)
                ->where('id_cuenta', $idCuenta)
                ->where('anio', $anio)
                ->where('estado', 'APROBADO')
                ->first();

            if ($presupuesto) {
                try {
                    $presupuesto->registrarEjecucion($mes, $montoNeto);
                } catch (\Exception $e) {
                    Log::warning("No se pudo registrar ejecución de OC en presupuesto: {$e->getMessage()}", [
                        'oc'         => $oc->id_oc,
                        'linea'      => $linea->id_linea,
                        'centro'     => $idCentro,
                        'cuenta'     => $idCuenta,
                        'monto_neto' => $montoNeto,
                    ]);
                }
            }
        }
    }

    /**
     * Resta los montos al presupuesto cuando se cancela la OC.
     */
    private static function revertirPresupuestoPorCancelacion(self $oc): void
    {
        $oc->load('detalles.producto');

        $empresa     = Empresa::find($oc->id_empresa);
        $tasaIva     = $empresa ? $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : false;

        $mes       = $oc->fecha_emision->month;
        $anio      = $oc->fecha_emision->year;
        $nombreMes = PresupuestoAnual::MESES[$mes] ?? null;

        if (!$nombreMes) return;

        foreach ($oc->detalles as $linea) {
            $idCentro = $linea->centro_efectivo;
            $idCuenta = $linea->cuenta_efectiva;

            if (!$idCentro || !$idCuenta) continue;

            $subtotal  = (float) $linea->subtotal;
            $montoNeto = self::calcularMontoNeto($subtotal, true, $tasaIva, $ivaIncluido);

            $presupuesto = PresupuestoAnual::where('id_empresa', $oc->id_empresa)
                ->where('id_centro', $idCentro)
                ->where('id_cuenta', $idCuenta)
                ->where('anio', $anio)
                ->first();

            if ($presupuesto) {
                $presupuesto->decrement("eje_{$nombreMes}", $montoNeto);
                $presupuesto->decrement('total_ejecutado', $montoNeto);
            }
        }
    }

    /**
     * Calcula el monto NETO (sin IVA) que va al presupuesto.
     * Mismo helper que en Factura para consistencia.
     */
    private static function calcularMontoNeto(
        float $subtotal,
        bool $esAfectoIva,
        float $tasaIva,
        bool $ivaIncluido
    ): float {
        if (!$esAfectoIva)  return round($subtotal, 4);
        if ($ivaIncluido)   return round($subtotal / (1 + $tasaIva), 4);
        return round($subtotal, 4);
    }
}