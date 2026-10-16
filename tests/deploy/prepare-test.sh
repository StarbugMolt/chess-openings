#!/usr/bin/env bash
set -euo pipefail
project=$(cd "$(dirname "$0")/../.." && pwd)
fixture=$(mktemp -d /tmp/chess-prepare-test-XXXXXX)
trap 'rm -rf "$fixture"' EXIT
mkdir -p "$fixture/shared"
printf 'APP_ENV=prod\nAPP_DEBUG=0\n' > "$fixture/shared/.env.local"
chmod 600 "$fixture/shared/.env.local"
export CHESS_PREPARE_TRACE="$fixture/calls"
cat > "$fixture/php" <<'PHP'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$CHESS_PREPARE_TRACE"
if [[ ${CHESS_PREPARE_FAIL:-0} == 1 && "$*" == *app:database:prepare* ]]; then exit 1; fi
PHP
chmod 700 "$fixture/php"
prepare() {
  local release="$fixture/$1"
  mkdir -p "$release/public" "$release/migrations/LessonData"
  bash "$project/scripts/deploy/prepare.sh" "$release" "$fixture/shared" "$fixture/php"
}
prepare first
[[ $(readlink "$fixture/first/.env.local") == "$fixture/shared/.env.local" ]]
[[ $(readlink "$fixture/first/var/sessions") == "$fixture/shared/sessions" ]]
[[ $(stat -c %a "$fixture/shared/sessions/prod") == 700 ]]
[[ -d "$fixture/first/var/cache" && ! -L "$fixture/first/var/cache" ]]
printf 'saved-session' > "$fixture/shared/sessions/prod/check"
prepare second
[[ $(cat "$fixture/shared/sessions/prod/check") == saved-session ]]
[[ $(grep -c '^bin/console app:database:prepare ' "$fixture/calls") == 2 ]]
before=$(wc -l < "$fixture/calls")
mkdir -p "$fixture/lesson/public" "$fixture/lesson/migrations/LessonData"
printf '<?php // Lesson migrations are ordered by the real PHP bootstrap command.\n' > "$fixture/lesson/migrations/LessonData/VersionFixture.php"
bash "$project/scripts/deploy/prepare.sh" "$fixture/lesson" "$fixture/shared" "$fixture/php"
[[ $(grep -c '^bin/console app:database:prepare ' "$fixture/calls") == 3 ]]
before=$(wc -l < "$fixture/calls")
if CHESS_PREPARE_FAIL=1 prepare failed > "$fixture/error" 2>&1; then
  printf '%s\n' 'Preparation must stop if the database bootstrap fails.' >&2
  exit 1
fi
[[ $(wc -l < "$fixture/calls") == $((before + 2)) ]]
before=$(wc -l < "$fixture/calls")
chmod 644 "$fixture/shared/.env.local"
if prepare insecure > "$fixture/error" 2>&1; then
  printf '%s\n' 'Preparation must refuse public-readable application secrets.' >&2
  exit 1
fi
[[ $(wc -l < "$fixture/calls") == "$before" ]]
if ! grep -q '^bin/console app:database:prepare --lock-timeout=120 --no-interaction$' "$fixture/calls"; then
  printf '%s\n' 'Preparation must wait a bounded time for the catalogue lock.' >&2
  exit 1
fi
python3 - "$fixture/calls" <<'CHECK'
from pathlib import Path
import sys
calls = Path(sys.argv[1]).read_text().splitlines()
for index, call in enumerate(calls):
    if call.startswith('bin/console app:database:prepare'):
        assert 'production.php' in calls[index - 1], 'Production validation and backup precede database migrations'
    if call.startswith('bin/console cache:clear'):
        assert call.endswith('--no-warmup'), 'Warm cache only once'
CHECK
printf '%s\n' 'Preparation checks passed: private configuration, shared sessions, per-release cache, ordered PHP bootstrap and stop on database failure.'
