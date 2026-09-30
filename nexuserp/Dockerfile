FROM php:8.4-apache

# Imagen de Nexus ERP (mismo esquema que sistema-restaurante). Sin Node: la
# plantilla Phoenix ya viene compilada en public/assets y public/vendors.

# Dependencias del sistema + librerías para extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        ca-certificates \
        cron \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por Laravel (gd para dompdf, intl, zip para Excel…)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        gd \
        intl \
        bcmath \
        zip \
        exif \
        pcntl \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: DocumentRoot en /public, mod_rewrite y sin anunciar versiones
ENV APACHE_DOCUMENT_ROOT=/var/www/nexuserp/public
RUN sed -ri -e "s!/var/www/html!\${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/conf-available/*.conf \
    && sed -ri -e "s!/var/www/!/var/www/nexuserp/public!g; s!AllowOverride None!AllowOverride All!g" /etc/apache2/apache2.conf \
    && a2enmod rewrite headers \
    && { echo 'ServerTokens Prod'; echo 'ServerSignature Off'; } > /etc/apache2/conf-available/zz-seguridad.conf \
    && a2enconf zz-seguridad

# Zona horaria del sistema operativo (logs y cron). La de Laravel está en config/app.php.
RUN ln -snf /usr/share/zoneinfo/America/Guatemala /etc/localtime \
    && echo 'America/Guatemala' > /etc/timezone

# PHP para producción
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'memory_limit=512M'; \
        echo 'upload_max_filesize=16M'; \
        echo 'post_max_size=64M'; \
        echo 'max_execution_time=120'; \
        echo 'expose_php=Off'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/nexuserp

# 1) Dependencias PHP (capa en caché mientras no cambien composer.json/lock)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# 2) Resto del código (ver .dockerignore: sin .env, sin la plantilla, sin pruebas)
COPY . .

# 3) Autoload optimizado (sus scripts corren package:discover: solo paquetes de producción)
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Permisos para Laravel (las imágenes subidas van a Contabo, no al disco)
RUN chown -R www-data:www-data /var/www/nexuserp \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint: prepara storage, cachea config/rutas/vistas y arranca Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD curl -fsS http://localhost/up || exit 1
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
