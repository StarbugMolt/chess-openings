#!/usr/bin/env bash
# One weekly PHP-only refresh with logs independent from deploy-log retention.
set -euo pipefail
umask 077
control=$(cd "$(dirname "$0")" && pwd)
root=$(dirname "$control")
php_bin=${1:?Usage: opening-sync.sh /absolute/php}
if [[ "$php_bin" != /* || ! -x "$php_bin" ]]; then
  printf '%s\n' 'Supply an absolute executable PHP path.' >&2
  exit 1
fi
mkdir -p "$root/shared/log"
chmod 700 "$root/shared/log"
exec 8>"$root/shared/opening-sync.lock"
flock -n 8 || exit 0
log="$root/shared/log/openings-sync.log"
if [[ -f "$log" ]] && (( $(stat -c %s "$log") > 1000000 )); then
  mv "$log" "$log.previous"
fi
# Symfony's file logger is independently bounded; this captures console output only.
touch "$log"
chmod 600 "$log"
command_status=0
"$php_bin" "$root/current/bin/console" app:openings:sync --env=prod --no-debug --no-interaction >> "$log" 2>&1 || command_status=$?
if (( command_status != 0 )); then
  printf '%s\n' 'Chess Openings weekly catalogue refresh failed; inspect private shared/log/openings-sync.log.' >&2
  exit "$command_status"
fi
