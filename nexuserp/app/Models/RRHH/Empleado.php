<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use SoftDeletes;

    protected $table      = 'empleado';
    protected $primaryKey = 'id_empleado';
    public $timestamps    = true;
    const DELETED_AT      = 'deleted_at';

    protected $fillable = [
        'id_empresa',
        'id_sucursal',
        'id_cargo',
        'id_depto_org',
        'id_supervisor',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'apellido_casada',
        'dpi_nit',
        'tipo_doc_id',
        'nit_personal',
        'igss_afiliacion',
        'fecha_nacimiento',
        'genero',
        'estado_civil',
        'nacionalidad',
        'email_personal',
        'email_corporativo',
        'telefono_personal',
        'telefono_emergencia',
        'contacto_emergencia',
        'id_municipio',
        'direccion',
        'codigo_empleado',
        'fecha_ingreso',
        'fecha_baja',
        'tipo_contrato',
        'modalidad_trabajo',
        'estado',
        'motivo_baja',
        'foto_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
        'fecha_baja'       => 'date',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected $hidden = ['dpi_nit', 'nit_personal', 'igss_afiliacion'];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function sucursal()
    {
        return $this->belongsTo(\App\Models\Core\Sucursal::class, 'id_sucursal');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function departamento()
    {
        return $this->belongsTo(DepartamentoOrg::class, 'id_depto_org');
    }

    public function supervisor()
    {
        return $this->belongsTo(Empleado::class, 'id_supervisor');
    }

    public function subordinados()
    {
        return $this->hasMany(Empleado::class, 'id_supervisor');
    }

    public function municipio()
    {
        return $this->belongsTo(\App\Models\Core\Municipio::class, 'id_municipio');
    }

    public function documentos()
    {
        return $this->hasMany(EmpleadoDocumento::class, 'id_empleado');
    }

    public function historialSalarial()
    {
        return $this->hasMany(HistorialSalarial::class, 'id_empleado')
                    ->orderBy('fecha_efectiva', 'desc');
    }

    public function salarioActual()
    {
        return $this->hasOne(HistorialSalarial::class, 'id_empleado')
                    ->latestOfMany('fecha_efectiva');
    }

    public function contratos()
    {
        return $this->hasMany(ContratoLaboral::class, 'id_empleado');
    }

    public function contratoVigente()
    {
        return $this->hasOne(ContratoLaboral::class, 'id_empleado')
                    ->where('estado', 'VIGENTE');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_empleado');
    }

    public function solicitudesAusencia()
    {
        return $this->hasMany(SolicitudAusencia::class, 'id_empleado');
    }

    public function nominaDetalles()
    {
        return $this->hasMany(DetalleNomina::class, 'id_empleado');
    }

    public function prestamos()
    {
        return $this->hasMany(PrestamoEmpleado::class, 'id_empleado')
                    ->where('estado', 'ACTIVO');
    }

    public function prestaciones()
    {
        return $this->hasMany(PrestacionLaboral::class, 'id_empleado');
    }

    public function asignacionesContrato()
    {
        return $this->hasMany(\App\Models\Clientes\AsignacionContrato::class, 'id_empleado');
    }

    public function asignacionesActivas()
    {
        return $this->hasMany(\App\Models\Clientes\AsignacionContrato::class, 'id_empleado')
                    ->where('activo', true);
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido,
            $this->apellido_casada,
        ])));
    }

    public function getNombreCortoAttribute(): string
    {
        return "{$this->primer_nombre} {$this->primer_apellido}";
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento
            ? $this->fecha_nacimiento->diffInYears(now())
            : null;
    }

    public function getAntiguedadMesesAttribute(): int
    {
        $hasta = $this->fecha_baja ?? now();
        return $this->fecha_ingreso->diffInMonths($hasta);
    }

    public function getAntiguedadAniosAttribute(): float
    {
        return round($this->antiguedad_meses / 12, 1);
    }

    public function getFotoUrlAttribute($value): string
    {
        return $value ?? asset('images/avatar-default.png');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function estaActivo(): bool
    {
        return $this->estado === 'ACTIVO';
    }

    public function tieneAsignacionActiva(): bool
    {
        return $this->asignacionesActivas()->exists();
    }

    public function getSalarioBase(): float
    {
        $salario = $this->salarioActual;
        return $salario ? $salario->salario_nuevo : 0;
    }

    public function calcularIgss(): float
    {
        // Cuota empleado IGSS Guatemala: 4.83%
        return round($this->getSalarioBase() * 0.0483, 4);
    }

    public function calcularIgssPatronal(): float
    {
        // Cuota patronal IGSS Guatemala: 12.67%
        return round($this->getSalarioBase() * 0.1267, 4);
    }

    public function darDeBaja(string $motivo, ?string $fecha = null): void
    {
        $this->update([
            'estado'      => 'BAJA',
            'fecha_baja'  => $fecha ?? today(),
            'motivo_baja' => $motivo,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO')->whereNull('deleted_at');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePorSucursal($query, $idSucursal)
    {
        return $query->where('id_sucursal', $idSucursal);
    }

    public function scopePorDepartamento($query, $idDepto)
    {
        return $query->where('id_depto_org', $idDepto);
    }

    public function scopePorCargo($query, $idCargo)
    {
        return $query->where('id_cargo', $idCargo);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('primer_nombre',    'LIKE', "%{$termino}%")
              ->orWhere('primer_apellido', 'LIKE', "%{$termino}%")
              ->orWhere('codigo_empleado', 'LIKE', "%{$termino}%")
              ->orWhere('email_corporativo','LIKE', "%{$termino}%");
        });
    }

    public function scopeCumpleaniosHoy($query)
    {
        return $query->whereMonth('fecha_nacimiento', now()->month)
                     ->whereDay('fecha_nacimiento', now()->day);
    }

    public function scopeAniversariosHoy($query)
    {
        return $query->whereMonth('fecha_ingreso', now()->month)
                     ->whereDay('fecha_ingreso', now()->day);
    }
}