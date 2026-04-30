FROM php:8.4-fpm

# Argumentos
ARG user=nexuserp
ARG uid=1000

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Usuario no root
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Directorio de trabajo
WORKDIR /var/www/nexuserp

# Copiar código
COPY . .

# Instalar dependencias PHP
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Permisos Laravel
RUN chown -R $user:www-data /var/www/nexuserp && \
    chmod -R 755 /var/www/nexuserp/storage && \
    chmod -R 755 /var/www/nexuserp/bootstrap/cache

# Configuración Nginx
COPY docker/nginx/nexuserp.conf /etc/nginx/sites-available/default

# Configuración Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configuración OPcache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]