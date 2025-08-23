#!/bin/bash
set -euo pipefail

echo "Attente DB (${DB_HOST:-db}:${DB_PORT:-5432})…"
until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}"; do
  echo "PostgreSQL non dispo, nouvelle tentative…"
  sleep 1
done

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
  echo "DB prête. Exécution des migrations Doctrine…"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --conn=migrations
else
  echo "RUN_MIGRATIONS=0 → on saute les migrations."
fi

# Prépare var/ pour www-data
umask 0002
cd /var/www/html
mkdir -p var/log var/cache/prod/http_cache public/uploads
chown -R www-data:www-data var public/uploads
find var -type d -exec chmod 2775 {} \;
chmod -R 2775 public/uploads

# warmup en www-data
rm -rf var/cache/* || true
su -s /bin/sh www-data -c "php bin/console cache:warmup --env=prod" || true

echo "Démarrage de PHP-FPM…"
exec php-fpm -F
