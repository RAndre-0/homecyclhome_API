init-db:
	docker compose exec app php bin/console doctrine:database:create --if-not-exists
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	-docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
