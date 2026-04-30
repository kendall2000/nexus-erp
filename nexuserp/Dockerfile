# ═══════════════════════════════════════════════════════════════
# NexusERP — Dockerfile Multi-Stage Production
# Stack: PHP 8.4 FPM + Nginx + Supervisor
# ═══════════════════════════════════════════════════════════════

# ─── STAGE 1: Composer dependencies ────────────────────────────
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

# ─── STAGE 2: Base PHP-FPM con extensiones ─────────────────────
FROM php:8.4-fpm-alpine AS base

ARG USER=nexuserp
ARG UID=1000
ARG GID=1000

# Metadata
LABEL maintainer="NexusERP <admin@nexuserp.com>"
LABEL org.opencontainers.image.source="https://github.com/grupo-rolsa/nexuserp"
LABEL org.opencontainers.image.description="NexusERP — Sistema ERP Multi-Empresa"

# Variables de entorno
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_MEMORY_LIMIT=512M \
    COMPOSER_ALLOW_SUPERUSER=1

# Dependencias del sistema (alpine es más liviano)
RUN apk add --no-cache \
        bash \
        curl \
        git \
        nginx \
        supervisor \
        tzdata \
        icu-data-full \
        # Para extensiones PHP
        $PHPIZE_DEPS \
        autoconf \
        gcc \
        g++ \
        make \
        # Librerías nativas
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        libxml2-dev \
        zlib-dev

# ─── Instalar extensiones PHP ──────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Limpiar build deps (reduce tamaño imagen)
RUN apk del $PHPIZE_DEPS autoconf gcc g++ make \
    && rm -rf /var/cache/apk/* /tmp/*

# ─── Composer ──────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ─── Configuración PHP optimizada ──────────────────────────────
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=${PHP_OPCACHE_MEMORY_CONSUMPTION}'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=${PHP_OPCACHE_MAX_ACCELERATED_FILES}'; \
        echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.jit_buffer_size=128M'; \
        echo 'opcache.jit=tracing'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && { \
        echo 'memory_limit=${PHP_MEMORY_LIMIT}'; \
        echo 'upload_max_filesize=64M'; \
        echo 'post_max_size=64M'; \
        echo 'max_execution_time=120'; \
        echo 'max_input_vars=5000'; \
        echo 'date.timezone=America/Guatemala'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/zz-custom.ini

# ─── Usuario sin privilegios ───────────────────────────────────
RUN addgroup -g ${GID} -S ${USER} \
    && adduser -u ${UID} -S ${USER} -G ${USER} -h /home/${USER} -s /bin/bash \
    && mkdir -p /home/${USER}/.composer \
    && chown -R ${USER}:${USER} /home/${USER}

# ─── STAGE 3: Aplicación final ─────────────────────────────────
FROM base AS app

WORKDIR /var/www/nexuserp

# Copiar dependencias de Composer del stage anterior
COPY --from=vendor /app/vendor ./vendor

# Copiar código fuente con ownership correcto
COPY --chown=${USER}:${USER} . .

# Generar autoload optimizado y permisos
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R ${USER}:${USER} storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ─── Configuración Nginx ───────────────────────────────────────
COPY docker/nginx/nginx.conf       /etc/nginx/nginx.conf
COPY docker/nginx/default.conf     /etc/nginx/http.d/default.conf

# ─── Configuración Supervisor ──────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# ─── Script entrypoint ─────────────────────────────────────────
COPY --chown=${USER}:${USER} docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ─── Healthcheck ───────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

# Exponer solo el puerto HTTP
EXPOSE 80

# Volumes para datos persistentes
VOLUME ["/var/www/nexuserp/storage", "/var/www/nexuserp/bootstrap/cache"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]