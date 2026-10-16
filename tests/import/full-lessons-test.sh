#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
test_db="chess_migration_test_${RANDOM}_${RANDOM}_$$"
created=false
granted=false
sql() {
  docker compose exec -T db sh -c 'exec mariadb --batch --skip-column-names --user=root --password="$MARIADB_ROOT_PASSWORD" "$@"' sh "$@"
}
cleanup() {
  if [[ "$created" == true ]]; then
    if [[ "$granted" == true ]]; then
      sql <<< "REVOKE ALL PRIVILEGES ON \`$test_db\`.* FROM 'chess'@'%';" >/dev/null
    fi
    sql <<< "DROP DATABASE IF EXISTS \`$test_db\`;" >/dev/null
  fi
}
trap cleanup EXIT
sql <<< "CREATE DATABASE \`$test_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
created=true
sql <<< "GRANT ALL PRIVILEGES ON \`$test_db\`.* TO 'chess'@'%';"
granted=true
docker compose exec -T --user www-data -e "MIGRATION_TEST_DATABASE=$test_db" -e LESSON_FULL_IMPORT=1 app php /app/tests/import/lessons-test.php
