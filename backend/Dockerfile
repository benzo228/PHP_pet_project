FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev curl \
    && docker-php-ext-install pdo_pgsql \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www/html
COPY public/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
