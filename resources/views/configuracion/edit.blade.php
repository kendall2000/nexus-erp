@extends('layouts.app', ['titulo' => 'Configuración del sistema'])

@section('contenido')
    @php
        $pestanas = ['sistema' => 'Sistema y parámetros', 'apariencia' => 'Apariencia y login', 'imagenes' => 'Imágenes', 'correos' => 'Correos'];
        $activa = array_key_exists(request('pestana'), $pestanas) ? request('pestana') : 'sistema';
        $v = fn (string $campo) => old($campo, $config->{$campo});
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Configuración del sistema</h2>
            <p class="text-700 fw-semi-bold mb-0">Nombre, apariencia, textos del login e imágenes. Las reglas de inicio de sesión están en <a href="{{ route('seguridad.index') }}">Seguridad y accesos</a>.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data" class="mb-9">
        @csrf
        @method('PUT')
        <input type="hidden" name="pestana" id="pestana" value="{{ $activa }}" />

        <div class="card">
            <div class="card-header p-0 border-bottom">
                <ul class="nav nav-underline fs--1 px-3" role="tablist">
                    @foreach ($pestanas as $clave => $texto)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $clave === $activa ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $clave }}" data-pestana="{{ $clave }}" role="tab">{{ $texto }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    {{-- Sistema y parámetros --}}
                    <div class="tab-pane fade {{ $activa === 'sistema' ? 'show active' : '' }}" id="tab-sistema" role="tabpanel">
                        <h5 class="mb-3 text-800">Información general</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label" for="nombreSistema">Nombre del sistema</label><input class="form-control" id="nombreSistema" name="nombreSistema" value="{{ $v('nombreSistema') }}" maxlength="100" required /></div>
                            <div class="col-md-6"><label class="form-label" for="nombreEmpresa">Nombre de la empresa</label><input class="form-control" id="nombreEmpresa" name="nombreEmpresa" value="{{ $v('nombreEmpresa') }}" maxlength="100" /></div>
                            <div class="col-12"><label class="form-label" for="slogan">Eslogan</label><input class="form-control" id="slogan" name="slogan" value="{{ $v('slogan') }}" maxlength="255" /><div class="form-text">Aparece sobre la imagen del login.</div></div>
                            <div class="col-md-4"><label class="form-label" for="nit">NIT</label><input class="form-control" id="nit" name="nit" value="{{ $v('nit') }}" maxlength="20" /></div>
                            <div class="col-md-4"><label class="form-label" for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="20" /></div>
                            <div class="col-md-4"><label class="form-label" for="correoContacto">Correo de contacto</label><input class="form-control" id="correoContacto" name="correoContacto" type="email" value="{{ $v('correoContacto') }}" maxlength="100" /></div>
                            <div class="col-md-8"><label class="form-label" for="direccion">Dirección</label><input class="form-control" id="direccion" name="direccion" value="{{ $v('direccion') }}" maxlength="255" /></div>
                            <div class="col-md-4"><label class="form-label" for="sitioWeb">Sitio web</label><input class="form-control" id="sitioWeb" name="sitioWeb" type="url" value="{{ $v('sitioWeb') }}" maxlength="100" placeholder="https://" /></div>
                        </div>

                        <h5 class="mb-3 text-800 border-top border-200 pt-4">Parámetros regionales</h5>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label" for="moneda">Moneda (símbolo)</label><input class="form-control" id="moneda" name="moneda" value="{{ $v('moneda') }}" maxlength="10" required /></div>
                            <div class="col-md-3"><label class="form-label" for="monedaCodigo">Moneda (código)</label><input class="form-control text-uppercase" id="monedaCodigo" name="monedaCodigo" value="{{ $v('monedaCodigo') }}" maxlength="3" required /></div>
                            <div class="col-md-3">
                                <label class="form-label" for="zonaHoraria">Zona horaria</label>
                                <select class="form-select" id="zonaHoraria" name="zonaHoraria" required>
                                    @foreach ($zonas as $zona)<option value="{{ $zona }}" @selected($v('zonaHoraria') === $zona)>{{ $zona }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="formatoFecha">Formato de fecha</label>
                                <select class="form-select" id="formatoFecha" name="formatoFecha" required>
                                    @foreach ($formatosFecha as $formato => $ejemplo)<option value="{{ $formato }}" @selected($v('formatoFecha') === $formato)>{{ $ejemplo }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label" for="diasMora">Días de mora</label><input class="form-control" id="diasMora" name="diasMora" type="number" min="0" max="365" value="{{ $v('diasMora') }}" /></div>
                            <div class="col-md-3"><label class="form-label" for="porcentajeMora">% de mora</label><input class="form-control" id="porcentajeMora" name="porcentajeMora" type="number" min="0" max="100" step="0.01" value="{{ $v('porcentajeMora') }}" /></div>
                        </div>
                    </div>

                    {{-- Apariencia y login --}}
                    <div class="tab-pane fade {{ $activa === 'apariencia' ? 'show active' : '' }}" id="tab-apariencia" role="tabpanel">
                        <h5 class="mb-3 text-800">Colores</h5>
                        <div class="row g-3 mb-4">
                            @foreach (['colorPrimario' => 'Primario (botones y enlaces)', 'colorSecundario' => 'Secundario', 'colorAccent' => 'Acento'] as $campo => $texto)
                                <div class="col-md-4">
                                    <label class="form-label" for="{{ $campo }}">{{ $texto }}</label>
                                    <input class="form-control form-control-color w-100" id="{{ $campo }}" name="{{ $campo }}" type="color" value="{{ $v($campo) ?: '#3874ff' }}" style="min-height:40px" />
                                </div>
                            @endforeach
                        </div>

                        <h5 class="mb-3 text-800 border-top border-200 pt-4">Textos del inicio de sesión</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label" for="loginTitulo">Título</label><input class="form-control" id="loginTitulo" name="loginTitulo" value="{{ $v('loginTitulo') }}" maxlength="100" placeholder="Iniciar sesión" /></div>
                            <div class="col-md-6"><label class="form-label" for="loginMensajeBienve">Mensaje bajo el título</label><input class="form-control" id="loginMensajeBienve" name="loginMensajeBienve" value="{{ $v('loginMensajeBienve') }}" maxlength="255" placeholder="Ingresa tus credenciales para continuar" /></div>
                            <div class="col-md-4"><label class="form-label" for="loginLabelUsuario">Etiqueta del usuario</label><input class="form-control" id="loginLabelUsuario" name="loginLabelUsuario" value="{{ $v('loginLabelUsuario') }}" maxlength="50" placeholder="Usuario o correo electrónico" /></div>
                            <div class="col-md-4"><label class="form-label" for="loginPlaceholderUs">Ejemplo en el campo usuario</label><input class="form-control" id="loginPlaceholderUs" name="loginPlaceholderUs" value="{{ $v('loginPlaceholderUs') }}" maxlength="100" /></div>
                            <div class="col-md-4"><label class="form-label" for="loginLabelPassword">Etiqueta de la contraseña</label><input class="form-control" id="loginLabelPassword" name="loginLabelPassword" value="{{ $v('loginLabelPassword') }}" maxlength="50" placeholder="Contraseña" /></div>
                            <div class="col-md-4"><label class="form-label" for="loginLabelRecordar">Texto de «Recordar sesión»</label><input class="form-control" id="loginLabelRecordar" name="loginLabelRecordar" value="{{ $v('loginLabelRecordar') }}" maxlength="50" /></div>
                            <div class="col-md-4"><label class="form-label" for="loginLinkOlvide">Texto de «¿Olvidaste tu contraseña?»</label><input class="form-control" id="loginLinkOlvide" name="loginLinkOlvide" value="{{ $v('loginLinkOlvide') }}" maxlength="50" /></div>
                            <div class="col-md-4"><label class="form-label" for="loginTextBoton">Texto del botón</label><input class="form-control" id="loginTextBoton" name="loginTextBoton" value="{{ $v('loginTextBoton') }}" maxlength="50" placeholder="Iniciar sesión" /></div>
                            <input type="hidden" name="loginSubtitulo" value="{{ $v('loginSubtitulo') }}" />
                        </div>

                        <h5 class="mb-3 text-800 border-top border-200 pt-4">Pie de página</h5>
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label" for="footerTexto">Texto</label><input class="form-control" id="footerTexto" name="footerTexto" value="{{ $v('footerTexto') }}" maxlength="255" placeholder="Todos los derechos reservados" /><div class="form-text">Se muestra después del nombre del sistema; el año se pone solo.</div></div>
                            <div class="col-md-4"><label class="form-label" for="footerVersion">Versión</label><input class="form-control" id="footerVersion" name="footerVersion" value="{{ $v('footerVersion') }}" maxlength="20" placeholder="v1.0.0" /></div>
                        </div>
                    </div>

                    {{-- Imágenes --}}
                    <div class="tab-pane fade {{ $activa === 'imagenes' ? 'show active' : '' }}" id="tab-imagenes" role="tabpanel">
                        @unless ($contaboListo)
                            <div class="alert alert-soft-warning fs--1" role="alert">La subida de imágenes no está configurada: faltan las credenciales de Contabo en el <code>.env</code>. Puedes guardar lo demás.</div>
                        @endunless
                        <p class="text-700 fs--1">JPG, PNG, WEBP o ICO de hasta 4 MB. Se guardan en Contabo.</p>
                        <div class="row g-3">
                            @foreach ($imagenes as $campo => [$titulo, $ayuda])
                                <div class="col-sm-6 col-xl-4">
                                    <div class="card h-100 border border-200 shadow-none">
                                        <div class="card-body">
                                            <h6 class="mb-1 text-1000">{{ $titulo }}</h6>
                                            <p class="fs--2 text-600 mb-2">{{ $ayuda }}</p>
                                            <div class="d-flex flex-center bg-light rounded mb-2" style="height: 110px;">
                                                @if ($config->{$campo})
                                                    <img src="{{ $config->{$campo} }}" alt="{{ $titulo }}" style="max-height: 100px; max-width: 100%; object-fit: contain;" />
                                                @else
                                                    <span class="text-500 fs--1">Sin imagen</span>
                                                @endif
                                            </div>
                                            <input class="form-control form-control-sm" name="imagen[{{ $campo }}]" type="file" accept=".jpg,.jpeg,.png,.webp,.ico" @disabled(! $contaboListo) />
                                            @if ($config->{$campo})
                                                <div class="form-check mt-2 mb-0">
                                                    <input class="form-check-input" id="quitar-{{ $campo }}" name="quitar[]" type="checkbox" value="{{ $campo }}" />
                                                    <label class="form-check-label fs--1" for="quitar-{{ $campo }}">Quitar</label>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Correos --}}
                    <div class="tab-pane fade {{ $activa === 'correos' ? 'show active' : '' }}" id="tab-correos" role="tabpanel">
                        <h5 class="mb-3 text-800">Asuntos y firma de los correos</h5>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="emailAsuntoReset">Asunto: recuperar contraseña</label><input class="form-control" id="emailAsuntoReset" name="emailAsuntoReset" value="{{ $v('emailAsuntoReset') }}" maxlength="150" /></div>
                            <div class="col-md-6"><label class="form-label" for="emailAsuntoBienve">Asunto: bienvenida</label><input class="form-control" id="emailAsuntoBienve" name="emailAsuntoBienve" value="{{ $v('emailAsuntoBienve') }}" maxlength="150" /></div>
                            <div class="col-md-6"><label class="form-label" for="emailAsuntoCuota">Asunto: cobro / cuota</label><input class="form-control" id="emailAsuntoCuota" name="emailAsuntoCuota" value="{{ $v('emailAsuntoCuota') }}" maxlength="150" /></div>
                            <div class="col-md-6"><label class="form-label" for="emailFirma">Firma</label><input class="form-control" id="emailFirma" name="emailFirma" value="{{ $v('emailFirma') }}" maxlength="255" /></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">Guardar configuración</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Recordar la pestaña abierta para volver a ella después de guardar.
        document.querySelectorAll('[data-pestana]').forEach(function (enlace) {
            enlace.addEventListener('shown.bs.tab', function () { document.getElementById('pestana').value = enlace.dataset.pestana; });
        });
    </script>
@endpush
