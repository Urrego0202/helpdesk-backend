FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-interaction --prefer-dist

COPY . .

RUN php artisan key:generate

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]