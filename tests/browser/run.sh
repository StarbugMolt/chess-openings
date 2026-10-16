#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
export LOCAL_UID=${LOCAL_UID:-$(id -u)} LOCAL_GID=${LOCAL_GID:-$(id -g)}
test_suffix="${RANDOM}_$$"
test_db="chess_browser_test_$test_suffix"
test_app="chess-browser-app-${test_suffix//_/-}"
test_frontend="chess-browser-frontend-${test_suffix//_/-}"
db_created=false
db_granted=false
app_created=false
frontend_created=false
sql() {
  docker compose exec -T db sh -c 'exec mariadb --batch --skip-column-names --user=root --password="$MARIADB_ROOT_PASSWORD" "$@"' sh "$@"
}
cleanup() {
  set +e
  if [[ "$frontend_created" == true ]]; then docker rm -f "$test_frontend" >/dev/null; fi
  if [[ "$app_created" == true ]]; then docker rm -f "$test_app" >/dev/null; fi
  if [[ "$db_granted" == true ]]; then sql <<< "REVOKE ALL PRIVILEGES ON \`$test_db\`.* FROM 'chess'@'%';" >/dev/null; fi
  if [[ "$db_created" == true ]]; then sql <<< "DROP DATABASE \`$test_db\`;" >/dev/null; fi
}
trap cleanup EXIT
if [[ ${CHECK_REUSE_SERVICES:-0} == 1 ]]; then
  for service in db mailpit; do
    container_id=$(docker compose ps --status running --quiet "$service")
    if [[ -z "$container_id" ]] || [[ $(docker inspect --format '{{.State.Health.Status}}' "$container_id") != healthy ]]; then
      printf 'CHECK_REUSE_SERVICES requires an already healthy %s service.\n' "$service" >&2
      exit 1
    fi
  done
else
  docker compose up -d --wait db mailpit
fi
sql <<< "CREATE DATABASE \`$test_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
db_created=true
sql <<< "GRANT ALL PRIVILEGES ON \`$test_db\`.* TO 'chess'@'%';"
db_granted=true
docker compose run -d --no-deps --name "$test_app" -e "DATABASE_URL=mysql://chess:local-chess-only@db:3306/$test_db?charset=utf8mb4" -e APP_ENV=browser -e APP_DEBUG=1 app >/dev/null
app_created=true
# Let the entrypoint finish UID/permission setup before starting a www-data CLI.
# Readiness checks SELECT 1 and does not require schema/catalogue tables.
for ((attempt=0; attempt<60; attempt++)); do
  if docker exec "$test_app" curl -fsS http://localhost/api/health >/dev/null 2>&1; then break; fi
  if (( attempt == 59 )); then echo 'Isolated browser API did not start.' >&2; exit 1; fi
  sleep 0.25
done
docker exec --user www-data "$test_app" bin/console app:database:prepare --source-directory=/app/backend/tests/Fixtures/Lessons/source --no-interaction
# Seed one verified administrator for the live admin journey, granted through the real console
# command. The password is random per run and exists only in this disposable database.
admin_email="admin-$test_suffix@example.test"
admin_password="Admin-$(od -An -N12 -tx1 /dev/urandom | tr -d ' \n')"
admin_hash=$(docker exec "$test_app" php -r 'echo password_hash($argv[1], PASSWORD_ARGON2ID);' "$admin_password")
sql <<< "INSERT INTO \`$test_db\`.user_account (email,password,created_at,verified_at) VALUES ('$admin_email','$admin_hash',UTC_TIMESTAMP(),UTC_TIMESTAMP());"
docker exec --user www-data "$test_app" bin/console app:admin:grant "$admin_email" --no-interaction >/dev/null
docker compose run -d --no-deps --name "$test_frontend" -e "API_PROXY_TARGET=http://$test_app" -e "BROWSER_HOST=$test_frontend" frontend >/dev/null
frontend_created=true
for ((attempt=0; attempt<60; attempt++)); do
  if docker exec "$test_frontend" node -e 'fetch("http://localhost:5173").then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))' >/dev/null 2>&1; then break; fi
  if (( attempt == 59 )); then echo 'Isolated browser frontend did not start.' >&2; exit 1; fi
  sleep 0.25
done
# Separate artifact roots also survive another session's default Playwright cleanup.
docker compose run --rm --no-deps -e "BASE_URL=http://$test_frontend:5173" -e LIVE_LESSON_TEST=1 -e "E2E_ADMIN_EMAIL=$admin_email" -e "E2E_ADMIN_PASSWORD=$admin_password" browser npm run test:e2e -- --output="/app/artifacts/browser/$test_suffix" "$@"
