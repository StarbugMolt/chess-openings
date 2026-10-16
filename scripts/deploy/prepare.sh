#!/usr/bin/env bash
set -euo pipefail
release_dir=${1:?New release directory required}
shared_dir=${2:?Shared directory required}
php_bin=${3:?PHP executable required}
if [[ ! -d "$release_dir/public" || ! -f "$shared_dir/.env.local" || -L "$shared_dir/.env.local" ]]; then
  printf '%s\n' 'Preparation requires a release and a real private shared/.env.local.' >&2
  exit 1
fi
if [[ $(stat -c %a "$shared_dir/.env.local") != 600 ]]; then
  printf '%s\n' 'shared/.env.local must have mode 600.' >&2
  exit 1
fi
# Private shared configuration is the only source for production secrets and origin.
unset APP_SECRET DATABASE_URL MAILER_DSN MAIL_FROM DEFAULT_URI ALLOW_LOCAL_MAIL_TRANSPORT
script_dir=$(cd "$(dirname "$0")" && pwd)
"$php_bin" "$script_dir/production.php" "$release_dir" "$shared_dir"
ln -s "$shared_dir/.env.local" "$release_dir/.env.local"
mkdir -p "$shared_dir/sessions/prod" "$shared_dir/log" "$release_dir/var/cache"
chmod 700 "$shared_dir/sessions" "$shared_dir/sessions/prod" "$shared_dir/log"
ln -s "$shared_dir/sessions" "$release_dir/var/sessions"
ln -s "$shared_dir/log" "$release_dir/var/log"
cd "$release_dir"
export APP_ENV=prod APP_DEBUG=0
"$php_bin" bin/console app:database:prepare --lock-timeout=120 --no-interaction
"$php_bin" bin/console cache:clear --no-warmup
"$php_bin" bin/console cache:warmup
