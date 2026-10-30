<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Campana extends Model
{
    protected $table      = 'campana';
    protected $primaryKey = 'id_campana';
    public $timestamps    = true;

    protected $fillable = [
        'id_empresa',
        'id_linea',
        'nombre',
        'descripcion',
        'tipo',
        'objetivo',
        'fecha_inicio',
        'fecha_fin',
        'presupuesto',
        'gasto_real',
        'moneda',
        'meta_leads',
        'leads_generados',
        'estado',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio'    => 'date',
        'fecha_fin'       => 'date',
        'presupuesto'     => 'decimal:4',
        'gasto_real'      => 'decimal:4',
        'meta_leads'      => 'integer',
        'leads_generados' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function lineaNegocio()
    {
        return $this->belongsTo(\App\Models\Core\LineaNegocio::class, 'id_linea');
    }

    public function contactos()
    {
        return $this->hasMany(CampanaContacto::class, 'id_campana');
    }

    public function getROIAttribute(): ?float
    {
        if (!$this->gasto_real || $this->gasto_real == 0) return null;
        return round((($this->leads_generados / $this->meta_leads) * 100), 2);
    }

    public function getPorcentajePresupuestoAttribute(): float
    {
        if (!$this->presupuesto || $this->presupuesto == 0) return 0;
        return round(($this->gasto_real / $this->presupuesto) * 100, 2);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'ACTIVA');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}