<form method="POST" action="{{ route('contratos.estado', [$c->id_contrato, $accion]) }}" @if ($confirmar) onsubmit="return confirm(@js($confirmar))" @endif>
    @csrf
    @method('PATCH')
    <button class="btn {{ $clase }}" type="submit">{{ $texto }}</button>
</form>
