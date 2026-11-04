/**
 * Base de inicialización de Vue 3 para Nexus ERP y Sistema de Notificaciones Toast de Phoenix
 */
window.NexusVue = {
    components: {},
    register(name, definition) {
        this.components[name] = definition;
    },
    mountAll() {
        if (!window.Vue) return;
        document.querySelectorAll('[data-vue-component]').forEach(el => {
            const compName = el.getAttribute('data-vue-component');
            const compDef = this.components[compName];
            if (compDef && !el.__vue_app__) {
                let props = {};
                const propsRaw = el.getAttribute('data-vue-props');
                if (propsRaw) {
                    try {
                        props = JSON.parse(propsRaw);
                    } catch (e) {
                        console.error('Error parseando props para Vue ' + compName, e);
                    }
                }
                const app = Vue.createApp(compDef, props);
                // Registrar todos los componentes universales globalmente en cada app
                Object.entries(this.components).forEach(([k, c]) => app.component(k, c));
                app.mount(el);
                el.__vue_app__ = app;
            }
        });
    }
};

/**
 * Escapa texto para meterlo en HTML. Los avisos y la bitácora muestran datos que
 * escriben los usuarios (nombres de clientes, archivos, valores de campos): sin esto,
 * un cliente llamado «<img src=x onerror=…>» ejecutaría código a quien lo viera.
 */
window.NexusHtml = {
    escapar(valor) {
        return String(valor === null || valor === undefined ? '' : valor).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    },
    // Para valores que van dentro de un atributo class: solo letras, números y guiones.
    clase(valor) {
        return String(valor || '').replace(/[^a-z0-9-]/gi, '');
    }
};

/**
 * Toast Notifier de Phoenix para notificaciones elegantes flotantes
 */
window.NexusToast = {
    show(message, type = 'success', title = '') {
        const container = document.getElementById('nexus-toast-container');
        if (!container) return;

        const id = 'toast-' + Math.random().toString(36).substring(2, 9);
        const iconMap = {
            success: 'fa-check-circle text-success',
            warning: 'fa-exclamation-triangle text-warning',
            danger: 'fa-times-circle text-danger',
            info: 'fa-info-circle text-info',
            primary: 'fa-bell text-primary'
        };
        const defaultTitle = {
            success: 'Operación Exitosa',
            warning: 'Advertencia',
            danger: 'Error',
            info: 'Información',
            primary: 'Notificación'
        };

        const icon = iconMap[type] || iconMap.info;
        const toastTitle = title || defaultTitle[type] || 'Notificación';

        const toastHtml = `
            <div id="${id}" class="toast align-items-center border-0 shadow-lg mb-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header border-bottom border-200 py-2">
                    <span class="fas ${icon} me-2 fs--1"></span>
                    <strong class="me-auto fs--1 text-1000">${NexusHtml.escapar(toastTitle)}</strong>
                    <small class="text-500 fs--2">Ahora</small>
                    <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body py-2 fs--1 text-800">
                    ${NexusHtml.escapar(message)}
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(id);
        if (window.bootstrap && window.bootstrap.Toast) {
            const bsToast = new window.bootstrap.Toast(toastEl, { delay: 5000 });
            bsToast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        } else {
            toastEl.classList.add('show');
            setTimeout(() => {
                toastEl.remove();
            }, 5000);
        }
    }
};

// Event listener global para emitir toasts fácilmente: window.dispatchEvent(new CustomEvent('nexus:toast', { detail: { message, type, title } }))
window.addEventListener('nexus:toast', (e) => {
    if (e.detail && e.detail.message) {
        window.NexusToast.show(e.detail.message, e.detail.type || 'info', e.detail.title || '');
    }
});

/**
 * Gestor Global del Drawer de Auditoría Rápida (Bitácora)
 */
window.NexusAuditoria = {
    abrir(tabla, idRegistro) {
        const offcanvasEl = document.getElementById('offcanvasAuditoria');
        if (!offcanvasEl) return;

        const subEl = document.getElementById('auditoriaSubtitulo');
        const cargandoEl = document.getElementById('auditoriaCargando');
        const contEl = document.getElementById('auditoriaContenido');

        subEl.textContent = `${tabla} · ID: ${idRegistro}`;
        cargandoEl.classList.remove('d-none');
        contEl.classList.add('d-none');
        contEl.innerHTML = '';

        const bsOffcanvas = window.bootstrap && window.bootstrap.Offcanvas
            ? window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl)
            : null;

        if (bsOffcanvas) {
            bsOffcanvas.show();
        }

        fetch(`/sistema/bitacora/rapido?tabla=${encodeURIComponent(tabla)}&registro=${encodeURIComponent(idRegistro)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            cargandoEl.classList.add('d-none');
            contEl.classList.remove('d-none');
            subEl.textContent = `${data.tabla || tabla} · ID: #${data.registro}`;

            if (!data.items || data.items.length === 0) {
                contEl.innerHTML = `
                    <div class="text-center py-5">
                        <span class="fas fa-shield-alt text-400 fs-3 mb-2 d-block"></span>
                        <p class="text-600 fs--1 mb-0">No se encontraron movimientos registrados en la bitácora para este elemento.</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="timeline-basic mb-0">';
            data.items.forEach(item => {
                let camposHtml = '';
                if (item.campos && item.campos.length > 0) {
                    camposHtml = '<div class="table-responsive mt-2"><table class="table table-sm fs--2 mb-0 border"><tbody>';
                    item.campos.forEach(c => {
                        camposHtml += `
                            <tr>
                                <td class="text-700 fw-bold bg-100 py-1" style="width: 35%;">${NexusHtml.escapar(c.campo)}</td>
                                <td class="py-1">
                                    ${c.antes !== null && c.antes !== undefined ? `<span class="text-danger text-decoration-line-through me-1">${NexusHtml.escapar(c.antes)}</span>` : ''}
                                    <span class="text-success fw-semi-bold">${c.despues !== null && c.despues !== undefined ? NexusHtml.escapar(c.despues) : '—'}</span>
                                </td>
                            </tr>
                        `;
                    });
                    camposHtml += '</tbody></table></div>';
                }

                html += `
                    <div class="border-bottom border-200 pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge badge-phoenix badge-phoenix-${window.NexusHtml.clase(item.color)} fs--2">${NexusHtml.escapar(item.accion_nombre)}</span>
                            <span class="text-600 fs--2">${NexusHtml.escapar(item.fecha)}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="fas fa-user-circle text-primary fs--1"></span>
                            <span class="text-1000 fs--1 fw-bold">${NexusHtml.escapar(item.usuario)}</span>
                            ${item.ip ? `<span class="text-500 fs--2">(${NexusHtml.escapar(item.ip)})</span>` : ''}
                        </div>
                        ${camposHtml}
                    </div>
                `;
            });
            html += '</div>';
            contEl.innerHTML = html;
        })
        .catch(err => {
            console.error('Error al cargar bitácora', err);
            cargandoEl.classList.add('d-none');
            contEl.classList.remove('d-none');
            contEl.innerHTML = `
                <div class="alert alert-soft-danger fs--1 mb-0">
                    <span class="fas fa-exclamation-circle me-1"></span>Error al consultar el historial de auditoría.
                </div>
            `;
        });
    }
};

/**
 * Gestor Global de Modales de Confirmación Phoenix para Acciones Destructivas
 */
window.NexusConfirm = {
    callbackConfirm: null,
    preguntar({ titulo, mensaje, botonTexto, botonClase, icono, alerta, onConfirm }) {
        const modalEl = document.getElementById('modalConfirmacionPhoenix');
        if (!modalEl) {
            if (confirm(mensaje || '¿Confirmar acción?')) {
                if (typeof onConfirm === 'function') onConfirm();
            }
            return;
        }

        document.getElementById('confirmModalTitulo').textContent = titulo || 'Confirmar acción';
        document.getElementById('confirmModalMensaje').textContent = mensaje || '¿Estás seguro de continuar con esta operación?';
        
        const btnEjecutar = document.getElementById('confirmModalBotonEjecutar');
        btnEjecutar.innerHTML = `<span class="fas fa-check me-1"></span>${NexusHtml.escapar(botonTexto || 'Confirmar')}`;
        btnEjecutar.className = `btn btn-sm ${window.NexusHtml.clase(botonClase) || 'btn-danger'}`;

        const iconoEl = document.getElementById('confirmModalIcono');
        if (icono) {
            iconoEl.className = `fas ${window.NexusHtml.clase(icono)} me-2 fs-0`;
        } else {
            iconoEl.className = 'fas fa-exclamation-triangle text-danger me-2 fs-0';
        }

        const alertaEl = document.getElementById('confirmModalAlerta');
        if (alerta) {
            alertaEl.textContent = alerta;
            alertaEl.classList.remove('d-none');
        } else {
            alertaEl.textContent = 'Esta acción quedará registrada en la bitácora de auditoría.';
        }

        this.callbackConfirm = onConfirm;

        const bsModal = window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        if (bsModal) {
            bsModal.show();
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.NexusVue.mountAll();

    // Listener para el botón de ejecución del modal de confirmación
    const btnEjecutar = document.getElementById('confirmModalBotonEjecutar');
    if (btnEjecutar) {
        btnEjecutar.addEventListener('click', () => {
            const modalEl = document.getElementById('modalConfirmacionPhoenix');
            const bsModal = window.bootstrap && window.bootstrap.Modal
                ? window.bootstrap.Modal.getInstance(modalEl)
                : null;
            if (bsModal) bsModal.hide();

            if (typeof window.NexusConfirm.callbackConfirm === 'function') {
                window.NexusConfirm.callbackConfirm();
                window.NexusConfirm.callbackConfirm = null;
            }
        });
    }

    // Interceptar botones con atributo data-confirm-phoenix="Mensaje"
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm-phoenix]');
        if (!btn) return;

        e.preventDefault();
        const mensaje = btn.getAttribute('data-confirm-phoenix');
        const titulo = btn.getAttribute('data-confirm-titulo') || 'Confirmar acción';
        const botonTexto = btn.getAttribute('data-confirm-boton') || 'Confirmar';
        const form = btn.closest('form');

        window.NexusConfirm.preguntar({
            titulo,
            mensaje,
            botonTexto,
            onConfirm: () => {
                if (form) {
                    form.submit();
                } else if (btn.tagName === 'A' && btn.href) {
                    window.location.href = btn.href;
                }
            }
        });
    });

    // Interceptar botones con atributo data-auditoria-tabla y data-auditoria-id
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-auditoria-tabla]');
        if (!btn) return;
        e.preventDefault();
        const tabla = btn.getAttribute('data-auditoria-tabla');
        const id = btn.getAttribute('data-auditoria-id');
        window.NexusAuditoria.abrir(tabla, id);
    });
});
