<?php

namespace App\Services\CRM;

use App\Models\CRM\EtapaFunnel;
use App\Models\CRM\Ticket;
use App\Support\Crm;
use App\Support\Sla;
use Illuminate\Support\Collection;

/**
 * Servicio de dominio CRM y Atención al Cliente.
 *
 * Provee una interfaz inyectable sobre la lógica de etapas de embudo y SLA,
 * manteniendo compatibilidad con las llamadas estáticas de App\Support\{Crm,Sla}.
 */
class CrmService
{
    /** @return Collection<int, EtapaFunnel> */
    public function etapasEmbudo(int $idEmpresa): Collection
    {
        return Crm::etapas($idEmpresa);
    }

    public function calcularSla(Ticket $ticket): array
    {
        return Sla::estado($ticket);
    }
}
