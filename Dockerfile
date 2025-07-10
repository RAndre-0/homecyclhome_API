FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

FROM dunglas/frankenphp

RUN install-php-extensions \
	intl \
	zip \
	pdo_pgsql

WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .

EXPOSE 80 443 443/udp
