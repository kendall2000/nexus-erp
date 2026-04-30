/**
 * NexusERP — Módulo Configuración del Sistema
 * Archivo: resources/views/modulos/configuracion/index.js
 */

new Vue({
    el: '#config-app',
    data: {
        cargando: true,
        guardando: false,
        form: {}, // Aquí volcaremos toda la tabla
        
        // Array auxiliar para generar dinámicamente las tarjetas de imágenes en el HTML
        camposImagenes: [
            { key: 'imgLogo', label: 'Logo Principal' },
            { key: 'imgLogoOscuro', label: 'Logo Modo Oscuro' },
            { key: 'imgFavicon', label: 'Favicon' },
            { key: 'imgFondoLogin', label: 'Fondo del Login' },
            { key: 'imgBannerDashboard', label: 'Banner Dashboard' },
            { key: 'imgAvatarDefault', label: 'Avatar Default' },
            { key: 'imgFondoEmail', label: 'Header de Email' },
            { key: 'imgLogoEmail', label: 'Logo para Email' },
            { key: 'imgLogoReporte', label: 'Logo Reportes PDF' },
            { key: 'imgFondoError404', label: 'Imagen Error 404' }
        ]
    },

    mounted() {
        this.cargarConfiguracion();
    },

    methods: {
        async cargarConfiguracion() {
            this.cargando = true;
            try {
                // Asumiendo que apiUrl ya fue definido en header.blade.php
                const response = await fetch(apiUrl + '/core/configuracion', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
                    }
                });

                const res = await response.json();
                if (res.success) {
                    this.form = res.data || {};
                } else {
                    Swal.fire('Error', res.message || 'No se pudo cargar la configuración', 'error');
                }
            } catch (error) {
                console.error("Error cargando configuración:", error);
                Swal.fire('Error', 'Ocurrió un error de conexión', 'error');
            } finally {
                this.cargando = false;
            }
        },

        async guardarConfiguracion() {
            this.guardando = true;
            try {
                const response = await fetch(apiUrl + '/core/configuracion', {
                    method: 'POST', // O PUT según configures tu API route
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
                    },
                    body: JSON.stringify(this.form)
                });

                const res = await response.json();

                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Configuración Guardada!',
                        text: 'Los cambios se aplicarán en el próximo inicio de sesión o al recargar.',
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', res.message || 'Error al guardar los cambios', 'error');
                }
            } catch (error) {
                console.error("Error guardando:", error);
                Swal.fire('Error', 'Error de red al intentar guardar', 'error');
            } finally {
                this.guardando = false;
            }
        },

        /**
         * Permite seleccionar un archivo de imagen y convertirlo a Base64
         * para guardarlo directamente en el campo text de la base de datos.
         */
        procesarImagen(event, fieldKey) {
            const file = event.target.files[0];
            if (!file) return;

            // Validación de peso (opcional, ej. 2MB max para evitar saturar Base64)
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Atención', 'La imagen pesa más de 2MB. Es preferible pegar una URL web o reducir su peso.', 'warning');
                event.target.value = ''; // Limpiamos el input
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                // Actualizamos reactivamente el objeto form con la nueva string Base64
                this.$set(this.form, fieldKey, e.target.result);
            };
            reader.readAsDataURL(file);
        }
    }
});