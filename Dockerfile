# Kontajner pre nasadenie na Render (a akýkoľvek iný Docker hosting).
# PHP 8.3 + SQLite, aplikácia beží cez vstavaný PHP server (php artisan serve).
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libpng-dev libsqlite3-dev \
    && docker-php-ext-install zip gd pdo_sqlite calendar \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Najprv závislosti (lepšie využitie cache pri ďalších buildoch)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs database \
    && chmod +x docker/start.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/database.sqlite \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 10000
CMD ["docker/start.sh"]
