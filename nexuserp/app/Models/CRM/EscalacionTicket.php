<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class EscalacionTicket extends Model
{
    protected $table      = 'escalacion_ticket';
    protected $primaryKey = 'id_escalacion';
    public $timestamps    = false;

    protected $fillable = [
        'id_ticket',
        'escalado_por',
        'escalado_a',
        'id_usuario',
        'motivo',
        'nivel',
    ];

    protected $casts = [
        'nivel'      => 'integer',
        'created_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function escaladoPor()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'escalado_por');
    }

    public function escaladoA()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'escalado_a');
    }

    /** Usuario del sistema que escribió o escaló (puede no ser empleado). */
    public function usuario()
    {
        return $this->belongsTo(\App\Models\Core\Usuario::class, 'id_usuario');
    }
}
