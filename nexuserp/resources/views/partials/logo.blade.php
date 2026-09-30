{{-- Logo del sistema (Configuración del sistema): imagen clara/oscura o, sin imagen, el ícono y el nombre. --}}
@php $cfg = \App\Support\Sistema::config(); $alto = $alto ?? 32; @endphp
@if ($cfg->imgLogo)
    <img class="{{ $cfg->imgLogoOscuro ? 'd-dark-none' : '' }}" src="{{ $cfg->imgLogo }}" alt="{{ \App\Support\Sistema::nombre() }}" style="max-height: {{ $alto }}px; max-width: 220px;" />
    @if ($cfg->imgLogoOscuro)
        <img class="d-light-none" src="{{ $cfg->imgLogoOscuro }}" alt="{{ \App\Support\Sistema::nombre() }}" style="max-height: {{ $alto }}px; max-width: 220px;" />
    @endif
@else
    <span class="fas fa-layer-group text-primary"></span>
    <p class="logo-text ms-2 {{ $claseTexto ?? 'd-none d-sm-block' }}">{{ \App\Support\Sistema::nombre() }}</p>
@endif
