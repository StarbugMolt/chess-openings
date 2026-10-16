#!/usr/bin/env bash
set -euo pipefail
umask 027
control=$(cd "$(dirname "$0")" && pwd)
root=$(dirname "$control")
php_bin=${1:?Usage: run.sh /absolute/php COMMAND [RELEASE_ID]}
shift
if [[ "$php_bin" != /* || ! -x "$php_bin" ]]; then
  printf '%s\n' 'Supply an absolute executable PHP path.' >&2
  exit 1
fi
# Wrapper lock also protects log rotation. PHP uses deploy.lock for all state changes.
exec 8>"$root/poll.lock"
flock -n 8 || exit 0
log="$root/logs/cron.log"
if [[ -f "$log" ]] && (( $(stat -c %s "$log") > 1000000 )); then
  mv "$log" "$log.previous"
fi
# Bound total build time. An interrupted attempt remains recorded until explicit retry.
export CHESS_DEPLOY_DEADLINE_EPOCH=$(( $(date +%s) + 900 ))
command_status=0
timeout --signal=TERM --kill-after=30s 15m "$php_bin" "$control/deploy.php" "$control/config.json" "${@:-poll}" >> "$log" 2>&1 || command_status=$?
if (( command_status != 0 )); then
  # cPanel cron can mail this message to the operator's configured recipient.
  printf '%s\n' 'Chess Openings deployment needs attention; inspect private logs/cron.log and controller status.' >&2
  exit "$command_status"
fi
