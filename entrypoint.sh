#!/bin/bash

set -e

echo "Attente que PostgreSQL soit prêt sur host: database port: 5432..."

# Attendre que PostgreSQL accepte les connexions
until pg_isready -h database -p 5432 -U "$POSTGRES_USER"; do
  echo "PostgreSQL non disponible, nouvelle tentative..."
  sleep 1
done

echo "BDD prête, lancement des migrations"

# Exécuter les migrations Symfony
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Lancer FrankenPHP
echo "Lancement de FrankenPHP"
exec frankenphp run --config /etc/caddy/Caddyfile
