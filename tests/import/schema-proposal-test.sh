#!/usr/bin/env bash
# Only a disposable review schema is touched. The application database is never selected.
set -euo pipefail
cd "$(dirname "$0")/../.."
review_db="chess_review_${RANDOM}_${RANDOM}_$$"
created=false

sql() {
  docker compose exec -T db sh -c 'exec mariadb --batch --skip-column-names --user=root --password="$MARIADB_ROOT_PASSWORD" "$@"' sh "$@"
}
cleanup() {
  if [[ "$created" == true ]]; then
    sql <<< "DROP DATABASE IF EXISTS \`$review_db\`;" >/dev/null
  fi
}
trap cleanup EXIT
sql <<< "CREATE DATABASE \`$review_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
created=true
sql "$review_db" < docs/IMPORTER_SCHEMA_PROPOSAL.sql
sql "$review_db" <<'SQL'
INSERT INTO opening_import_batch (id,upstream_sha,content_checksum,importer_version,status,created_at)
VALUES (1,REPEAT('a',40),REPEAT('b',64),'spike-v1','staging',UTC_TIMESTAMP(6));
INSERT INTO opening_import_file VALUES (1,'a.tsv',REPEAT('c',64),100,1);
INSERT INTO chess_position (id,position_key) VALUES
(1,'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -'),
(2,'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -');
INSERT INTO chess_move VALUES (1,1,2,'e2e4','e4');
INSERT INTO opening_entry VALUES (1,1,REPEAT('d',64),'a.tsv',2,'A00','Name','1. e4',2);
INSERT INTO opening_entry_step VALUES (1,1,1);
UPDATE opening_catalogue_state SET active_batch_id=1,catalogue_revision=1 WHERE id=1;
SQL

reject() {
  local expected_code=$1 statement=$2 failure
  if failure=$(sql "$review_db" <<< "$statement" 2>&1); then
    printf 'Schema proposal accepted invalid write: %s\n' "$statement" >&2
    exit 1
  fi
  if [[ "$failure" != *"ERROR $expected_code"* ]]; then
    printf 'Expected SQL error %s, received: %s\n' "$expected_code" "$failure" >&2
    exit 1
  fi
}
reject 1062 "INSERT INTO chess_position (position_key) SELECT position_key FROM chess_position WHERE id=1;"
reject 1062 "INSERT INTO chess_move (from_position_id,to_position_id,uci,san) VALUES (1,1,'e2e4','e4');"
reject 1452 "INSERT INTO chess_move (from_position_id,to_position_id,uci,san) VALUES (1,999,'d2d4','d4');"
reject 4025 "INSERT INTO chess_move (from_position_id,to_position_id,uci,san) VALUES (1,2,'E2E4','e4');"
reject 4025 "INSERT INTO chess_move (from_position_id,to_position_id,uci,san) VALUES (1,2,CONCAT('e2e4',CHAR(10)),'e4');"
reject 4025 "INSERT INTO opening_import_file VALUES (1,'f.tsv',REPEAT('c',64),1,1);"
reject 4025 "UPDATE opening_entry SET eco='a00' WHERE id=1;"
reject 4025 "UPDATE opening_entry SET eco='B00' WHERE id=1;"
reject 4025 "UPDATE opening_entry SET source_row=1 WHERE id=1;"
reject 1452 "UPDATE opening_entry SET source_file='b.tsv',eco='B00' WHERE id=1;"
reject 1062 "INSERT INTO opening_entry SELECT 2,batch_id,source_uid,source_file,source_row,eco,full_name,original_pgn,terminal_position_id FROM opening_entry WHERE id=1;"
reject 4025 "INSERT INTO opening_entry_step VALUES (1,0,1);"
reject 1452 "INSERT INTO opening_entry_step VALUES (1,2,999);"
reject 1451 "DELETE FROM chess_position WHERE id=2;"
reject 1451 "DELETE FROM chess_move WHERE id=1;"
reject 1451 "DELETE FROM opening_import_batch WHERE id=1;"
reject 4025 "INSERT INTO opening_catalogue_state (id) VALUES (2);"
reject 4025 "UPDATE opening_import_batch SET status='unknown' WHERE id=1;"
printf 'Disposable MariaDB proposal: DDL, indexes and 18 rejection checks passed.\n'
