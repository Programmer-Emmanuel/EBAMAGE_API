FROM php:8.3-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers du projet
COPY . .

# Vérifier que l’extension MongoDB est activée
RUN php -m | grep mongodb

# Installer les dépendances Composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Donner les droits corrects pour Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Exposer le port 8000 pour artisan serve
EXPOSE 8000

# Lancer Laravel en mode développement
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
