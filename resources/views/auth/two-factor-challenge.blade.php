@extends('layouts.auth', ['titulo' => 'Verificación en dos pasos'])

@section('contenido')
    <div class="text-center">
        <h4 class="text-1000">Verificación en dos pasos</h4>
        <p class="text-700 mb-2" id="ayuda-codigo">Escribe el código de 6 dígitos de tu aplicación de autenticación.</p>
        <p class="text-700 mb-2 d-none" id="ayuda-recuperacion">Escribe uno de tus códigos de recuperación.</p>
        <p class="fs--2 mb-4">¿No tienes acceso a tu aplicación? <a href="#" id="alternar" class="fw-bold">Usar un código de recuperación</a></p>

        @include('partials.alertas')

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="verification-form" id="form-2fa">
            @csrf
            <div class="mb-4" id="campo-codigo">
                <label class="form-label visually-hidden" for="code">Código</label>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2" id="otp-boxes">
                    <input class="form-control px-2 text-center otp-digit" type="text" inputmode="numeric" maxlength="1" />
                    <input class="form-control px-2 text-center otp-digit" type="text" inputmode="numeric" maxlength="1" />
                    <input class="form-control px-2 text-center otp-digit" type="text" inputmode="numeric" maxlength="1" />
                    <span>-</span>
                    <input class="form-control px-2 text-center otp-digit" type="text" inputmode="numeric" maxlength="1" />
                    <input class="form-control px-2 text-center otp-digit" type="text" inputmode="numeric" maxlength="1" />
                    <input class="form-control px-2 text-center otp-digit" type="text" inputmode="numeric" maxlength="1" />
                </div>
                <input class="form-control text-center fs-2 d-none" id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" autofocus />
            </div>
            <div class="mb-4 d-none text-start" id="campo-recuperacion">
                <label class="form-label" for="recovery_code">Código de recuperación</label>
                <div class="form-icon-container">
                    <input class="form-control form-icon-input" id="recovery_code" name="recovery_code" type="text" placeholder="XXXX-XXXX" autocomplete="off" />
                    <span class="fas fa-key text-900 fs--1 form-icon"></span>
                </div>
            </div>
            <button class="btn btn-primary w-100 mb-3" type="submit">Verificar</button>
        </form>
    </div>

    <script>
        (function () {
            const digits = Array.from(document.querySelectorAll('.otp-digit'));
            const codeInput = document.getElementById('code');
            const syncCode = () => { codeInput.value = digits.map(d => d.value).join(''); };

            digits.forEach((input, idx) => {
                input.addEventListener('input', (e) => {
                    input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
                    syncCode();
                    if (input.value && idx < digits.length - 1) digits[idx + 1].focus();
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && idx > 0) digits[idx - 1].focus();
                });
                input.addEventListener('paste', (e) => {
                    const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                    if (text) {
                        e.preventDefault();
                        text.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
                        syncCode();
                        if (digits[Math.min(text.length, 5)]) digits[Math.min(text.length, 5)].focus();
                    }
                });
            });
            if (digits[0]) digits[0].focus();

            document.getElementById('alternar').addEventListener('click', function (e) {
                e.preventDefault();
                const recuperacion = document.getElementById('campo-recuperacion').classList.toggle('d-none') === false;
                document.getElementById('campo-codigo').classList.toggle('d-none', recuperacion);
                document.getElementById('ayuda-codigo').classList.toggle('d-none', recuperacion);
                document.getElementById('ayuda-recuperacion').classList.toggle('d-none', !recuperacion);
                if (recuperacion) {
                    digits.forEach(d => d.value = '');
                    codeInput.value = '';
                    document.getElementById('recovery_code').focus();
                } else {
                    document.getElementById('recovery_code').value = '';
                    if (digits[0]) digits[0].focus();
                }
                this.textContent = recuperacion ? 'Usar el código de la aplicación' : 'Usar un código de recuperación';
            });
        })();
    </script>
@endsection
