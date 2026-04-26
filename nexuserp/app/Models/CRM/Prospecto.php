<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospecto extends Model
{
    use SoftDeletes;

    protected $table      = 'prospecto';
    protected $primaryKey = 'id_prospecto';
    public $timestamps    = true;
    const DELETED_AT      = 'deleted_at';

    protected $fillable = [
        'id_empresa',
        'id_fuente',
        'id_asignado_a',
        'id_pais',
        'id_industria',
        'nombre_empresa',
        'sitio_web',
        'empleados_estimados',
        'nombre_contacto',
        'cargo_contacto',
        'email_contacto',
        'telefono_contacto',
        'whatsapp_contacto',
        'temperatura',
        'puntuacion_lead',
        'interes_servicio',
        'presupuesto_estimado',
        'moneda',
        'estado',
        'motivo_descarte',
        'fecha_conversion',
        'id_cliente_generado',
        'notas',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'puntuacion_lead'     => 'integer',
        'presupuesto_estimado'=> 'decimal:4',
        'fecha_conversion'    => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function fuente()
    {
        return $this->belongsTo(FuenteLead::class, 'id_fuente');
    }

    public function asignadoA()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_asignado_a');
    }

    public function pais()
    {
        return $this->belongsTo(\App\Models\Core\Pais::class, 'id_pais');
    }

    public function industria()
    {
        return $this->belongsTo(\App\Models\Clientes\Industria::class, 'id_industria');
    }

    public function clienteGenerado()
    {
        return $this->belongsTo(\App\Models\Clientes\Cliente::class, 'id_cliente_generado');
    }

    public function seguimientos()
    {
        return $this->hasMany(SeguimientoProspecto::class, 'id_prospecto')
                    ->orderBy('fecha_hora', 'desc');
    }

    public function ultimoSeguimiento()
    {
        return $this->hasOne(SeguimientoProspecto::class, 'id_prospecto')
                    ->latestOfMany('fecha_hora');
    }

    public function oportunidades()
    {
        return $this->hasMany(Oportunidad::class, 'id_prospecto');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function convertirACliente(): bool
    {
        return $this->estado === 'CONVERTIDO' && !is_null($this->id_cliente_generado);
    }

    public function estaCaliente(): bool
    {
        return $this->temperatura === 'CALIENTE';
    }

    public function diasSinSeguimiento(): int
    {
        $ultimo = $this->ultimoSeguimiento;
        if (!$ultimo) return now()->diffInDays($this->created_at);
        return now()->diffInDays($ultimo->fecha_hora);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->whereNotIn('estado', ['CONVERTIDO', 'DESCARTADO']);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorVendedor($query, $idEmpleado)
    {
        return $query->where('id_asignado_a', $idEmpleado);
    }

    public function scopeCalientes($query)
    {
        return $query->where('temperatura', 'CALIENTE');
    }

    public function scopeSinSeguimientoEn($query, int $dias = 7)
    {
        return $query->whereDoesntHave('seguimientos', function ($q) use ($dias) {
            $q->where('fecha_hora', '>=', now()->subDays($dias));
        });
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre_empresa', 'LIKE', "%{$termino}%")
              ->orWhere('nombre_contacto', 'LIKE', "%{$termino}%")
              ->orWhere('email_contacto', 'LIKE', "%{$termino}%");
        });
    }
}