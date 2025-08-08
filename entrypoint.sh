#!/bin/bash
set -euo pipefail

echo "Attente DB (${DB_HOST:-db}:${DB_PORT:-5432})…"
until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${POSTGRES_USER:-postgres}"; do
  echo "PostgreSQL non dispo, on réessaie…"
  sleep 1
done

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
  echo "DB prête. Exécution des migrations Doctrine…"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
  echo "RUN_MIGRATIONS=0 → on saute les migrations."
fi

echo "Démarrage de PHP-FPM…"
exec php-fpm -F
