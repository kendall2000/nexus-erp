{{-- Desglose mensual de una sección del consolidado: una fila por empresa y moneda, y el total por moneda. --}}
@if ($tabla['filas'])
    @php $mesesNombre = \App\Http\Controllers\ConsolidadoController::MESES; @endphp
    <div class="border-top border-200">
        <p class="px-3 pt-3 mb-2 fs--1 fw-bold text-800">{{ $titulo }}</p>
        <div class="table-responsive scrollbar">
            <table class="table table-sm fs--2 mb-0 align-middle text-nowrap">
                <thead class="bg-100">
                    <tr>
                        <th class="ps-3">EMPRESA</th>
                        <th>MONEDA</th>
                        @foreach ($mesesNombre as $m)
                            <th class="text-end">{{ mb_strtoupper($m) }}</th>
                        @endforeach
                        <th class="text-end pe-3">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tabla['filas'] as $f)
                        <tr>
                            <td class="ps-3 fw-semi-bold text-1000">{{ $empresa($f['id_empresa']) }}</td>
                            <td>{{ $f['moneda'] }}</td>
                            @foreach ($f['meses'] as $v)
                                <td class="text-end {{ $v < 0 ? 'text-danger' : ($v == 0 ? 'text-500' : '') }}">{{ $dinero($v) }}</td>
                            @endforeach
                            <td class="text-end fw-bold pe-3">{{ $dinero(array_sum($f['meses'])) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-100 fw-bold">
                    @foreach ($tabla['totales'] as $tot)
                        <tr>
                            <td class="ps-3">Total</td>
                            <td>{{ $tot['moneda'] }}</td>
                            @foreach ($tot['meses'] as $v)
                                <td class="text-end">{{ $dinero($v) }}</td>
                            @endforeach
                            <td class="text-end pe-3">{{ $dinero(array_sum($tot['meses'])) }}</td>
                        </tr>
                    @endforeach
                </tfoot>
            </table>
        </div>
    </div>
@endif
