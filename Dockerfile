FROM php:8.3-fpm

# Dépendances système
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev unzip git libpq-dev postgresql-client \
  && docker-php-ext-install intl zip pdo_pgsql opcache \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier l'app
COPY . .

# Dépendances PHP (prod)
RUN git config --global --add safe.directory /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Limites d'upload
RUN { \
  echo 'post_max_size=20M'; \
  echo 'upload_max_filesize=20M'; \
  echo 'max_file_uploads=20'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Opcache (prod)
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.memory_consumption=256'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.preload=/var/www/html/config/preload.php'; \
  echo 'opcache.preload_user=www-data'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Dossiers & droits
RUN mkdir -p var/cache var/log public/uploads \
 && chown -R www-data:www-data var public/uploads

# Entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
