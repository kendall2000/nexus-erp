@extends('layouts.app', ['titulo' => 'Cambio #'.$cambio->id_cambio])

@section('contenido')
    @php
        [$etiqueta, $color] = \App\Support\Bitacora::ACCIONES[$cambio->accion] ?? [$cambio->accion, 'secondary'];
        $tabla = \App\Support\Bitacora::tabla($cambio->tabla_afectada);
        $valor = fn (mixed $v) => $v === null || $v === '' ? null : (is_array($v) ? implode(', ', $v) : (string) $v);
        $esLista = collect($campos)->contains(fn ($f) => is_array($f['antes']) || is_array($f['despues']));
    @endphp

    <x-page-header
        :titulo="$tabla.' #'.$cambio->id_registro"
        icono="fas fa-history"
        :migas="[
            ['label' => 'Bitácora de cambios', 'url' => route('bitacora.index')],
            ['label' => 'Cambio #'.$cambio->id_cambio],
        ]"
    >
        <x-slot:insignia>
            <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $etiqueta }}</span>
        </x-slot:insignia>
        <x-slot:subtituloSlot>
            <p class="text-700 mb-0">
                {{ $cambio->usuario?->nombre_completo ?? 'Sistema' }}{{ $cambio->usuario ? ' ('.$cambio->usuario->username.')' : '' }}
                · {{ $cambio->created_at?->format('d/m/Y H:i:s') }}{{ $cambio->ip_address ? ' · IP '.$cambio->ip_address : '' }}
            </p>
        </x-slot:subtituloSlot>
        <x-slot:acciones>
            @if ($historial > 1)
                <a class="btn btn-phoenix-secondary" href="{{ route('bitacora.index', ['tabla' => $cambio->tabla_afectada, 'registro' => $cambio->id_registro]) }}">
                    <span class="fas fa-history me-2"></span>Historial del registro ({{ $historial }})
                </a>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <div class="card border border-200 shadow-none">
        <div class="card-header bg-100 border-bottom border-200 py-3 px-4">
            <h5 class="mb-0 text-1100"><span class="fas fa-code-branch text-primary me-2"></span>Comparativa de campos modificados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm Phoenix-table fs--1 mb-0 align-middle">
                    <thead class="bg-100">
                    <tr>
                        <th class="ps-4" style="width: 22%">Campo</th>
                        @if ($esLista)
                            <th>Quitados</th><th class="pe-4">Agregados</th>
                        @else
                            @if ($cambio->accion !== 'INSERT')<th>{{ $cambio->accion === 'DELETE' ? 'Valor eliminado' : 'Antes' }}</th>@endif
                            @if ($cambio->accion !== 'DELETE')<th class="pe-4">{{ $cambio->accion === 'INSERT' ? 'Valor' : 'Después' }}</th>@endif
                        @endif
                    </tr>
                    </thead>
                    <tbody class="list">
                    @forelse ($campos as $f)
                        <tr>
                            <td class="ps-4 fw-semi-bold font-monospace fs--2">{{ $f['campo'] }}</td>
                            @if ($esLista || $cambio->accion !== 'INSERT')
                                <td class="text-break {{ $cambio->accion === 'UPDATE' ? 'text-danger' : '' }}">{{ $valor($f['antes']) ?? '—' }}</td>
                            @endif
                            @if ($esLista || $cambio->accion !== 'DELETE')
                                <td class="text-break pe-4 {{ $cambio->accion === 'UPDATE' ? 'text-success' : '' }}">{{ $valor($f['despues']) ?? '—' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-700 py-4">Sin detalle de campos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
