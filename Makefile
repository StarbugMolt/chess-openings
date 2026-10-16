.PHONY: dev stop check build mail logs catalogue
dev:
	bash scripts/local-up.sh
stop:
	docker compose down
check:
	bash scripts/check.sh
build:
	docker compose run --rm --no-deps frontend npm run build
mail:
	docker compose exec -T --user www-data app bin/console app:dev:send-test-email
logs:
	docker compose logs -f app frontend mailpit
catalogue:
	docker compose exec -T --user www-data app bin/console app:database:prepare --no-interaction
