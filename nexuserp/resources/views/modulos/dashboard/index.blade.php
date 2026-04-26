@extends('layouts.app')

@section('breadcrumb', 'Dashboard')

@section('content')
<div class="row g-3 mb-3" id="dashboard-app">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <span data-feather="home" class="me-2"></span>
                    Bienvenido a NexusERP
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Has iniciado sesión correctamente como
                    <strong>@{{ usuario.nombre_completo }}</strong>
                    en la empresa
                    <strong>@{{ usuario.empresa ? usuario.empresa.nombre_comercial : '' }}</strong>.
                </p>
            </div>
        </div>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="col-md-3" v-for="card in tarjetas" :key="card.titulo">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <span :data-feather="card.icono" class="text-primary"></span>
                    </div>
                </div>
                <div>
                    <h6 class="mb-0 text-muted">@{{ card.titulo }}</h6>
                    <h4 class="mb-0 fw-bold">@{{ card.valor }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verifica que haya token — si no, redirige al login
    if (!sessionStorage.getItem('nexus_token')) {
        window.location.href = server + '/login';
        return;
    }

    new Vue({
        el: '#dashboard-app',
        data: {
            usuario: JSON.parse(sessionStorage.getItem('nexus_usuario') || '{}'),
            tarjetas: [
                { titulo: 'Clientes Activos',  icono: 'briefcase',   valor: '...' },
                { titulo: 'Empleados',          icono: 'users',       valor: '...' },
                { titulo: 'Contratos Vigentes', icono: 'file-text',   valor: '...' },
                { titulo: 'Tickets Abiertos',   icono: 'headphones',  valor: '...' },
            ]
        },
        async mounted() {
            await this.cargarResumen();
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },
        methods: {
            async cargarResumen() {
                try {
                    const res = await fetch(apiUrl + '/dashboard/resumen', {
                        headers: {
                            'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.data) {
                            this.tarjetas[0].valor = data.data.clientes       ?? '0';
                            this.tarjetas[1].valor = data.data.empleados      ?? '0';
                            this.tarjetas[2].valor = data.data.contratos      ?? '0';
                            this.tarjetas[3].valor = data.data.tickets        ?? '0';
                        }
                    }
                } catch(e) {
                    console.error('Error cargando resumen:', e);
                }
            }
        }
    });
});
</script>
@endsection