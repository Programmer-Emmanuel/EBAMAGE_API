# Étape 1 : PHP 8.3 avec CLI
FROM php:8.3-cli

# Installer dépendances système + PostgreSQL
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpq-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www

# Copier tout le projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Donner les permissions correctes à Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exposer le port HTTP
EXPOSE 8080

# Lancer Laravel directement
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
