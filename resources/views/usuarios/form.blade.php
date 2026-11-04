@extends('layouts.app', ['titulo' => $usuario->exists ? 'Editar usuario' : 'Nuevo usuario'])

@section('contenido')
    <x-page-header
        :titulo="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario'"
        icono="fas fa-user-cog"
        subtitulo="Gestiona las credenciales de acceso, rol asignado y permisos adicionales del usuario."
        :migas="[
            ['label' => 'Usuarios', 'url' => route('usuarios.index')],
            ['label' => $usuario->exists ? $usuario->nombre_completo : 'Nuevo'],
        ]"
    />

    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario->id_usuario) : route('usuarios.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($usuario->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            {{-- Columna Principal: Información General y Credenciales (8 columnas) --}}
            <div class="col-12 col-xl-8">
                {{-- Tarjeta 1: Información Personal y de Cuenta --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header border-bottom border-200 bg-body-emphasis py-3">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-user text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Información de la Cuenta</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="nombre_completo">Nombre completo <span class="text-danger">*</span></label>
                            <div class="form-icon-container">
                                <input class="form-control form-icon-input @error('nombre_completo') is-invalid @enderror" id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo', $usuario->nombre_completo) }}" required maxlength="200" placeholder="Ej. Kendall Moreno" />
                                <span class="fas fa-id-card text-900 fs--1 form-icon"></span>
                            </div>
                            @error('nombre_completo')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label" for="username">Nombre de usuario <span class="text-danger">*</span></label>
                                <div class="form-icon-container">
                                    <input class="form-control form-icon-input @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $usuario->username) }}" required maxlength="60" autocomplete="off" placeholder="kmoreno" />
                                    <span class="fas fa-user-tag text-900 fs--1 form-icon"></span>
                                </div>
                                <div class="form-text fs--2">Sin espacios. Se usará para iniciar sesión.</div>
                                @error('username')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-7">
                                <label class="form-label" for="email">Correo electrónico corporativo <span class="text-danger">*</span></label>
                                <div class="form-icon-container">
                                    <input class="form-control form-icon-input @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="150" placeholder="usuario@empresa.com" />
                                    <span class="fas fa-envelope text-900 fs--1 form-icon"></span>
                                </div>
                                <div class="form-text fs--2">Para notificaciones y recuperación de accesos.</div>
                                @error('email')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="id_rol">Rol del sistema <span class="text-danger">*</span></label>
                                <div class="form-icon-container">
                                    <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select @error('id_rol') is-invalid @enderror" id="id_rol" name="id_rol" required>
                                        <option value="">Selecciona rol…</option>
                                        @foreach ($roles as $rol)
                                            <option value="{{ $rol->id_rol }}" @selected((int) old('id_rol', $idRol) === $rol->id_rol)>{{ $rol->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('id_rol')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="id_sucursal">Sucursal de adscripción</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select @error('id_sucursal') is-invalid @enderror" id="id_sucursal" name="id_sucursal">
                                    <option value="">Sin sucursal fija (Global)</option>
                                    @foreach ($sucursales as $s)
                                        <option value="{{ $s->id_sucursal }}" @selected((int) old('id_sucursal', $usuario->id_sucursal) === $s->id_sucursal)>{{ $s->etiqueta() }}</option>
                                    @endforeach
                                </select>
                                @error('id_sucursal')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Acceso a otras empresas: un rol por empresa (solo el administrador lo ve) --}}
                @if (($otrasEmpresas ?? collect())->isNotEmpty())
                    <div class="card border border-200 shadow-none mb-4">
                        <div class="card-header border-bottom border-200 bg-body-emphasis py-3">
                            <h5 class="mb-0 text-1000"><span class="fas fa-building text-primary me-2"></span>Acceso a otras empresas</h5>
                            <p class="mb-0 fs--2 text-600">Con un rol en otra empresa, el usuario puede cambiarse a ella desde la barra superior y usa los permisos de ese rol.</p>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach ($otrasEmpresas as $otra)
                                    @php $idOtra = $otra['empresa']->id_empresa; @endphp
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="acceso_{{ $idOtra }}">{{ $otra['empresa']->nombre_comercial }}</label>
                                        <select class="form-select @error('accesos.'.$idOtra) is-invalid @enderror" id="acceso_{{ $idOtra }}" name="accesos[{{ $idOtra }}]">
                                            <option value="">Sin acceso</option>
                                            @foreach ($otra['roles'] as $rolOtra)
                                                <option value="{{ $rolOtra->id_rol }}" @selected((int) old('accesos.'.$idOtra, $otra['actual']) === $rolOtra->id_rol)>{{ $rolOtra->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('accesos.'.$idOtra)<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                                        @if ($otra['roles']->isEmpty())
                                            <div class="fs--2 text-600 mt-1">Esta empresa todavía no tiene roles.</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Tarjeta 2: Seguridad y Contraseñas --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header border-bottom border-200 bg-body-emphasis py-3">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-lock text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Seguridad y Credenciales</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            @if ($usuario->exists && $usuario->id_usuario === auth()->id())
                                <div class="col-12">
                                    <label class="form-label" for="password_actual">Contraseña actual</label>
                                    <div class="form-icon-container">
                                        <input class="form-control form-icon-input @error('password_actual') is-invalid @enderror" id="password_actual" name="password_actual" type="password" autocomplete="current-password" placeholder="••••••••••••" />
                                        <span class="fas fa-lock text-900 fs--1 form-icon"></span>
                                    </div>
                                    <div class="form-text fs--2 mt-1">Necesaria solo si cambias tu contraseña, tu usuario o tu correo.</div>
                                    @error('password_actual')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="password">{{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña de acceso *' }}</label>
                                <div class="form-icon-container">
                                    <input class="form-control form-icon-input @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" @required(! $usuario->exists) placeholder="••••••••••••" />
                                    <span class="fas fa-key text-900 fs--1 form-icon"></span>
                                </div>
                                @error('password')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                                <div class="form-icon-container">
                                    <input class="form-control form-icon-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Repite la contraseña" />
                                    <span class="fas fa-check-double text-900 fs--1 form-icon"></span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-soft-secondary fs--2 mb-3 py-2 px-3 border border-300">
                            <span class="fas fa-shield-alt text-primary me-1"></span>
                            {{ \App\Support\Contrasenas::requisitos() }}
                            @if ($usuario->exists && $usuario->id_usuario !== auth()->id())
                                <br><span class="text-warning fw-semi-bold">Nota:</span> Si cambias la contraseña, se revocarán automáticamente todas las sesiones abiertas de este usuario.
                            @endif
                        </div>

                        @if ($usuario->id_usuario !== auth()->id())
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="debe_cambiar_password" value="0" />
                                <input class="form-check-input" id="debe_cambiar_password" name="debe_cambiar_password" type="checkbox" value="1"
                                    @checked(old('debe_cambiar_password', $usuario->exists ? $usuario->debe_cambiar_password : true)) />
                                <label class="form-check-label fw-semi-bold text-900" for="debe_cambiar_password">Pedir que cambie la contraseña al entrar</label>
                                <div class="form-text fs--2 mt-0">Permite que el usuario defina su propia clave privada y confidencial.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Columna Lateral: Panel de Perfil / Foto / Acciones (4 columnas) --}}
            <div class="col-12 col-xl-4">
                {{-- Tarjeta: Foto de Perfil e Identidad --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header border-bottom border-200 bg-body-emphasis py-3">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-camera text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Foto de Perfil</h5>
                        </div>
                    </div>
                    <div class="card-body text-center p-4">
                        <div class="avatar avatar-5xl mx-auto mb-3 position-relative">
                            @if ($usuario->avatar_url)
                                <img class="rounded-circle shadow-sm border border-200" src="{{ $usuario->avatar_url }}" alt="{{ $usuario->nombre_completo }}" style="object-fit: cover; width: 110px; height: 110px;" />
                            @else
                                <div class="avatar-name rounded-circle bg-primary-subtle text-primary fw-bolder fs-3 d-flex align-items-center justify-content-center mx-auto" style="width: 110px; height: 110px;">
                                    <span class="fas fa-user"></span>
                                </div>
                            @endif
                        </div>

                        <div class="text-start mt-3">
                            <label class="form-label fs--1 fw-bold text-700 mb-1" for="foto">Subir nueva imagen</label>
                            <input class="form-control form-control-sm @error('foto') is-invalid @enderror" id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp" />
                            <div class="form-text fs--2">Formatos válidos: JPG, PNG o WEBP (máx. 2 MB).</div>
                            @error('foto')<div class="text-danger fs--2 mt-1">{{ $message }}</div>@enderror

                            @if ($usuario->avatar_url)
                                <div class="form-check mt-3 border-top border-200 pt-2 mb-0">
                                    <input class="form-check-input" id="quitar_foto" name="quitar_foto" type="checkbox" value="1" />
                                    <label class="form-check-label fs--1 text-danger fw-semi-bold" for="quitar_foto">
                                        <span class="fas fa-trash-alt me-1"></span>Eliminar foto actual
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tarjeta: Panel de Publicación / Guardar --}}
                <div class="card border border-200 shadow-none">
                    <div class="card-header border-bottom border-200 bg-body-emphasis py-3">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-sliders-h text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Acciones del Usuario</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" type="submit">
                                <span class="fas fa-save me-2"></span>{{ $usuario->exists ? 'Guardar Cambios' : 'Crear Usuario' }}
                            </button>
                            <a class="btn btn-phoenix-secondary" href="{{ route('usuarios.index') }}">
                                <span class="fas fa-times me-2"></span>Cancelar
                            </a>
                        </div>
                        @if ($usuario->exists)
                            <div class="border-top border-200 mt-4 pt-3 text-700 fs--2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Registrado el:</span>
                                    <span class="fw-semi-bold">{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Último acceso:</span>
                                    <span class="fw-semi-bold">{{ $usuario->ultimo_login_at ? $usuario->ultimo_login_at->format('d/m/Y H:i') : 'Nunca' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if ($usuario->exists)
        {{-- Permisos extra: además de los de su rol --}}
        <div class="card border border-200 shadow-none mt-4">
            <div class="card-header border-bottom border-200 bg-body-emphasis py-3">
                <div class="d-flex align-items-center">
                    <span class="fas fa-key text-primary me-2 fs-0"></span>
                    <h5 class="mb-0 text-1000">Permisos extra</h5>
                </div>
            </div>
            <div class="card-body">
                @if ($usuario->esAdministrador())
                    <div class="alert alert-soft-success d-flex align-items-center mb-0">
                        <span class="fas fa-check-circle fs-3 me-3 text-success"></span>
                        <div>
                            <h6 class="text-success fw-bolder mb-1">Acceso de Administrador Total</h6>
                            <p class="mb-0 fs--1 text-700">Este usuario cuenta con privilegios de Administrador Global. Todos los permisos del ERP se encuentran otorgados de manera irrestricta.</p>
                        </div>
                    </div>
                @else
                    <p class="text-700 fs--1 mb-3">
                        Los permisos adicionales se suman a los ya otorgados por su rol principal (<strong>{{ $usuario->roles->pluck('nombre')->join(', ') ?: 'sin rol asignado' }}</strong>). Los permisos en color gris ya son heredados y fijos.
                        @if ($propios !== null) <span class="text-warning">Solo puedes otorgar permisos que tú mismo posees.</span> @endif
                    </p>
                    <form method="POST" action="{{ route('usuarios.permisos', $usuario->id_usuario) }}">
                        @csrf
                        @method('PUT')
                        @include('permisos.matriz', ['matriz' => $matriz, 'marcados' => $extras, 'propios' => $propios, 'heredados' => $heredados])
                        <div class="mt-3">
                            <button class="btn btn-primary" type="submit"><span class="fas fa-save me-2"></span>Guardar permisos extra</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endsection
