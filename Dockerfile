# Utiliser PHP 8.2 FPM
FROM php:8.2-fpm

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libpq-dev pkg-config \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers de l'application
COPY . .

# Installer toutes les dépendances composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Nettoyer le cache Laravel
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear

# Donner les bonnes permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Exposer le port 8000 pour Laravel Serve
EXPOSE 8000

# Commande pour lancer Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
