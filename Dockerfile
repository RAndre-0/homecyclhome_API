# Utilise FrankenPHP comme base
FROM dunglas/frankenphp

# Activer les réglages de production PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN install-php-extensions \
	pdo_mysql \
	gd \
	intl \
	zip \
	opcache \
    curl \
    exif \
    fileinfo \
    ldap \
    mbstring \
    mysqli \
    openssl \
    pdo_mysql \
    pdo_pgsql \
    sodium \
    xsl

# Installer Bash et autres utilitaires
RUN apt update && apt install -y bash wget git unzip postgresql-client

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer la Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Définir le répertoire de travail
WORKDIR /app

# Copier le projet dans le bon répertoire
COPY . /app

# Installer les dépendances sans dev
RUN composer install --no-dev --optimize-autoloader

# Définir les permissions
RUN chown -R www-data:www-data /app/public

# Exposer les ports HTTP et HTTPS
EXPOSE 80 443

#CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
# Copier le script d’entrée
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Définir le point d'entrée
ENTRYPOINT ["/entrypoint.sh"]



