<template>
    <div class="login-wrapper">
        <!-- Panel izquierdo — Branding -->
        <div class="login-brand">
            <div class="brand-content">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <span>N</span>
                    </div>
                    <span class="logo-text">Nexus<strong>ERP</strong></span>
                </div>
                <div class="brand-tagline">
                    <h2>Gestión empresarial<br/>sin fronteras</h2>
                    <p>ERP + CRM integrado para empresas<br/>de servicios en Centroamérica.</p>
                </div>
                <div class="brand-modules">
                    <div class="module-pill" v-for="m in modulos" :key="m">{{ m }}</div>
                </div>
            </div>
            <div class="brand-bg">
                <div class="bg-circle c1"></div>
                <div class="bg-circle c2"></div>
                <div class="bg-circle c3"></div>
                <div class="grid-lines"></div>
            </div>
        </div>

        <!-- Panel derecho — Formulario -->
        <div class="login-form-panel">
            <div class="form-container">
                <!-- Header -->
                <div class="form-header">
                    <div class="form-badge">Sistema de Gestión</div>
                    <h1>Iniciar sesión</h1>
                    <p>Ingresa tus credenciales para acceder al sistema.</p>
                </div>

                <!-- Alerta de error -->
                <transition name="slide-down">
                    <div class="alert-error" v-if="errorMsg">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        {{ errorMsg }}
                    </div>
                </transition>

                <!-- Formulario -->
                <form @submit.prevent="handleLogin" class="login-form" novalidate>
                    <!-- Campo usuario -->
                    <div class="field-group" :class="{ 'has-error': errors.login, 'is-focused': focused === 'login' }">
                        <label>Usuario o correo electrónico</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input
                                v-model="form.login"
                                type="text"
                                placeholder="usuario o correo@empresa.com"
                                autocomplete="username"
                                @focus="focused = 'login'"
                                @blur="focused = null; validarLogin()"
                            />
                        </div>
                        <span class="field-error" v-if="errors.login">{{ errors.login }}</span>
                    </div>

                    <!-- Campo contraseña -->
                    <div class="field-group" :class="{ 'has-error': errors.password, 'is-focused': focused === 'password' }">
                        <label>Contraseña</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input
                                v-model="form.password"
                                :type="mostrarPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                @focus="focused = 'password'"
                                @blur="focused = null; validarPassword()"
                            />
                            <button type="button" class="toggle-password" @click="mostrarPassword = !mostrarPassword" tabindex="-1">
                                <svg v-if="!mostrarPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <span class="field-error" v-if="errors.password">{{ errors.password }}</span>
                    </div>

                    <!-- Recordarme -->
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.remember" />
                            <span class="checkbox-custom"></span>
                            Mantener sesión activa (30 días)
                        </label>
                    </div>

                    <!-- Botón submit -->
                    <button type="submit" class="btn-login" :class="{ loading: cargando }" :disabled="cargando">
                        <span class="btn-text" v-if="!cargando">Ingresar al sistema</span>
                        <span class="btn-loader" v-else>
                            <span class="spinner"></span>
                            Verificando...
                        </span>
                        <svg v-if="!cargando" class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <!-- Footer -->
                <div class="form-footer">
                    <span>NexusERP v1.0</span>
                    <span>•</span>
                    <span>Centroamérica</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router   = useRouter();
const auth     = useAuthStore();
const cargando = ref(false);
const errorMsg = ref('');
const focused  = ref(null);
const mostrarPassword = ref(false);

const modulos = ['RRHH', 'Clientes', 'Finanzas', 'CRM', 'Inventario', 'Compras'];

const form = reactive({ login: '', password: '', remember: false });
const errors = reactive({ login: '', password: '' });

function validarLogin() {
    errors.login = form.login.trim() ? '' : 'El usuario o correo es obligatorio.';
}

function validarPassword() {
    errors.password = form.password.length >= 6 ? '' : 'Mínimo 6 caracteres.';
}

async function handleLogin() {
    validarLogin();
    validarPassword();
    if (errors.login || errors.password) return;

    cargando.value = true;
    errorMsg.value = '';

    const result = await auth.login({
        login:    form.login.trim(),
        password: form.password,
        remember: form.remember,
    });

    cargando.value = false;

    if (result.success) {
        router.push({ name: 'dashboard' });
    } else {
        errorMsg.value = result.message;
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.login-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
    font-family: 'DM Sans', sans-serif;
    background: #09090f;
}

/* ── Panel izquierdo ────────────────────────────────── */
.login-brand {
    position: relative;
    background: linear-gradient(135deg, #0d0d1a 0%, #0a0a18 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 3rem;
}

.brand-bg { position: absolute; inset: 0; pointer-events: none; }

.bg-circle {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.15;
}
.c1 { width: 400px; height: 400px; background: #6366f1; top: -100px; left: -100px; animation: float1 8s ease-in-out infinite; }
.c2 { width: 300px; height: 300px; background: #8b5cf6; bottom: -50px; right: -50px; animation: float2 10s ease-in-out infinite; }
.c3 { width: 200px; height: 200px; background: #06b6d4; top: 50%; left: 50%; transform: translate(-50%,-50%); animation: float3 6s ease-in-out infinite; }

.grid-lines {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px);
    background-size: 40px 40px;
}

@keyframes float1 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(30px,20px)} }
@keyframes float2 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-20px,30px)} }
@keyframes float3 { 0%,100%{transform:translate(-50%,-50%)} 50%{transform:translate(-45%,-55%)} }

.brand-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo-icon {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 30px rgba(99,102,241,0.4);
}

.logo-icon span {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.5rem;
    color: white;
}

.logo-text {
    font-family: 'Syne', sans-serif;
    font-size: 1.6rem;
    color: #e2e8f0;
    letter-spacing: -0.5px;
}

.logo-text strong {
    color: #818cf8;
    font-weight: 800;
}

.brand-tagline h2 {
    font-family: 'Syne', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #f1f5f9;
    line-height: 1.25;
    margin-bottom: 0.75rem;
}

.brand-tagline p {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.6;
}

.brand-modules {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.module-pill {
    background: rgba(99,102,241,0.1);
    border: 1px solid rgba(99,102,241,0.2);
    color: #818cf8;
    padding: 0.35rem 0.85rem;
    border-radius: 100px;
    font-size: 0.78rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* ── Panel derecho ───────────────────────────────────── */
.login-form-panel {
    background: #09090f;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    border-left: 1px solid rgba(255,255,255,0.04);
}

.form-container {
    width: 100%;
    max-width: 420px;
    animation: fadeUp 0.5s ease both;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.form-header { margin-bottom: 2rem; }

.form-badge {
    display: inline-block;
    background: rgba(99,102,241,0.1);
    border: 1px solid rgba(99,102,241,0.25);
    color: #818cf8;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 0.3rem 0.8rem;
    border-radius: 100px;
    margin-bottom: 1rem;
}

.form-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 1.9rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
}

.form-header p {
    color: #475569;
    font-size: 0.9rem;
}

/* ── Alerta ──────────────────────────────────────────── */
.alert-error {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    color: #fca5a5;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
}

.alert-error svg { width: 16px; height: 16px; flex-shrink: 0; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

/* ── Campos ──────────────────────────────────────────── */
.login-form { display: flex; flex-direction: column; gap: 1.25rem; }

.field-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
    transition: color 0.2s;
}

.field-group.is-focused label { color: #818cf8; }
.field-group.has-error label  { color: #f87171; }

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    width: 16px;
    height: 16px;
    color: #334155;
    pointer-events: none;
    transition: color 0.2s;
}

.field-group.is-focused .input-icon { color: #818cf8; }

.input-wrapper input {
    width: 100%;
    background: #0f0f1e;
    border: 1px solid #1e293b;
    border-radius: 10px;
    padding: 0.85rem 1rem 0.85rem 2.75rem;
    color: #e2e8f0;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.2s;
}

.input-wrapper input::placeholder { color: #334155; }

.input-wrapper input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
}

.field-group.has-error .input-wrapper input {
    border-color: rgba(239,68,68,0.4);
    box-shadow: 0 0 0 3px rgba(239,68,68,0.08);
}

.toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: #334155;
    padding: 4px;
    display: flex;
    transition: color 0.2s;
}

.toggle-password:hover { color: #818cf8; }
.toggle-password svg { width: 16px; height: 16px; }

.field-error {
    display: block;
    color: #f87171;
    font-size: 0.78rem;
    margin-top: 0.4rem;
}

/* ── Checkbox ────────────────────────────────────────── */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    cursor: pointer;
    font-size: 0.85rem;
    color: #475569;
    user-select: none;
}

.checkbox-label input[type="checkbox"] { display: none; }

.checkbox-custom {
    width: 18px;
    height: 18px;
    border: 1px solid #1e293b;
    border-radius: 5px;
    background: #0f0f1e;
    flex-shrink: 0;
    transition: all 0.2s;
    position: relative;
}

.checkbox-label input:checked + .checkbox-custom {
    background: #6366f1;
    border-color: #6366f1;
}

.checkbox-label input:checked + .checkbox-custom::after {
    content: '';
    position: absolute;
    top: 2px; left: 5px;
    width: 5px; height: 9px;
    border: 2px solid white;
    border-top: none;
    border-left: none;
    transform: rotate(45deg);
}

/* ── Botón ───────────────────────────────────────────── */
.btn-login {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 0.9rem 1.5rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 0.25rem;
    position: relative;
    overflow: hidden;
}

.btn-login::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #818cf8, #a78bfa);
    opacity: 0;
    transition: opacity 0.2s;
}

.btn-login:hover::before { opacity: 1; }
.btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }
.btn-login:active { transform: translateY(0); }
.btn-login:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.btn-text, .btn-loader { position: relative; z-index: 1; }
.btn-loader { display: flex; align-items: center; gap: 0.5rem; }

.btn-arrow {
    width: 16px;
    height: 16px;
    position: relative;
    z-index: 1;
    transition: transform 0.2s;
}

.btn-login:hover .btn-arrow { transform: translateX(3px); }

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Footer ──────────────────────────────────────────── */
.form-footer {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 2rem;
    color: #1e293b;
    font-size: 0.78rem;
}

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 768px) {
    .login-wrapper { grid-template-columns: 1fr; }
    .login-brand   { display: none; }
    .login-form-panel { padding: 2rem 1.5rem; }
}
</style>