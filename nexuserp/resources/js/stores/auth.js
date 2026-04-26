import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const usuario  = ref(JSON.parse(localStorage.getItem('nexus_usuario') || 'null'));
    const token    = ref(localStorage.getItem('nexus_token') || null);
    const cargando = ref(false);
    const error    = ref(null);

    const estaAutenticado = computed(() => !!token.value);
    const nombreUsuario   = computed(() => usuario.value?.nombre_completo || '');
    const permisos        = computed(() => usuario.value?.permisos || []);

    async function login(credenciales) {
        cargando.value = true;
        error.value    = null;
        try {
            const { data } = await axios.post('/auth/login', credenciales);
            token.value   = data.data.token;
            usuario.value = data.data.usuario;
            localStorage.setItem('nexus_token',   data.data.token);
            localStorage.setItem('nexus_usuario', JSON.stringify(data.data.usuario));
            return { success: true };
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al iniciar sesión.';
            return { success: false, message: error.value };
        } finally {
            cargando.value = false;
        }
    }

    async function logout() {
        try { await axios.post('/auth/logout'); } catch {}
        token.value   = null;
        usuario.value = null;
        localStorage.removeItem('nexus_token');
        localStorage.removeItem('nexus_usuario');
    }

    function tienePermiso(codigo) {
        return permisos.value.includes(codigo);
    }

    return { usuario, token, cargando, error, estaAutenticado, nombreUsuario, login, logout, tienePermiso };
});