/**
 * Base de inicialización de Vue 3 para Nexus ERP con componentes universales
 * tomados 1:1 de Phoenix Dashboard Template
 */
window.NexusVue = {
    components: {},
    register(name, definition) {
        this.components[name] = definition;
    },
    mountAll() {
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

document.addEventListener('DOMContentLoaded', () => {
    window.NexusVue.mountAll();
});
