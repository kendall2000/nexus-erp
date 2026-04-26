{{-- ============================================================
     MENÚ LATERAL — NexusERP
     Vue 2 compatible — templates en JS con @{{ }} para Blade
     Comportamiento de Acordeón Activado
     ============================================================ --}}

<nav class="navbar navbar-vertical navbar-expand-lg">

    <script>
        var navbarStyle = window.config && window.config.config ? window.config.config.phoenixNavbarStyle : null;
        if (navbarStyle && navbarStyle !== 'transparent') {
            document.querySelector('body').classList.add('navbar-' + navbarStyle);
        }
    </script>

    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content">
            {{-- El ID navbarVerticalNav será el "Padre" de nuestro acordeón --}}
            <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                <div id="menu-app" class="w-100"></div>
            </ul>
        </div>
    </div>

    <div class="navbar-vertical-footer">
        <button class="btn navbar-vertical-toggle border-0 fw-semi-bold w-100 white-space-nowrap d-flex align-items-center">
            <span class="uil uil-left-arrow-to-left fs-0"></span>
            <span class="uil uil-arrow-from-right fs-0"></span>
            <span class="navbar-vertical-footer-text ms-2">Collapsed View</span>
        </button>
    </div>
</nav>

{{-- ── Plantillas de Vue separadas del JS para evitar conflicto con Blade ── --}}

{{-- Template: enlace directo (Nivel 2 sin hijos) --}}
<script type="text/x-template" id="tpl-menu-link">
    <a class="nav-link label-1" 
       :class="{ 'active': isActive }" 
       :href="enlaceCalculado" 
       role="button">
        <div class="d-flex align-items-center">
            <span class="nav-link-icon">
                <span :data-feather="(item.icono && item.icono !== 'NULL') ? item.icono : 'chevrons-right'"></span>
            </span>
            <span class="nav-link-text">@{{ item.nombre }}</span>
        </div>
    </a>
</script>

{{-- Template: dropdown con subitems (Nivel 2 con hijos en Nivel 3) --}}
<script type="text/x-template" id="tpl-menu-dropdown">
    <div class="w-100">
        <a class="nav-link dropdown-indicator label-1"
           :class="{ 'active': isOpen }"
           :href="'#nv-' + item.id"
           role="button"
           data-bs-toggle="collapse"
           :aria-expanded="isOpen ? 'true' : 'false'"
           :aria-controls="'nv-' + item.id">
            <div class="d-flex align-items-center">
                <div class="dropdown-indicator-icon">
                    <span class="fas fa-caret-right"></span>
                </div>
                <span class="nav-link-icon">
                    <span :data-feather="(item.icono && item.icono !== 'NULL') ? item.icono : 'chevrons-right'"></span>
                </span>
                <span class="nav-link-text">@{{ item.nombre }}</span>
            </div>
        </a>
        <div class="parent-wrapper label-1">
            <ul class="nav collapse parent" 
                :class="{ 'show': isOpen }"
                data-bs-parent="#navbarVerticalNav" 
                :id="'nv-' + item.id">
                <li class="collapsed-nav-item-title d-none">@{{ item.nombre }}</li>
                
                <li class="nav-item" v-for="sub in item.subitems" :key="sub.id">
                    <a class="nav-link" 
                       :class="{ 'active': isSubActive(sub.ruta) }" 
                       :href="baseUrl + sub.ruta">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon">
                                <span :data-feather="(sub.icono && sub.icono !== 'NULL') ? sub.icono : 'chevrons-right'"></span>
                            </span>
                            <span class="nav-link-text">@{{ sub.nombre }}</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</script>

{{-- Template: grupo del menú (Nivel 1: Principal, CRM, ERP...) --}}
<script type="text/x-template" id="tpl-menu-grupo">
    <li class="nav-item">
        <p class="navbar-vertical-label">@{{ grupo.nombre }}</p>
        <hr class="navbar-vertical-line" />
        <div class="nav-item-wrapper">
            <template v-for="item in itemsGrupo">
                {{-- Muestra dropdown si tiene subitems, si no, muestra link normal --}}
                <menu-dropdown
                    v-if="item.subitems && item.subitems.length > 0"
                    :key="'d-' + item.id"
                    :item="item"
                    :base-url="baseUrl"
                    :current-path="currentPath">
                </menu-dropdown>
                <menu-link
                    v-else
                    :key="'l-' + item.id"
                    :item="item"
                    :base-url="baseUrl"
                    :current-path="currentPath">
                </menu-link>
            </template>
        </div>
    </li>
</script>

{{-- Template: app principal --}}
<script type="text/x-template" id="tpl-menu-app">
    <div>
        {{-- Skeleton Loader (Animación de carga) --}}
        <li class="nav-item" v-if="!menuCargado" v-for="n in 5" :key="'sk-'+n">
            <div class="px-3 py-2">
                <div style="height:10px;background:#e3e6ea;border-radius:4px;margin-bottom:6px;width:70%;"></div>
            </div>
        </li>
        
        {{-- Menú real --}}
        <menu-grupo
            v-if="menuCargado"
            v-for="grupo in menu"
            :key="'g-' + (grupo.id || Math.random())"
            :grupo="grupo"
            :base-url="baseUrl"
            :current-path="currentPath">
        </menu-grupo>
    </div>
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    Vue.component('menu-link', {
        props: ['item', 'baseUrl', 'currentPath'],
        template: '#tpl-menu-link',
        computed: {
            enlaceCalculado: function() {
                var ruta = this.item.ruta && this.item.ruta !== 'NULL' ? this.item.ruta : null;
                if (!ruta && this.item.subitems && this.item.subitems.length > 0) {
                    ruta = this.item.subitems[0].ruta;
                }
                
                if (ruta && ruta.startsWith('/') && this.baseUrl.endsWith('/')) {
                    ruta = ruta.substring(1);
                }
                return ruta ? this.baseUrl + ruta : '#';
            },
            isActive: function() {
                var href = this.enlaceCalculado;
                if (href === '#') return false;
                return window.location.href.indexOf(href) !== -1 || (this.item.ruta && this.currentPath.indexOf(this.item.ruta) !== -1);
            }
        }
    });

    Vue.component('menu-dropdown', {
        props: ['item', 'baseUrl', 'currentPath'],
        template: '#tpl-menu-dropdown',
        computed: {
            isOpen: function() {
                var self = this;
                var activaDirecta = this.item.ruta && this.item.ruta !== 'NULL' && this.currentPath.indexOf(this.item.ruta) !== -1;
                
                var hijoActivo = false;
                if (this.item.subitems) {
                    hijoActivo = this.item.subitems.some(function(sub) {
                        return sub.ruta && self.currentPath.indexOf(sub.ruta) !== -1;
                    });
                }
                return activaDirecta || hijoActivo;
            }
        },
        methods: {
            isSubActive: function(ruta) {
                if (!ruta || ruta === 'NULL') return false;
                return this.currentPath.indexOf(ruta) !== -1;
            }
        }
    });

    Vue.component('menu-grupo', {
        props: ['grupo', 'baseUrl', 'currentPath'],
        template: '#tpl-menu-grupo',
        computed: {
            itemsGrupo: function () {
                return this.grupo.items || [];
            }
        }
    });

    new Vue({
        el: '#menu-app',
        template: '#tpl-menu-app',
        data: {
            menu: [],
            menuCargado: false,
            baseUrl: window.server || '', 
            currentPath: window.location.pathname
        },
        mounted: function () {
            this.cargarMenu();
        },
        methods: {
            cargarMenu: async function () {
                try {
                    var token = sessionStorage.getItem('nexus_token') || '';
                    var res = await fetch(apiUrl + '/menu', { 
                        method:  'GET',
                        headers: {
                            'Content-Type':  'application/json',
                            'Authorization': 'Bearer ' + token
                        }
                    });
                    if (res.ok) {
                        var json  = await res.json();
                        this.menu = json.data || []; 
                    }
                } catch (e) {
                    console.error('Error cargando menú:', e);
                } finally {
                    this.menuCargado = true;
                    this.$nextTick(function () {
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    });
                }
            }
        }
    });

});
</script>