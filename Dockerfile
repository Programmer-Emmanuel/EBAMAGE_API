FROM php:8.3-cli

# Installer dépendances système + PostgreSQL + GMP
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpq-dev libonig-dev libxml2-dev libzip-dev gmp \
    && docker-php-ext-install pdo pdo_pgsql bcmath gmp

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
