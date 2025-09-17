# Étape 1 : Image PHP 8.3 CLI
FROM php:8.3-cli

# Installer les dépendances système et extensions PHP
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

# Copier tout le projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Donner les permissions correctes à Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exposer le port pour Laravel
EXPOSE 8080

# Commande pour lancer Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
