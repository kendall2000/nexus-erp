@extends('layouts.app', ['titulo' => 'Geografía'])

@section('contenido')
    @php $pestanas = ['pais' => 'Países', 'division' => 'Departamentos', 'municipio' => 'Municipios']; @endphp

    <div class="mb-4">
        <h2 class="mb-1 text-1100">Geografía</h2>
        <p class="text-700 fw-semi-bold mb-0">Países, departamentos y municipios que se usan en sucursales, clientes y proveedores. Es un catálogo compartido por todas las empresas.</p>
    </div>

    <div class="card mb-9">
        <div class="card-header p-0 border-bottom">
            <ul class="nav nav-underline fs--1 px-3" role="tablist">
                @foreach ($pestanas as $clave => $texto)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $clave === $pestana ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $clave }}" role="tab">{{ $texto }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- Países --}}
                <div class="tab-pane fade {{ $pestana === 'pais' ? 'show active' : '' }}" id="tab-pais" role="tabpanel">
                    <form method="POST" action="{{ route('geografia.store', 'pais') }}" class="row g-2 align-items-end mb-4">
                        @csrf
                        <div class="col-md-4"><label class="form-label fs--1" for="nuevo-pais">Nuevo país</label><input class="form-control form-control-sm" id="nuevo-pais" name="nombre" maxlength="100" required placeholder="Nombre" /></div>
                        <div class="col-4 col-md-2"><label class="form-label fs--1">ISO 2</label><input class="form-control form-control-sm text-uppercase" name="codigo_iso2" maxlength="2" placeholder="GT" /></div>
                        <div class="col-4 col-md-2"><label class="form-label fs--1">ISO 3</label><input class="form-control form-control-sm text-uppercase" name="codigo_iso3" maxlength="3" placeholder="GTM" /></div>
                        <div class="col-4 col-md-2"><label class="form-label fs--1">Prefijo</label><input class="form-control form-control-sm" name="prefijo_tel" maxlength="5" placeholder="+502" /></div>
                        <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit"><span class="fas fa-plus me-1"></span>Agregar</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>País</th><th>ISO</th><th>Prefijo</th><th>Moneda</th><th class="text-end">Departamentos</th><th>Estado</th><th class="text-end"></th></tr></thead>
                            <tbody>
                            @forelse ($paises as $p)
                                <tr>
                                    <td class="fw-semi-bold">{{ $p->nombre }}</td>
                                    <td>{{ collect([$p->codigo_iso2, $p->codigo_iso3])->filter()->join(' / ') ?: '—' }}</td>
                                    <td>{{ $p->prefijo_tel ?: '—' }}</td>
                                    <td>{{ $p->moneda_defecto ?: '—' }}</td>
                                    <td class="text-end"><a href="{{ route('geografia.index', ['pestana' => 'division', 'pais' => $p->id_pais]) }}">{{ $p->divisiones_count }}</a></td>
                                    <td>@include('geografia.estado', ['activo' => $p->activo])</td>
                                    <td class="text-end">@include('geografia.acciones', ['tipo' => 'pais', 'id' => $p->id_pais, 'registro' => $p])</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-700 py-4">No hay países.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Departamentos --}}
                <div class="tab-pane fade {{ $pestana === 'division' ? 'show active' : '' }}" id="tab-division" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                        <form method="GET" class="d-flex gap-2 align-items-end">
                            <input type="hidden" name="pestana" value="division" />
                            <div><label class="form-label fs--1" for="filtro-pais">Filtrar por país</label>
                                <select class="form-select form-select-sm" id="filtro-pais" name="pais" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    @foreach ($paises as $p)<option value="{{ $p->id_pais }}" @selected($idPais === $p->id_pais)>{{ $p->nombre }}</option>@endforeach
                                </select>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('geografia.store', 'division') }}" class="d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <input type="hidden" name="volver_pais" value="{{ $idPais }}" />
                            <div><label class="form-label fs--1">País</label>
                                <select class="form-select form-select-sm" name="id_pais" required>
                                    @foreach ($paises as $p)<option value="{{ $p->id_pais }}" @selected($idPais === $p->id_pais)>{{ $p->nombre }}</option>@endforeach
                                </select>
                            </div>
                            <div><label class="form-label fs--1" for="nuevo-dep">Nuevo departamento</label><input class="form-control form-control-sm" id="nuevo-dep" name="nombre" maxlength="100" required placeholder="Nombre" /></div>
                            <button class="btn btn-primary btn-sm" type="submit"><span class="fas fa-plus me-1"></span>Agregar</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Departamento</th><th>País</th><th class="text-end">Municipios</th><th>Estado</th><th class="text-end"></th></tr></thead>
                            <tbody>
                            @forelse ($divisiones as $d)
                                <tr>
                                    <td class="fw-semi-bold">{{ $d->nombre }}</td>
                                    <td>{{ $d->pais?->nombre ?? '—' }}</td>
                                    <td class="text-end"><a href="{{ route('geografia.index', ['pestana' => 'municipio', 'departamento' => $d->id_division]) }}">{{ $d->municipios_count }}</a></td>
                                    <td>@include('geografia.estado', ['activo' => $d->activo])</td>
                                    <td class="text-end">@include('geografia.acciones', ['tipo' => 'division', 'id' => $d->id_division, 'registro' => $d])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-700 py-4">No hay departamentos{{ $idPais ? ' en este país' : '' }}.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Municipios --}}
                <div class="tab-pane fade {{ $pestana === 'municipio' ? 'show active' : '' }}" id="tab-municipio" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                        <form method="GET" class="d-flex gap-2 align-items-end">
                            <input type="hidden" name="pestana" value="municipio" />
                            <div><label class="form-label fs--1" for="filtro-dep">Filtrar por departamento</label>
                                <select class="form-select form-select-sm" id="filtro-dep" name="departamento" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    @foreach ($todasDivisiones as $d)<option value="{{ $d->id_division }}" @selected($idDivision === $d->id_division)>{{ $d->nombre }} ({{ $d->pais?->nombre }})</option>@endforeach
                                </select>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('geografia.store', 'municipio') }}" class="d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <input type="hidden" name="volver_departamento" value="{{ $idDivision }}" />
                            <div><label class="form-label fs--1">Departamento</label>
                                <select class="form-select form-select-sm" name="id_division" required>
                                    @foreach ($todasDivisiones as $d)<option value="{{ $d->id_division }}" @selected($idDivision === $d->id_division)>{{ $d->nombre }} ({{ $d->pais?->nombre }})</option>@endforeach
                                </select>
                            </div>
                            <div><label class="form-label fs--1" for="nuevo-mun">Nuevo municipio</label><input class="form-control form-control-sm" id="nuevo-mun" name="nombre" maxlength="100" required placeholder="Nombre" /></div>
                            <button class="btn btn-primary btn-sm" type="submit"><span class="fas fa-plus me-1"></span>Agregar</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Municipio</th><th>Departamento</th><th>País</th><th>Estado</th><th class="text-end"></th></tr></thead>
                            <tbody>
                            @forelse ($municipios as $m)
                                <tr>
                                    <td class="fw-semi-bold">{{ $m->nombre }}</td>
                                    <td>{{ $m->division?->nombre ?? '—' }}</td>
                                    <td>{{ $m->division?->pais?->nombre ?? '—' }}</td>
                                    <td>@include('geografia.estado', ['activo' => $m->activo])</td>
                                    <td class="text-end">@include('geografia.acciones', ['tipo' => 'municipio', 'id' => $m->id_municipio, 'registro' => $m])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-700 py-4">No hay municipios{{ $idDivision ? ' en este departamento' : '' }}.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
