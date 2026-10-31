@extends('layouts.app', ['titulo' => 'Cuentas contables'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$usuario->puede('cuentas_contables.crear'), $usuario->puede('cuentas_contables.editar'), $usuario->puede('cuentas_contables.eliminar')];
        $colorTipo = ['ACTIVO' => 'primary', 'PASIVO' => 'warning', 'PATRIMONIO' => 'info', 'INGRESO' => 'success', 'GASTO' => 'danger', 'COSTO' => 'secondary'];
    @endphp

    <x-page-header
        titulo="Cuentas contables"
        icono="fas fa-book"
        subtitulo="Plan de cuentas. Las cuentas de agrupación reúnen subcuentas; solo las de movimiento se usan en compras y presupuesto."
        :migas="[
            ['label' => 'Finanzas'],
            ['label' => 'Cuentas contables'],
        ]"
    >
        <x-slot:acciones>
            @if ($usuario->puede('cuentas_contables.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('cuentas-contables.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($puedeCrear && $puedeEditar)
                <a class="btn btn-phoenix-secondary" href="{{ route('cuentas-contables.importar') }}"><span class="fas fa-file-upload me-2"></span>Importar</a>
            @endif
            @if ($puedeCrear)
                <a class="btn btn-primary" href="{{ route('cuentas-contables.create') }}"><span class="fas fa-plus me-2"></span>Nueva cuenta</a>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <x-phoenix-table>
        <x-slot:filtros>
            <form method="GET" class="row g-2 mb-0">
                <div class="col-md-5"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código (empieza con…) o nombre" maxlength="100" /></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="tipo">
                        <option value="">Todos los tipos</option>
                        @foreach ($tipos as $t)<option value="{{ $t }}" @selected(($filtros['tipo'] ?? '') === $t)>{{ ucfirst(strtolower($t)) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Activas e inactivas</option>
                        <option value="activas" @selected(($filtros['estado'] ?? '') === 'activas')>Activas</option>
                        <option value="inactivas" @selected(($filtros['estado'] ?? '') === 'inactivas')>Inactivas</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if ($filtrando)<a class="btn btn-link btn-sm px-1" href="{{ route('cuentas-contables.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
        </x-slot:filtros>
        <x-slot:head>
            <tr>
                <th>Cuenta</th>
                <th>Tipo</th>
                <th>Naturaleza</th>
                <th>Uso</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </x-slot:head>
        @forelse ($cuentas as $c)
            <tr class="{{ $c->activo ? '' : 'text-500' }}">
                <td style="padding-left: {{ $filtrando ? 0.5 : 0.5 + ($c->nivel - 1) * 1.5 }}rem">
                    @if (! $c->permite_movimiento)<span class="fas fa-folder text-warning me-1"></span>@endif
                    <code>{{ $c->codigo }}</code>
                    <span class="{{ $c->permite_movimiento ? '' : 'fw-bold' }}">{{ $c->nombre }}</span>
                </td>
                <td><span class="badge badge-phoenix badge-phoenix-{{ $colorTipo[$c->tipo] ?? 'secondary' }}">{{ $c->tipo }}</span></td>
                <td class="text-700">{{ ucfirst(strtolower($c->naturaleza)) }}</td>
                <td class="text-700">{{ $c->permite_movimiento ? 'Movimiento' : 'Agrupación ('.$c->hijas_count.')' }}</td>
                <td>
                    @if ($c->activo)
                        <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                    @else
                        <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                    @endif
                </td>
                <td class="text-end text-nowrap">
                    @if ($puedeCrear && ! $c->permite_movimiento)
                        <a class="btn btn-link btn-sm px-1" href="{{ route('cuentas-contables.create', ['padre' => $c->id_cuenta]) }}" title="Agregar subcuenta"><span class="fas fa-plus"></span></a>
                    @endif
                    @if ($puedeEditar)
                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('cuentas-contables.edit', $c->id_cuenta) }}">Editar</a>
                        <form method="POST" action="{{ route('cuentas-contables.estado', $c->id_cuenta) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-phoenix-{{ $c->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $c->activo ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                    @endif
                    @if ($puedeEliminar && ! $c->hijas_count)
                        <form method="POST" action="{{ route('cuentas-contables.destroy', $c->id_cuenta) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar la cuenta '.$c->etiqueta.'?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-700 py-4">{{ $filtrando ? 'Ninguna cuenta coincide con los filtros.' : 'No hay cuentas contables. Créalas una a una o impórtalas desde Excel.' }}</td></tr>
        @endforelse
    </x-phoenix-table>
@endsection
