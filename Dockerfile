FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

FROM dunglas/frankenphp

RUN install-php-extensions \
    intl \
    zip \
    pdo_pgsql \
    opcache

WORKDIR /app

# Copier les vendors d'abord
COPY --from=vendor /app/vendor ./vendor

# Copier le reste du code
COPY . .

# Créer le cache et logs directories avec les bonnes permissions
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var/

# Optimisations pour la production
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=256
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=20000

EXPOSE 80 443 443/udp