#!/usr/bin/env bash
set -euo pipefail

# Scramble servers[] is pinned in config/scramble.php; still force APP_URL so any
# residual url()/asset() usage cannot pick up local-stack hosts.
export APP_URL="${APP_URL_OPENAPI:-http://localhost:8000}"

php artisan scramble:analyze
php artisan scramble:export --path=openapi-ci.json

expected_server='http://localhost:8000/api'
actual_server="$(php -r '$d=json_decode(file_get_contents("openapi-ci.json"), true); echo $d["servers"][0]["url"] ?? "";')"
if [[ "$actual_server" != "$expected_server" ]]; then
  echo "OpenAPI servers[0].url must be ${expected_server}, got: ${actual_server}" >&2
  echo "Pin config/scramble.php servers and regenerate with:" >&2
  echo "  APP_URL=http://localhost:8000 php artisan scramble:export" >&2
  rm -f openapi-ci.json
  exit 1
fi

diff openapi.json openapi-ci.json
rm -f openapi-ci.json

echo "OpenAPI check passed."

