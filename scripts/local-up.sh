#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
export LOCAL_UID=${LOCAL_UID:-$(id -u)} LOCAL_GID=${LOCAL_GID:-$(id -g)}
docker compose build app frontend
docker compose stop app frontend
docker compose run --rm --no-deps --user root app sh -c 'chown -R "$1:33" /app/backend/var /app/backend/vendor /tmp/composer-cache; chmod -R g+rwX /app/backend/var /tmp/composer-cache' sh "$LOCAL_UID"
docker compose run --rm --no-deps --user root frontend sh -c '
  mkdir -p /app/frontend/dist /app/backend/public/assets
  chown -R "$1:$2" /app/frontend/node_modules /tmp/npm-cache /app/frontend/dist /app/backend/public/assets
  for generated in /app/backend/public/index.html /app/backend/public/favicon.svg; do
    if [ -f "$generated" ]; then chown "$1:$2" "$generated"; fi
  done
' sh "$LOCAL_UID" "$LOCAL_GID"
docker compose run --rm --no-deps --user "$LOCAL_UID:33" app sh -c 'umask 0002; composer install --no-interaction --prefer-dist'
docker compose run --rm --no-deps frontend npm ci
docker compose up -d --wait db mailpit app
docker compose exec -T --user www-data app bin/console app:schema:migrate --no-interaction
docker compose up -d frontend
printf '\nFrontend: http://127.0.0.1:%s\nAPI: http://127.0.0.1:%s/api/health\nMailpit: http://127.0.0.1:%s\n' "${FRONTEND_PORT:-5173}" "${APP_PORT:-8080}" "${MAIL_PORT:-8025}"
