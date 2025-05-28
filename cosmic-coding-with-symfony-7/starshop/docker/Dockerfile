# Użycie obrazu PHP 8 z Apache
FROM php:8.3-apache

RUN a2enmod rewrite
# Aktualizacja pakietów systemowych i instalacja niezbędnych narzędzi
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_mysql mbstring gd

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

CMD ["apache2-foreground"]