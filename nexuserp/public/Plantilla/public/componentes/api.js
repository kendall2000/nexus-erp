/**
 * Wrapper global de fetch que maneja autenticación y errores 401.
 * Si el servidor responde 401, limpia sesión y redirige al login.
 */
window.apiFetch = async function(url, options = {}) {
    const token = sessionStorage.getItem('nexus_token');

    const defaultHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    if (token) {
        defaultHeaders['Authorization'] = 'Bearer ' + token;
    }

    options.headers = {
        ...defaultHeaders,
        ...(options.headers || {}),
    };

    try {
        const response = await fetch(url, options);

        // Token expirado o inválido → forzar logout
        if (response.status === 401) {
            sessionStorage.removeItem('nexus_token');
            sessionStorage.removeItem('nexus_usuario');
            window.location.href = '/login';
            // Lanza para detener cualquier .then() que venga después
            throw new Error('Sesión expirada');
        }

        return response;
    } catch (error) {
        // Errores de red u otros
        throw error;
    }
};