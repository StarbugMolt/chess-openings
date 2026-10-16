#!/usr/bin/env bash
set -euo pipefail
source_dir=${1:?Source checkout required}
release_dir=${2:?New release directory required}
php_bin=${3:?PHP executable required}
composer_bin=${4:?Composer PHP executable required}
if [[ ! -d "$source_dir/backend" || ! -d "$source_dir/frontend" || -e "$release_dir" ]]; then
  printf '%s\n' 'Build requires backend/frontend source directories and a new release destination.' >&2
  exit 1
fi
"$php_bin" "$composer_bin" install --working-dir="$source_dir/backend" --no-dev --prefer-dist --no-interaction --no-scripts --no-plugins --optimize-autoloader
# Generate Symfony's runtime bridge directly; installation never activates plugins.
"$php_bin" -r '
$vendor = $argv[1]."/backend/vendor";
$template = file_get_contents($vendor."/symfony/runtime/Internal/autoload_runtime.template");
$code = strtr($template, ["%project_dir%" => "dirname(__DIR__)", "%runtime_class%" => var_export("Symfony\\Component\\Runtime\\SymfonyRuntime", true), "%runtime_options%" => "[\"project_dir\" => dirname(__DIR__)]"]);
if (file_put_contents($vendor."/autoload_runtime.php", $code) === false) { exit(1); }
' "$source_dir"
npm --prefix "$source_dir/frontend" ci --include=dev --ignore-scripts --cache "$source_dir/npm-cache"
npm --prefix "$source_dir/frontend" run build
cp -R "$source_dir/frontend/dist/." "$source_dir/backend/public/"
mv "$source_dir/backend" "$release_dir"
