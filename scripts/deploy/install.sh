#!/usr/bin/env bash
set -euo pipefail
root=${1:?Usage: bash scripts/deploy/install.sh /home/ACCOUNT/apps/chess-openings}
script_dir=$(cd "$(dirname "$0")" && pwd)
bash "$script_dir/../o2switch/install.sh" "$root"
for recipe in build prepare; do
  install -m 700 "$script_dir/$recipe.sh" "$root/control/$recipe.sh.next"
  mv "$root/control/$recipe.sh.next" "$root/control/$recipe.sh"
done
install -m 700 "$script_dir/production.php" "$root/control/production.php.next"
mv "$root/control/production.php.next" "$root/control/production.php"
printf '%s\n'  'Select profile=custom, health_mode=symfony and the installed control/build.sh and control/prepare.sh hooks. Polling is not enabled by this installer.'
