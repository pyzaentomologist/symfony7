# Użycie obrazu PHP 8 z Apache
FROM php:8.5-apache

RUN a2enmod rewrite
# Aktualizacja pakietów systemowych i instalacja niezbędnych narzędzi
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_pgsql mbstring gd

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalacja Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

# Ustawienie katalogu roboczego
WORKDIR /var/www

# Kopiowanie plików projektu
COPY . .

# Instalacja zależności przez Composer
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader && composer update

ARG UID=1000
ARG GID=1000

RUN usermod -u ${UID} www-data \
 && groupmod -g ${GID} www-data \
 && chown -R www-data:www-data /var/www

CMD ["apache2-foreground"]