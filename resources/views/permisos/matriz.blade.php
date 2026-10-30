{{--
    Matriz de permisos módulos × acciones (como en sistema-inventario).
    Parámetros:
      $matriz      MatrizPermisos::datos()
      $marcados    ids de permisos marcados
      $propios     ids que quien edita puede dar (null = todos: Administrador)
      $heredados   ids que ya da el rol (se muestran marcados y fijos; opcional)
      $bloqueado   toda la matriz en solo lectura
--}}
@php
    $heredados = $heredados ?? [];
    $bloqueado = $bloqueado ?? false;
@endphp
<div class="border-top border-bottom border-300 mb-3">
    <div class="table-responsive scrollbar">
        <table class="table table-sm fs--1 mb-0 align-middle matriz-permisos">
            <thead>
            <tr>
                <th class="ps-0 fs--2" scope="col" style="min-width: 11rem">MÓDULO</th>
                @foreach ($matriz['acciones'] as $accion)
                    <th class="text-center fs--2 lh-sm px-1" scope="col" style="min-width: 3.6rem" title="{{ $accion->descripcion }}">{{ $accion->nombre }}</th>
                @endforeach
                @unless ($bloqueado)<th class="text-end pe-0" scope="col"></th>@endunless
            </tr>
            </thead>
            @foreach ($matriz['grupos'] as $grupo => $filas)
                <tbody>
                <tr class="bg-light"><td class="ps-2 fw-bold text-700 fs--2 text-uppercase" colspan="{{ $matriz['acciones']->count() + 2 }}">{{ $grupo }}</td></tr>
                @foreach ($filas as ['modulo' => $modulo, 'permisos' => $permisos])
                    <tr>
                        <td class="ps-0">
                            <span class="text-900 fw-semi-bold">
                                @if ($modulo->icono)<span data-feather="{{ $modulo->icono }}" class="me-1 text-600" style="width:14px;height:14px"></span>@endif
                                {{ $modulo->nombre }}
                            </span>
                        </td>
                        @foreach ($matriz['acciones'] as $accion)
                            <td class="text-center">
                                @isset($permisos[$accion->id_accion])
                                    @php
                                        $id = $permisos[$accion->id_accion];
                                        $heredado = in_array($id, $heredados, true);
                                        $ajeno = $propios !== null && ! in_array($id, $propios, true);
                                    @endphp
                                    <input class="form-check-input permiso" type="checkbox" name="permisos[]" value="{{ $id }}"
                                           title="{{ $modulo->codigo }}.{{ $accion->codigo }}{{ $heredado ? ' · lo da su rol' : '' }}{{ $ajeno && ! $heredado ? ' · tú no tienes este permiso' : '' }}"
                                           @checked($heredado || in_array($id, $marcados, true)) @disabled($bloqueado || $heredado || $ajeno) />
                                @else
                                    <span class="text-300">—</span>
                                @endisset
                            </td>
                        @endforeach
                        @unless ($bloqueado)
                            <td class="text-end pe-0">
                                <button class="btn btn-link btn-sm p-0 fs--2 marcar-fila" type="button">Todo</button>
                            </td>
                        @endunless
                    </tr>
                @endforeach
                </tbody>
            @endforeach
        </table>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // «Todo» marca o desmarca los permisos editables de la fila.
            document.addEventListener('click', function (e) {
                if (!e.target.classList.contains('marcar-fila')) return;
                var casillas = Array.prototype.filter.call(e.target.closest('tr').querySelectorAll('input.permiso'), function (c) { return !c.disabled; });
                var marcar = casillas.some(function (c) { return !c.checked; });
                casillas.forEach(function (c) { c.checked = marcar; });
            });
        </script>
    @endpush
@endonce
