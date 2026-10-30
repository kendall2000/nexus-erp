#!/bin/sh
set -e

# ==========================================================================
# Entrypoint del contenedor de Nexus ERP (PHP 8.4 + Apache).
# Mismo esquema que sistema-restaurante. Se ejecuta CADA vez que arranca:
#  1. Reconstruir la estructura de storage/ (el volumen puede venir vacío).
#  2. Asegurar APP_KEY (solo si falta).
#  3. Cachear config / rutas / vistas / eventos para producción.
#  4. (Opcional) correr migraciones si RUN_MIGRATIONS=true.
#  5. (Opcional) cron del scheduler si ENABLE_SCHEDULER=true.
#  6. Ceder el control al CMD (apache2-foreground).
# ==========================================================================

cd /var/www/nexuserp

echo "[entrypoint] Preparando aplicación..."

# --- 1) Estructura de storage (las imágenes subidas van a Contabo, no al disco) ---
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# --- 2) APP_KEY: generar solo si no está definida ---
# Ojo: las sesiones (SESSION_ENCRYPT=true) y los secretos de 2 pasos se cifran con
# APP_KEY. Una vez en uso NO se cambia: todos perderían la sesión y la 2FA.
if [ -z "${APP_KEY}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "[entrypoint] APP_KEY ausente — generando..."
    php artisan key:generate --force || true
fi

# --- 3) Limpiar y re-cachear (la config en caché puede ser de un build viejo) ---
php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan event:cache || true
chown -R www-data:www-data storage bootstrap/cache || true

# --- 4) Migraciones opcionales (default: NO, la BD está en un servidor compartido) ---
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "[entrypoint] RUN_MIGRATIONS=true — ejecutando migraciones..."
    php artisan migrate --force || true
fi

# --- 5) Cron del scheduler (hoy no hay tareas programadas en routes/console.php) ---
# Corre como www-data para que logs y caché sigan siendo suyos. PHP de la imagen
# oficial está en /usr/local/bin, que no está en el PATH de cron.
if [ "${ENABLE_SCHEDULER}" = "true" ]; then
    cat > /etc/cron.d/laravel-scheduler <<'CRON'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * www-data cd /var/www/nexuserp && /usr/local/bin/php artisan schedule:run >> /var/www/nexuserp/storage/logs/scheduler.log 2>&1
CRON
    chmod 0644 /etc/cron.d/laravel-scheduler
    touch storage/logs/scheduler.log
    chown www-data:www-data storage/logs/scheduler.log || true
    service cron start || true
    echo "[entrypoint] Cron del scheduler iniciado."
fi

echo "[entrypoint] Listo. Arrancando: $*"

# --- 6) Ejecutar el CMD del Dockerfile (apache2-foreground) ---
exec "$@"
