#!/usr/bin/env bash
# Exercise Apache routing against the local Compose app; never use on hosting.
set -euo pipefail
cd "$(dirname "$0")/.."
base=${1:-http://127.0.0.1:${APP_PORT:-8080}}
challenge="$PWD/backend/public/.well-known/acme-challenge"
work=$(mktemp -d)
probe=''
cleanup() {
  if [[ -n "$probe" ]]; then rm -f "$probe"; fi
  rmdir "$challenge" "$(dirname "$challenge")" 2>/dev/null || true
  rm -rf "$work"
}
trap cleanup EXIT
mkdir -p "$challenge"
probe=$(mktemp "$challenge/check-XXXXXXXX")
printf 'local-http01-verification\n' > "$probe"
chmod 644 "$probe"
token=$(basename "$probe")
curl --fail --silent --show-error --max-time 10 "$base/.well-known/acme-challenge/$token" > "$work/response"
cmp "$probe" "$work/response"
[[ $(curl --silent --show-error --max-time 10 -o /dev/null -w '%{http_code}' "$base/.well-known/acme-challenge/missing-$token") == 404 ]]
for path in /.env /.git/config /.well-known/private /.well-known/acme-challenge/ /.well-known/acme-challenge/.env /.well-known/acme-challenge/probe.php; do
  [[ $(curl --silent --show-error --max-time 10 -o /dev/null -w '%{http_code}' "$base$path") == 403 ]]
done
echo 'Apache HTTP-01 checks passed: token served, missing token 404, other hidden paths blocked.'
