#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
export LOCAL_UID=${LOCAL_UID:-$(id -u)} LOCAL_GID=${LOCAL_GID:-$(id -g)}
if [[ ${CHECK_REUSE_SERVICES:-0} == 1 ]]; then
  # Coordinated sessions may be testing against these services. Refuse to
  # restart them; an ordinary check still provisions the expected environment.
  for service in db mailpit app; do
    container_id=$(docker compose ps --status running --quiet "$service")
    if [[ -z "$container_id" ]] || [[ $(docker inspect --format '{{.State.Health.Status}}' "$container_id") != healthy ]]; then
      printf 'CHECK_REUSE_SERVICES requires an already healthy %s service.\n' "$service" >&2
      exit 1
    fi
  done
else
  docker compose up -d --wait db mailpit app
fi
docker compose exec -T --user www-data app composer validate --strict
docker compose exec -T --user www-data app bin/console lint:container
docker compose exec -T --user www-data app bin/console lint:yaml config
docker compose exec -T --user www-data app composer check-style
docker compose exec -T --user www-data app composer analyse
docker compose exec -T --user www-data app composer test
bash tests/import/schema-proposal-test.sh
bash tests/import/migrations-test.sh
bash tests/import/catalogue-test.sh
bash tests/import/migrations-test.sh accounts-test.php
bash tests/import/migrations-test.sh account-data-test.php
bash tests/import/migrations-test.sh lessons-test.php
bash tests/import/migrations-test.sh variation-test.php
bash tests/import/migrations-test.sh practice-test.php
docker compose run --rm --no-deps frontend sh -c 'npm run lint && npm run format:check && npm run typecheck && npm test && npm run build && cp -R dist/. /app/backend/public/'
docker compose exec -T app php /app/tests/smoke.php
docker compose exec -T app bash /app/scripts/test-acme.sh http://127.0.0.1
for test_file in deploy-test.php health-test.php setup-test.php; do
  docker compose exec -T app php "/app/tests/o2switch/$test_file"
done
docker compose exec -T app bash /app/tests/o2switch/install-test.sh
docker compose exec -T app python3 /app/tests/o2switch/setup-terminal-test.py
docker compose exec -T app bash /app/tests/deploy/prepare-test.sh
docker compose exec -T app php /app/tests/deploy/production-test.php
docker compose exec -T app bash /app/tests/deploy/build-test.sh
bash tests/deploy/backup-restore-test.sh
bash tests/browser/run.sh
