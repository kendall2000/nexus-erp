<?php

namespace App\Models\Core;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use PerteneceAEmpresa;

    protected $table      = 'sucursal';
    protected $primaryKey = 'id_sucursal';
    public $timestamps    = true;

    /** Tipo => etiqueta. Una agencia depende de una sucursal. */
    public const TIPOS = ['SUCURSAL' => 'Sucursal', 'AGENCIA' => 'Agencia'];

    protected $fillable = [
        'id_empresa',
        'tipo',
        'id_sucursal_padre',
        'codigo_establecimiento',
        'id_pais',
        'id_division',
        'id_municipio',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'es_casa_matriz',
        'activo',
    ];

    protected $casts = [
        'es_casa_matriz' => 'boolean',
        'activo'         => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'id_pais');
    }

    public function division()
    {
        return $this->belongsTo(DivisionGeografica::class, 'id_division');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'id_municipio');
    }

    public function padre()
    {
        return $this->belongsTo(self::class, 'id_sucursal_padre');
    }

    public function agencias()
    {
        return $this->hasMany(self::class, 'id_sucursal_padre');
    }

    public function esAgencia(): bool
    {
        return $this->tipo === 'AGENCIA';
    }

    /** Nombre para listas: la agencia lleva la sucursal de la que depende. */
    public function etiqueta(): string
    {
        return $this->esAgencia() && $this->padre ? "{$this->nombre} (agencia de {$this->padre->nombre})" : $this->nombre;
    }

    /**
     * Sedes activas de la empresa para un select: cada sucursal seguida de sus agencias.
     * $incluir: una sede que debe aparecer aunque esté inactiva (la que ya tiene el registro).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function paraSelect(int $idEmpresa, ?int $incluir = null)
    {
        $sedes = self::query()->where('id_empresa', $idEmpresa)
            ->where(fn ($q) => $q->where('activo', true)->when($incluir, fn ($q) => $q->orWhere('id_sucursal', $incluir)))
            ->with('padre')->orderByDesc('es_casa_matriz')->orderBy('nombre')->get();

        return self::enArbol($sedes);
    }

    /**
     * Cada sucursal seguida de sus agencias, conservando el orden recibido; las agencias cuya
     * sucursal no está en la lista van al final.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $sedes
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function enArbol($sedes)
    {
        $agencias = $sedes->filter->esAgencia()->groupBy('id_sucursal_padre');

        return $sedes->reject->esAgencia()->flatMap(fn ($s) => collect([$s])->concat($agencias->pull($s->id_sucursal) ?? []))
            ->concat($agencias->flatten())->values();
    }

    public function empleados()
    {
        return $this->hasMany(\App\Models\RRHH\Empleado::class, 'id_sucursal');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_sucursal');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeCasaMatriz($query)
    {
        return $query->where('es_casa_matriz', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}