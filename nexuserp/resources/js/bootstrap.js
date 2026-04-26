import axios from 'axios';
window.axios = axios;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.baseURL = '/api/v1';

// Interceptor: agrega token en cada request
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('nexus_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// Interceptor: maneja 401 global
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('nexus_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);