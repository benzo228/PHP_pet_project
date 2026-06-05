FROM php:8.3-fpm
# Устанавливаем расширения для PostgreSQL и Redis
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql
RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html
COPY public/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html