#!/usr/bin/env bash
set -euo pipefail
project=$(cd "$(dirname "$0")/../.." && pwd)
php -r '
$lock = json_decode(file_get_contents($argv[1]."/frontend/package-lock.json"), true, 512, JSON_THROW_ON_ERROR);
$version = $lock["packages"]["node_modules/@playwright/test"]["version"];
if (!str_contains(file_get_contents($argv[1]."/compose.yaml"), "mcr.microsoft.com/playwright:v".$version."-")) { throw new RuntimeException("Playwright package and browser image versions must match."); }
' "$project"
fixture=$(mktemp -d /tmp/chess-build-test-XXXXXX)
trap 'rm -rf "$fixture"' EXIT
mkdir -p "$fixture/source/backend/vendor/symfony/runtime/Internal" "$fixture/source/backend/public" "$fixture/source/frontend" "$fixture/bin"
cp "$project/backend/vendor/symfony/runtime/Internal/autoload_runtime.template" "$fixture/source/backend/vendor/symfony/runtime/Internal/"
export CHESS_BUILD_TRACE="$fixture/calls"
cat > "$fixture/composer.php" <<'PHP'
<?php
if (!in_array('--no-plugins', $argv, true) || !in_array('--no-scripts', $argv, true)) exit(1);
file_put_contents(getenv('CHESS_BUILD_TRACE'), "composer install without plugin/script execution\n", FILE_APPEND);
PHP
cat > "$fixture/bin/npm" <<'NPM'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$*" >> "$CHESS_BUILD_TRACE"
if [[ $3 == ci ]]; then
  [[ $* == *--ignore-scripts* && $* == *--cache* ]]
else
  mkdir -p "$2/dist/assets"
  printf '<!doctype html>\n' > "$2/dist/index.html"
  printf 'fixture\n' > "$2/dist/assets/fixture.js"
fi
NPM
chmod 700 "$fixture/bin/npm"
PATH="$fixture/bin:$PATH" bash "$project/scripts/deploy/build.sh" "$fixture/source" "$fixture/release" "$(command -v php)" "$fixture/composer.php"
php -l "$fixture/release/vendor/autoload_runtime.php" > /dev/null
grep -Fq "'Symfony\\\\Component\\\\Runtime\\\\SymfonyRuntime'" "$fixture/release/vendor/autoload_runtime.php"
[[ -f "$fixture/release/public/assets/fixture.js" ]]
[[ ! -e "$fixture/source/backend" ]]
printf '%s\n' 'Build hook checks passed: scripts/plugins disabled, runtime bridge generated, disposable npm cache and built public assets.'
