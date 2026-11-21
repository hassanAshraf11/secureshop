FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libxml2-dev libsqlite3-dev unzip \
    && docker-php-ext-install mbstring pdo_mysql pdo_sqlite dom xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-autoloader
COPY . .
RUN composer dump-autoload && mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache
USER www-data
CMD ["php","-S","0.0.0.0:8080","-t","public","server.php"]
