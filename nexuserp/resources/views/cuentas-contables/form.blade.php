@extends('layouts.app', ['titulo' => $cuenta->exists ? 'Editar cuenta' : 'Nueva cuenta contable'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('cuentas-contables.index') }}">Cuentas contables</a></li>
            <li class="breadcrumb-item active">{{ $cuenta->exists ? $cuenta->codigo : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $cuenta->exists ? 'Editar cuenta '.$cuenta->codigo : 'Nueva cuenta contable' }}</h2>

    <div class="card" style="max-width: 48rem">
        <div class="card-body">
            <form method="POST" action="{{ $cuenta->exists ? route('cuentas-contables.update', $cuenta->id_cuenta) : route('cuentas-contables.store') }}">
                @csrf
                @if ($cuenta->exists)
                    @method('PUT')
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="codigo">Código</label>
                        <input class="form-control @error('codigo') is-invalid @enderror" id="codigo" name="codigo" value="{{ old('codigo', $cuenta->codigo) }}" required maxlength="20" placeholder="Ej.: 5.01.001" />
                        @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $cuenta->nombre) }}" required maxlength="200" />
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="id_padre">Cuenta padre</label>
                        <select class="form-select @error('id_padre') is-invalid @enderror" id="id_padre" name="id_padre">
                            <option value="">Ninguna (cuenta raíz)</option>
                            @foreach ($padres as $p)
                                <option value="{{ $p->id_cuenta }}" data-tipo="{{ $p->tipo }}" data-naturaleza="{{ $p->naturaleza }}" @selected((int) old('id_padre', $cuenta->id_padre) === $p->id_cuenta)>{{ str_repeat('— ', $p->nivel - 1) }}{{ $p->codigo }} {{ $p->nombre }} ({{ strtolower($p->tipo) }})</option>
                            @endforeach
                        </select>
                        @error('id_padre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Solo aparecen cuentas de agrupación. La subcuenta toma el tipo de su padre.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
                            @foreach ($tipos as $t)<option value="{{ $t }}" @selected(old('tipo', $cuenta->tipo) === $t)>{{ ucfirst(strtolower($t)) }}</option>@endforeach
                        </select>
                        @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="naturaleza">Naturaleza</label>
                        <select class="form-select" id="naturaleza" name="naturaleza" required>
                            @foreach ($naturalezas as $n)<option value="{{ $n }}" @selected(old('naturaleza', $cuenta->naturaleza) === $n)>{{ ucfirst(strtolower($n)) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input @error('permite_movimiento') is-invalid @enderror" id="permite_movimiento" name="permite_movimiento" type="checkbox" value="1" @checked(old('permite_movimiento', $cuenta->permite_movimiento)) @disabled($tieneHijas) />
                    <label class="form-check-label" for="permite_movimiento">Permite movimientos</label>
                    @error('permite_movimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text mt-0">{{ $tieneHijas ? 'Tiene subcuentas: es una cuenta de agrupación.' : 'Desmárcalo si la cuenta solo agrupará subcuentas.' }}</div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $cuenta->activo)) />
                    <label class="form-check-label" for="activo">Activa</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('cuentas-contables.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const padre = document.getElementById('id_padre');
            const tipo = document.getElementById('tipo');
            const naturaleza = document.getElementById('naturaleza');
            const naturalezaDe = @json($naturalezaDe);
            // Al elegir padre se copian su tipo y naturaleza; al cambiar el tipo se sugiere la naturaleza habitual.
            padre.addEventListener('change', function () {
                const opcion = padre.selectedOptions[0];
                if (opcion && opcion.dataset.tipo) {
                    tipo.value = opcion.dataset.tipo;
                    naturaleza.value = opcion.dataset.naturaleza;
                }
            });
            tipo.addEventListener('change', function () {
                naturaleza.value = naturalezaDe[tipo.value] || naturaleza.value;
            });
        })();
    </script>
@endsection
