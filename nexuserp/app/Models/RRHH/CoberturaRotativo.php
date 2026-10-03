<?php

namespace App\Models\RRHH;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CoberturaRotativo extends Model
{
    protected $table      = 'cobertura_rotativo';
    protected $primaryKey = 'id_cobertura';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_rotativo',
        'id_titular',
        'motivo',
        'fecha_inicio',
        'fecha_fin',
        'paga_fines_semana',
        'dias',
        'tarifa_dia',
        'total',
        'estado',
        'observaciones',
        'created_by',
        'anulada_por',
    ];

    protected $casts = [
        'fecha_inicio'      => 'date',
        'fecha_fin'         => 'date',
        'paga_fines_semana' => 'boolean',
        'dias'              => 'decimal:2',
        'tarifa_dia'        => 'decimal:4',
        'total'             => 'decimal:4',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function rotativo()
    {
        return $this->belongsTo(Empleado::class, 'id_rotativo')->withTrashed();
    }

    public function titular()
    {
        return $this->belongsTo(Empleado::class, 'id_titular')->withTrashed();
    }

    /** Días que se pagan entre dos fechas (inclusive), con o sin sábados y domingos. */
    public static function diasEntre(Carbon $desde, Carbon $hasta, bool $finesSemana): int
    {
        if ($hasta->lt($desde)) {
            return 0;
        }

        return collect(CarbonPeriod::create($desde->copy()->startOfDay(), $hasta->copy()->startOfDay()))
            ->reject(fn ($d) => ! $finesSemana && $d->isWeekend())->count();
    }

    /** Días de esta cobertura que caen dentro de un rango (el periodo de nómina). */
    public function diasEn(Carbon $desde, Carbon $hasta): int
    {
        return self::diasEntre($this->fecha_inicio->max($desde), $this->fecha_fin->min($hasta), $this->paga_fines_semana);
    }

    public function scopeVigentes($query)
    {
        return $query->where('estado', 'VIGENTE');
    }

    /** Coberturas que tocan el rango de fechas. */
    public function scopeEntre($query, $desde, $hasta)
    {
        return $query->whereDate('fecha_inicio', '<=', $hasta)->whereDate('fecha_fin', '>=', $desde);
    }
}
