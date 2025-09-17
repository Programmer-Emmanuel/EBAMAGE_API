# Étape 1 : Image PHP 8.3 FPM sur Debian Bullseye (stable)
FROM php:8.3-fpm-bullseye

# Installer dépendances système et extensions PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        zip \
        libpq-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        gmp \
        libgmp-dev \
        apt-transport-https \
        ca-certificates \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        bcmath \
        gmp \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier le projet Laravel
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Donner les permissions correctes à Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exposer le port HTTP
EXPOSE 8080

# Commande pour lancer Laravel et appliquer migrations automatiquement
CMD php artisan migrate --force && \
    php artisan session:table && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080
