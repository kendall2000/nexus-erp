{{-- Barra de ejecución de presupuesto: verde, amarilla desde 90 % y roja si pasa del 100 %. --}}
<div class="d-flex align-items-center justify-content-end gap-2">
    <div class="progress flex-grow-1" style="height: 6px; max-width: 7rem;">
        <div class="progress-bar rounded-pill bg-{{ $pct > 100 ? 'danger' : ($pct >= 90 ? 'warning' : 'success') }}" style="width: {{ min(100, max(0, $pct)) }}%"></div>
    </div>
    <span class="fs--2 fw-semi-bold {{ $pct > 100 ? 'text-danger' : 'text-700' }}" style="min-width: 3.2rem;">{{ number_format($pct, 1) }} %</span>
</div>
