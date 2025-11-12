# Étape 1 : Construire l'application
FROM php:8.3-fpm AS builder

# Installer dépendances système et extensions PHP nécessaires à Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    curl \
    vim \
    nodejs \
    npm \
    libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers Composer et installer les dépendances
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier le reste du projet
COPY . .

# Donner les droits à www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

# Exposer le port utilisé par Artisan Serve
EXPOSE 8000

# Démarrer le serveur Laravel (pas besoin de Nginx)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
