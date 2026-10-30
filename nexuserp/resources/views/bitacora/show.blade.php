@extends('layouts.app', ['titulo' => 'Cambio #'.$cambio->id_cambio])

@section('contenido')
    @php
        [$etiqueta, $color] = \App\Support\Bitacora::ACCIONES[$cambio->accion] ?? [$cambio->accion, 'secondary'];
        $tabla = \App\Support\Bitacora::tabla($cambio->tabla_afectada);
        $valor = fn (mixed $v) => $v === null || $v === '' ? null : (is_array($v) ? implode(', ', $v) : (string) $v);
        $esLista = collect($campos)->contains(fn ($f) => is_array($f['antes']) || is_array($f['despues']));
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('bitacora.index') }}">Bitácora de cambios</a></li>
            <li class="breadcrumb-item active">Cambio #{{ $cambio->id_cambio }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100"><span class="badge badge-phoenix badge-phoenix-{{ $color }} fs-0 align-middle me-2">{{ $etiqueta }}</span>{{ $tabla }} #{{ $cambio->id_registro }}</h2>
            <p class="text-700 mb-0">
                {{ $cambio->usuario?->nombre_completo ?? 'Sistema' }}{{ $cambio->usuario ? ' ('.$cambio->usuario->username.')' : '' }}
                · {{ $cambio->created_at?->format('d/m/Y H:i:s') }}{{ $cambio->ip_address ? ' · IP '.$cambio->ip_address : '' }}
            </p>
        </div>
        @if ($historial > 1)
            <a class="btn btn-phoenix-secondary" href="{{ route('bitacora.index', ['tabla' => $cambio->tabla_afectada, 'registro' => $cambio->id_registro]) }}">
                <span class="fas fa-history me-2"></span>Historial del registro ({{ $historial }})
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead>
                    <tr>
                        <th style="width: 22%">Campo</th>
                        @if ($esLista)
                            <th>Quitados</th><th>Agregados</th>
                        @else
                            @if ($cambio->accion !== 'INSERT')<th>{{ $cambio->accion === 'DELETE' ? 'Valor eliminado' : 'Antes' }}</th>@endif
                            @if ($cambio->accion !== 'DELETE')<th>{{ $cambio->accion === 'INSERT' ? 'Valor' : 'Después' }}</th>@endif
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($campos as $f)
                        <tr>
                            <td class="fw-semi-bold font-monospace fs--2">{{ $f['campo'] }}</td>
                            @if ($esLista || $cambio->accion !== 'INSERT')
                                <td class="text-break {{ $cambio->accion === 'UPDATE' ? 'text-danger' : '' }}">{{ $valor($f['antes']) ?? '—' }}</td>
                            @endif
                            @if ($esLista || $cambio->accion !== 'DELETE')
                                <td class="text-break {{ $cambio->accion === 'UPDATE' ? 'text-success' : '' }}">{{ $valor($f['despues']) ?? '—' }}</td>
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
