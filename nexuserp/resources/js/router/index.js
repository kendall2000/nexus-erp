import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('nexus_token');
    if (to.meta.requiresAuth && !token) return next({ name: 'login' });
    if (to.meta.guest && token)         return next({ name: 'dashboard' });
    next();
});

export default router;