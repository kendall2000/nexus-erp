<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;

class PeriodoNomina extends Model
{
    protected $table      = 'periodo_nomina';
    protected $primaryKey = 'id_periodo';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'nombre',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'estado',
        'moneda',
        'total_bruto',
        'total_deducciones',
        'total_neto',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio'     => 'date',
        'fecha_fin'        => 'date',
        'fecha_pago'       => 'date',
        'total_bruto'      => 'decimal:4',
        'total_deducciones'=> 'decimal:4',
        'total_neto'       => 'decimal:4',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleNomina::class, 'id_periodo');
    }

    public function getTotalEmpleadosAttribute(): int
    {
        return $this->detalles()->count();
    }

    public function recalcularTotales(): void
    {
        $this->update([
            'total_bruto'       => $this->detalles()->sum('total_ingresos'),
            'total_deducciones' => $this->detalles()->sum('total_deducciones'),
            'total_neto'        => $this->detalles()->sum('liquido_pagar'),
        ]);
    }

    public function cerrar(): void
    {
        $this->recalcularTotales();
        $this->update(['estado' => 'CERRADO']);
    }

    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'ABIERTO');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'PAGADO');
    }
}