<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class TicketComentario extends Model
{
    protected $table      = 'ticket_comentario';
    protected $primaryKey = 'id_comentario';
    public $timestamps    = false;

    protected $fillable = [
        'id_ticket',
        'id_autor',
        'es_nota_interna',
        'contenido',
    ];

    protected $casts = [
        'es_nota_interna' => 'boolean',
        'created_at'      => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function autor()
    {
        return $this->belongsTo(\App\Models\RRHH\Empleado::class, 'id_autor');
    }

    public function adjuntos()
    {
        return $this->hasMany(TicketAdjunto::class, 'id_comentario');
    }

    public function scopePublicos($query)
    {
        return $query->where('es_nota_interna', false);
    }

    public function scopeInternos($query)
    {
        return $query->where('es_nota_interna', true);
    }
}