@extends('layouts.app', ['titulo' => 'Módulos'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h2 class="mb-1 text-1100">Módulos</h2>
            <p class="text-700 fw-semi-bold mb-0">
                Cada módulo es una opción del menú lateral y define sus acciones (permisos). Qué puede hacer cada puesto se marca en
                <a href="{{ route('roles.index') }}">Roles y permisos</a>. Un módulo sin acciones solo lo ve el Administrador.
            </p>
        </div>
        <a class="btn btn-primary" href="{{ route('modulos.create') }}"><span class="fas fa-plus me-2"></span>Nuevo módulo</a>
    </div>

    <div class="row g-3 mt-2 mb-9">
        @forelse ($grupos as $grupo => $modulos)
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center py-2">
                        <h5 class="mb-0 flex-1 text-1000">{{ $grupo }}</h5>
                        <a class="btn btn-link btn-sm px-0" href="{{ route('modulos.create', ['grupo' => $grupo]) }}"><span class="fas fa-plus me-1"></span>Agregar</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($modulos as $m)
                            @include('gestion-modulos.fila', ['m' => $m, 'primero' => $loop->first, 'ultimo' => $loop->last, 'nivel' => 0])
                            @foreach ($hijos->get($m->id_modulo, collect()) as $h)
                                @include('gestion-modulos.fila', ['m' => $h, 'primero' => $loop->first, 'ultimo' => $loop->last, 'nivel' => 1])
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-center text-700 py-6">No hay módulos.</div></div></div>
        @endforelse
    </div>
@endsection
