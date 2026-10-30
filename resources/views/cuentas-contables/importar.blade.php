@extends('layouts.app', ['titulo' => 'Importar cuentas contables'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('cuentas-contables.index') }}">Cuentas contables</a></li>
            <li class="breadcrumb-item active">Importar</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Importar plan de cuentas</h2>

    @if (! $analisis)
        <div class="card" style="max-width: 48rem">
            <div class="card-body">
                <p class="fs--1 text-700">
                    Sube un archivo Excel (.xlsx) o CSV con las columnas <strong>Código, Nombre, Tipo, Naturaleza, Código padre y Permite movimiento</strong>.
                    Las cuentas cuyo código ya existe se actualizan; las demás se crean. Nada se guarda hasta que confirmes la vista previa.
                </p>
                <ul class="fs--1 text-700">
                    <li>Tipo: ACTIVO, PASIVO, PATRIMONIO, INGRESO, GASTO o COSTO. Si falta la naturaleza, se usa la habitual del tipo.</li>
                    <li>Si «Permite movimiento» está vacío: «Sí», salvo que la cuenta tenga subcuentas.</li>
                    <li>En Excel, da formato de <strong>texto</strong> a la columna Código para que «5.10» no se convierta en «5.1».</li>
                </ul>
                <form method="POST" action="{{ route('cuentas-contables.importar.previa') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-start">
                    @csrf
                    <div class="flex-grow-1">
                        <input class="form-control @error('archivo') is-invalid @enderror" type="file" name="archivo" accept=".xlsx,.csv" required />
                        @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary" type="submit"><span class="fas fa-search me-2"></span>Revisar archivo</button>
                </form>
                <a class="d-inline-block mt-3 fs--1" href="{{ route('cuentas-contables.plantilla') }}"><span class="fas fa-download me-1"></span>Descargar plantilla</a>
            </div>
        </div>
    @else
        @error('archivo')<div class="alert alert-soft-danger fs--1">{{ $message }}</div>@enderror
        <div class="row g-3 mb-4">
            <div class="col-sm-3"><div class="card"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Filas</p><h3 class="mb-0">{{ $analisis['total'] }}</h3></div></div></div>
            <div class="col-sm-3"><div class="card"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Nuevas</p><h3 class="mb-0 text-success">{{ $analisis['nuevas'] }}</h3></div></div></div>
            <div class="col-sm-3"><div class="card"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">A actualizar</p><h3 class="mb-0 text-info">{{ $analisis['actualizar'] }}</h3></div></div></div>
            <div class="col-sm-3"><div class="card"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Con errores</p><h3 class="mb-0 {{ $analisis['errores'] ? 'text-danger' : '' }}">{{ count($analisis['errores']) }}</h3></div></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                @if ($analisis['errores'])
                    <p class="text-danger fw-semi-bold"><span class="fas fa-exclamation-triangle me-2"></span>Corrige estas filas en el archivo y vuelve a subirlo; no se importará nada mientras haya errores.</p>
                @endif
                <div class="table-responsive" style="max-height: 28rem">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead><tr><th>Línea</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Naturaleza</th><th>Padre</th><th>Movimiento</th><th>Resultado</th></tr></thead>
                        <tbody>
                        @foreach ($analisis['filas'] as $f)
                            @php $error = $analisis['errores'][$f['linea']] ?? null; @endphp
                            <tr class="{{ $error ? 'table-danger' : '' }}">
                                <td>{{ $f['linea'] }}</td>
                                <td><code>{{ $f['codigo'] }}</code></td>
                                <td>{{ $f['nombre'] }}</td>
                                <td>{{ $f['tipo'] }}</td>
                                <td>{{ $f['naturaleza'] }}</td>
                                <td>{{ $f['padre'] ?? '—' }}</td>
                                <td>{{ $f['permite_movimiento'] ? 'Sí' : 'No' }}</td>
                                <td>{!! $error ? '<span class="text-danger">'.implode('<br>', array_map('e', $error['errores'])).'</span>' : '<span class="text-success">OK</span>' !!}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            @unless ($analisis['errores'])
                <form method="POST" action="{{ route('cuentas-contables.importar.confirmar') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit"><span class="fas fa-check me-2"></span>Importar {{ $analisis['total'] }} cuentas</button>
                </form>
            @endunless
            <form method="POST" action="{{ route('cuentas-contables.importar.cancelar') }}">
                @csrf
                <button class="btn btn-phoenix-secondary" type="submit">{{ $analisis['errores'] ? 'Subir otro archivo' : 'Cancelar' }}</button>
            </form>
        </div>
    @endif
@endsection
