<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table      = 'cliente';
    protected $primaryKey = 'id_cliente';
    public $timestamps    = true;
    const DELETED_AT      = 'deleted_at';

    protected $fillable = [
        'id_empresa',
        'id_industria',
        'id_pais',
        'id_municipio',
        'razon_social',
        'nombre_comercial',
        'nit',
        'tipo_persona',
        'email_principal',
        'telefono_principal',
        'sitio_web',
        'direccion_fiscal',
        'segmento',
        'categoria',
        'moneda_facturacion',
        'dias_credito',
        'limite_credito',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activo'          => 'boolean',
        'dias_credito'    => 'integer',
        'limite_credito'  => 'decimal:4',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function industria()
    {
        return $this->belongsTo(Industria::class, 'id_industria');
    }

    public function pais()
    {
        return $this->belongsTo(\App\Models\Core\Pais::class, 'id_pais');
    }

    public function municipio()
    {
        return $this->belongsTo(\App\Models\Core\Municipio::class, 'id_municipio');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Core\Moneda::class, 'moneda_facturacion', 'codigo');
    }

    public function contactos()
    {
        return $this->hasMany(ContactoCliente::class, 'id_cliente');
    }

    public function contactoPrincipal()
    {
        return $this->hasOne(ContactoCliente::class, 'id_cliente')
                    ->where('es_contacto_principal', true);
    }

    public function sitiosTrabajo()
    {
        return $this->hasMany(SitioTrabajo::class, 'id_cliente');
    }

    public function contratos()
    {
        return $this->hasMany(ContratoServicio::class, 'id_cliente');
    }

    public function contratosVigentes()
    {
        return $this->hasMany(ContratoServicio::class, 'id_cliente')
                    ->where('estado', 'VIGENTE');
    }

    public function facturas()
    {
        return $this->hasMany(\App\Models\Finanzas\Factura::class, 'id_cliente');
    }

    public function pagos()
    {
        return $this->hasMany(\App\Models\Finanzas\Pago::class, 'id_cliente');
    }

    public function tickets()
    {
        return $this->hasMany(\App\Models\CRM\Ticket::class, 'id_cliente');
    }

    public function oportunidades()
    {
        return $this->hasMany(\App\Models\CRM\Oportunidad::class, 'id_cliente');
    }

    public function evaluaciones()
    {
        return $this->hasMany(\App\Models\CRM\EvaluacionSatisfaccion::class, 'id_cliente');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre_comercial ?? $this->razon_social;
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->facturas()
            ->whereIn('estado', ['EMITIDA', 'ENVIADA', 'PARCIAL', 'VENCIDA'])
            ->sum('saldo_pendiente');
    }

    public function tieneCreditoDisponible(float $monto): bool
    {
        if (is_null($this->limite_credito)) return true;
        return ($this->getSaldoPendienteAttribute() + $monto) <= $this->limite_credito;
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

    public function scopePorSegmento($query, string $segmento)
    {
        return $query->where('segmento', $segmento);
    }

    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('razon_social', 'LIKE', "%{$termino}%")
              ->orWhere('nombre_comercial', 'LIKE', "%{$termino}%")
              ->orWhere('nit', 'LIKE', "%{$termino}%")
              ->orWhere('email_principal', 'LIKE', "%{$termino}%");
        });
    }
}