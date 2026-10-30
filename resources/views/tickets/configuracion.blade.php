@extends('layouts.app', ['titulo' => 'Categorías y SLA'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Tickets</a></li>
            <li class="breadcrumb-item active">Categorías y SLA</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Categorías y SLA</h2>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Categorías</h5>
                    @if ($errors->categoria->any())<div class="alert alert-soft-danger">{{ $errors->categoria->first() }}</div>@endif
                    @foreach ($categorias->push(new \App\Models\CRM\CategoriaTicket(['activo' => true, 'prioridad_default' => 'MEDIA'])) as $c)
                        <form method="POST" action="{{ $c->exists ? route('tickets.categorias.update', $c->id_categoria) : route('tickets.categorias.store') }}" class="row g-2 border-bottom border-200 py-2 align-items-center">
                            @csrf
                            @if ($c->exists)
                                @method('PUT')
                            @endif
                            <div class="col-md-5"><input class="form-control form-control-sm" name="nombre" value="{{ $c->nombre }}" placeholder="{{ $c->exists ? '' : 'Nueva categoría' }}" required maxlength="100" /></div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" name="prioridad_default" title="Prioridad sugerida">@foreach ($prioridades as $k => [$n])<option value="{{ $k }}" @selected($c->prioridad_default === $k)>{{ $n }}</option>@endforeach</select>
                            </div>
                            <div class="col-md-2">
                                <input type="hidden" name="activo" value="0" />
                                <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="activo" value="1" id="cat-{{ $c->id_categoria ?? 'nueva' }}" @checked($c->activo) /><label class="form-check-label" for="cat-{{ $c->id_categoria ?? 'nueva' }}">Activa</label></div>
                            </div>
                            <div class="col-md-2"><button class="btn btn-{{ $c->exists ? 'phoenix-secondary' : 'primary' }} btn-sm w-100" type="submit">{{ $c->exists ? 'Guardar' : 'Agregar' }}</button></div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-1">Planes de SLA</h5>
                    <p class="text-700">Horas para la primera respuesta y para resolver, por prioridad. Sin «fines de semana», sábado y domingo no cuentan. Los cambios aplican a los tickets nuevos.</p>
                    @if ($errors->sla->any())<div class="alert alert-soft-danger">{{ $errors->sla->first() }}</div>@endif
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead><tr><th>Plan</th><th>Prioridad</th><th>Respuesta (h)</th><th>Resolución (h)</th><th>Fines de semana</th><th>Activo</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($slas->flatten()->push(new \App\Models\CRM\SlaConfig(['activo' => true, 'prioridad' => 'MEDIA'])) as $s)
                                <tr>
                                    <form method="POST" action="{{ $s->exists ? route('tickets.slas.update', $s->id_sla) : route('tickets.slas.store') }}" id="sla-{{ $s->id_sla ?? 'nuevo' }}">
                                        @csrf
                                        @if ($s->exists)
                                            @method('PUT')
                                        @endif
                                    </form>
                                    <td><input class="form-control form-control-sm" form="sla-{{ $s->id_sla ?? 'nuevo' }}" name="nombre" value="{{ $s->nombre }}" placeholder="{{ $s->exists ? '' : 'Nuevo plan o fila' }}" required maxlength="100" /></td>
                                    <td><select class="form-select form-select-sm" form="sla-{{ $s->id_sla ?? 'nuevo' }}" name="prioridad">@foreach ($prioridades as $k => [$n])<option value="{{ $k }}" @selected($s->prioridad === $k)>{{ $n }}</option>@endforeach</select></td>
                                    <td><input class="form-control form-control-sm" form="sla-{{ $s->id_sla ?? 'nuevo' }}" name="tiempo_primera_respuesta_hrs" type="number" min="1" max="255" value="{{ $s->tiempo_primera_respuesta_hrs }}" required style="width: 5rem" /></td>
                                    <td><input class="form-control form-control-sm" form="sla-{{ $s->id_sla ?? 'nuevo' }}" name="tiempo_resolucion_hrs" type="number" min="1" value="{{ $s->tiempo_resolucion_hrs }}" required style="width: 6rem" /></td>
                                    <td class="text-center"><input class="form-check-input" form="sla-{{ $s->id_sla ?? 'nuevo' }}" type="checkbox" name="aplica_fines_semana" value="1" @checked($s->aplica_fines_semana) /></td>
                                    <td class="text-center">
                                        <input type="hidden" form="sla-{{ $s->id_sla ?? 'nuevo' }}" name="activo" value="0" />
                                        <input class="form-check-input" form="sla-{{ $s->id_sla ?? 'nuevo' }}" type="checkbox" name="activo" value="1" @checked($s->activo) />
                                    </td>
                                    <td><button class="btn btn-{{ $s->exists ? 'phoenix-secondary' : 'primary' }} btn-sm" form="sla-{{ $s->id_sla ?? 'nuevo' }}" type="submit">{{ $s->exists ? 'Guardar' : 'Agregar' }}</button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
