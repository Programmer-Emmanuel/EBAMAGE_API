# Image PHP avec extensions nécessaires
FROM php:8.3-cli

# Arguments Composer
ARG COMPOSER_ALLOW_SUPERUSER=1
ARG COMPOSER_HOME=/composer

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    curl \
    vim \
    libonig-dev \
    npm \
    nodejs \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier tout le projet AVANT d’installer les dépendances
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Donner les droits à www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Exposer le port PHP intégré
EXPOSE 8000

# Commande par défaut : lancer migrate:fresh --seed puis le serveur PHP
CMD ["sh", "-c", "php artisan migrate:fresh --seed && php artisan serve --host=0.0.0.0 --port=8000"]
