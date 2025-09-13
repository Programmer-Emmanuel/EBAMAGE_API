FROM php:8.3-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# Installer l’extension MongoDB via PECL
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet
WORKDIR /var/www
COPY . .

# Installer les dépendances composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Donner les bons droits (facultatif mais recommandé)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
