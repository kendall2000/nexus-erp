@extends('layouts.app', ['titulo' => 'Categorías de productos'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$usuario->puede('categorias.crear'), $usuario->puede('categorias.editar'), $usuario->puede('categorias.eliminar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Categorías de productos</h2>
            <p class="text-700 fw-semi-bold mb-0">{{ $total }} {{ $total === 1 ? 'categoría' : 'categorías' }} organizadas en árbol.</p>
        </div>
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('categorias.create') }}"><span class="fas fa-plus me-2"></span>Nueva categoría</a>
        @endif
    </div>

    <div class="card mb-9">
        <div class="card-body">
            <div class="search-box mb-3" style="max-width: 22rem">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input form-control-sm" id="buscar-categoria" type="search" placeholder="Buscar categoría" aria-label="Buscar" />
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle" id="tabla-categorias">
                    <thead><tr><th>Categoría</th><th class="text-end">Subcategorías</th><th class="text-end">Productos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($arbol as ['categoria' => $c, 'nivel' => $nivel])
                        <tr data-nombre="{{ mb_strtolower($c->nombre) }}">
                            <td>
                                <div style="padding-left: {{ $nivel * 1.5 }}rem">
                                    @if ($nivel > 0)<span class="text-400 me-1">└</span>@endif
                                    <span class="{{ $nivel === 0 ? 'fw-bold' : 'fw-semi-bold' }}">{{ $c->nombre }}</span>
                                    @if ($c->descripcion)<p class="text-600 fs--2 mb-0 line-clamp-1" style="padding-left: {{ $nivel > 0 ? '1rem' : '0' }}">{{ $c->descripcion }}</p>@endif
                                </div>
                            </td>
                            <td class="text-end">{{ $c->hijos_count }}</td>
                            <td class="text-end">{{ $c->productos_count }}</td>
                            <td>
                                @if ($c->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($puedeCrear || $puedeEditar || $puedeEliminar)
                                    <div class="font-sans-serif btn-reveal-trigger position-static">
                                        <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" aria-label="Acciones"><span class="fas fa-ellipsis-h fs--2"></span></button>
                                        <div class="dropdown-menu dropdown-menu-end py-2">
                                            @if ($puedeEditar)
                                                <a class="dropdown-item" href="{{ route('categorias.edit', $c->id_categoria) }}">Editar</a>
                                            @endif
                                            @if ($puedeCrear)
                                                <a class="dropdown-item" href="{{ route('categorias.create', ['padre' => $c->id_categoria]) }}">Agregar subcategoría</a>
                                            @endif
                                            @if ($puedeEditar)
                                                <form method="POST" action="{{ route('categorias.estado', $c->id_categoria) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="dropdown-item" type="submit">{{ $c->activo ? 'Desactivar' : 'Activar' }}</button>
                                                </form>
                                            @endif
                                            @if ($puedeEliminar && $c->hijos_count === 0 && $c->productos_count === 0)
                                                <div class="dropdown-divider"></div>
                                                <form method="POST" action="{{ route('categorias.destroy', $c->id_categoria) }}" onsubmit="return confirm(@js('¿Eliminar la categoría '.$c->nombre.'?'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit">Eliminar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-700 py-4">No hay categorías.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Búsqueda al instante (resalta coincidencias sin perder la jerarquía).
        document.getElementById('buscar-categoria').addEventListener('input', function () {
            var texto = this.value.trim().toLowerCase();
            document.querySelectorAll('#tabla-categorias tbody tr[data-nombre]').forEach(function (fila) {
                fila.classList.toggle('d-none', texto !== '' && fila.dataset.nombre.indexOf(texto) === -1);
            });
        });
    </script>
@endpush
