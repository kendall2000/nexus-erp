@extends('layouts.app', ['titulo' => 'Campañas'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Campañas"
        subtitulo="Acciones de marketing: presupuesto, alcance y prospectos generados."
        icono="fas fa-bullhorn"
        :breadcrumbs="['CRM' => null, 'Campañas' => null]"
    >
        @if ($yo->puede('campanas.crear'))
            <a class="btn btn-primary" href="{{ route('campanas.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva campaña
            </a>
        @endif
    </x-page-header>

    <x-phoenix-table :paginador="$campanas">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Campaña</th>
                    <th class="text-uppercase text-700">Tipo · objetivo</th>
                    <th class="text-uppercase text-700">Fechas</th>
                    <th class="text-end text-uppercase text-700">Gasto / presupuesto</th>
                    <th class="text-end text-uppercase text-700">Leads / meta</th>
                    <th class="text-end text-uppercase text-700">Contactos</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($campanas as $c)
                @php [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary']; @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('campanas.show', $c->id_campana) }}">{{ $c->nombre }}</a>
                        <span class="d-block fs--2 text-600">{{ $c->lineaNegocio?->nombre ?? 'General' }}</span>
                    </td>
                    <td>
                        <span class="fw-semi-bold text-900">{{ $tipos[$c->tipo] ?? $c->tipo }}</span>
                        <span class="d-block fs--2 text-600">{{ $objetivos[$c->objetivo] ?? $c->objetivo }}</span>
                    </td>
                    <td class="text-nowrap text-800">{{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? '…' }}</td>
                    <td class="text-end text-nowrap fw-semi-bold text-900">
                        {{ number_format((float) $c->gasto_real, 2) }} / {{ $c->presupuesto !== null ? number_format((float) $c->presupuesto, 2) : '—' }}
                    </td>
                    <td class="text-end fw-semi-bold text-900">{{ $c->leads_generados }} / {{ $c->meta_leads ?? '—' }}</td>
                    <td class="text-end text-800">{{ $c->contactos_count }}</td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-700 py-5">
                        <span class="fas fa-bullhorn fs-2 text-300 d-block mb-2"></span>
                        Todavía no hay campañas.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
