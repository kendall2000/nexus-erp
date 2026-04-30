FROM php:8.4-apache

ARG USER=nexuserp
ARG UID=1000

# Dependencias
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libzip-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Apache: mod_rewrite + DocumentRoot a public
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/nexuserp/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Usuario
RUN useradd -G www-data,root -u $UID -d /home/$USER $USER

# OPcache producción
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'memory_limit=512M'; \
    echo 'upload_max_filesize=64M'; \
    echo 'post_max_size=64M'; \
} > /usr/local/etc/php/conf.d/zz-custom.ini

WORKDIR /var/www/nexuserp

COPY --chown=$USER:www-data . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R $USER:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN chown -R www-data:www-data /var/www/nexuserp

EXPOSE 80