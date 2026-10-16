#!/usr/bin/env bash
set -euo pipefail
exec bash "$(dirname "$0")/migrations-test.sh" catalogue-test.php
