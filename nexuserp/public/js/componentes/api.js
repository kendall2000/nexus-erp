/**
 * Wrapper global de fetch para la API. La sesión viaja en la cookie y el
 * interceptor de layouts/extras agrega el token CSRF; si el servidor
 * responde 401, redirige al login.
 */
window.apiFetch = async function(url, options = {}) {
    options.headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(options.headers || {}),
    };

    const response = await fetch(url, options);

    // Sesión expirada o usuario desactivado → al login
    if (response.status === 401) {
        window.location.href = '/login';
        // Lanza para detener cualquier .then() que venga después
        throw new Error('Sesión expirada');
    }

    return response;
};
