@extends('layouts.app', ['titulo' => 'Campañas'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Campañas</h2>
            <p class="text-700 fw-semi-bold mb-0">Acciones de marketing: presupuesto, alcance y prospectos generados.</p>
        </div>
        @if ($yo->puede('campanas.crear'))
            <a class="btn btn-primary" href="{{ route('campanas.create') }}"><span class="fas fa-plus me-2"></span>Nueva campaña</a>
        @endif
    </div>
    <div class="card"><div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm fs--1 mb-3 align-middle">
                <thead><tr><th>Campaña</th><th>Tipo · objetivo</th><th>Fechas</th><th class="text-end">Gasto / presupuesto</th><th class="text-end">Leads / meta</th><th class="text-end">Contactos</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse ($campanas as $c)
                    @php [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary']; @endphp
                    <tr>
                        <td><a class="fw-semi-bold" href="{{ route('campanas.show', $c->id_campana) }}">{{ $c->nombre }}</a><span class="d-block fs--2 text-600">{{ $c->lineaNegocio?->nombre }}</span></td>
                        <td>{{ $tipos[$c->tipo] ?? $c->tipo }}<span class="d-block fs--2 text-600">{{ $objetivos[$c->objetivo] ?? $c->objetivo }}</span></td>
                        <td class="text-nowrap">{{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? '…' }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $c->gasto_real, 2) }} / {{ $c->presupuesto !== null ? number_format((float) $c->presupuesto, 2) : '—' }}</td>
                        <td class="text-end">{{ $c->leads_generados }} / {{ $c->meta_leads ?? '—' }}</td>
                        <td class="text-end">{{ $c->contactos_count }}</td>
                        <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-700 py-4">Todavía no hay campañas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $campanas->links('pagination::bootstrap-5') }}
    </div></div>
@endsection
