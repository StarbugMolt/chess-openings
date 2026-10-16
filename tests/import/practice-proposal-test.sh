#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
test_db="chess_practice_proposal_${RANDOM}_${RANDOM}_$$"
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
docker compose exec -T --user www-data -e "PRACTICE_PROPOSAL_DATABASE=$test_db" app php /app/tests/import/practice-proposal-test.php
