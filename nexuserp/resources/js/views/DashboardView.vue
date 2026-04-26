<template>
    <div class="dashboard">
        <h1>Dashboard — NexusERP</h1>
        <p>Bienvenido, {{ auth.nombreUsuario }}</p>
        <button @click="cerrarSesion">Cerrar sesión</button>
    </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const auth   = useAuthStore();
const router = useRouter();

async function cerrarSesion() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>